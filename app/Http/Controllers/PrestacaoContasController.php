<?php

namespace App\Http\Controllers;

use App\Models\Instrumento;
use App\Models\Peca;
use App\Models\PrestacaoBem;
use App\Models\PrestacaoContas;
use App\Models\PrestacaoGlosa;
use App\Models\User;
use App\Support\PrestacaoDocumento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Prestação de contas (módulo 3.4).
 *
 * A OSC preenche campos — não redige documentos: o ofício, o relatório e o
 * resumo da folha são gerados do que ela lançou, com as somas prontas, e é
 * esse texto que vai à assinatura. Enquanto não houver assinatura, cada
 * gravação regera o documento; depois dela, nada mais o altera.
 *
 * Quem faz o quê: a OSC monta e envia, a SCP analisa previamente, e a Unidade
 * Gestora — com o Gestor da Parceria e a Comissão de Monitoramento — decide.
 */
class PrestacaoContasController extends Controller
{
    /** Blocos gerados a partir dos campos, e o que cada um regera. */
    private const GERADOS = [
        'oficio_encaminhamento' => 'oficio',
        'relatorio'             => 'relatorio',
        'resumo_folha'          => 'resumoFolha',
    ];

    private function osc(): ?int
    {
        return auth()->user()->ehRepresentanteOsc() ? auth()->user()->osc?->id : null;
    }

    /** Vê a prestação quem atua no município ou a OSC daquela parceria. */
    private function autorizarVer(PrestacaoContas $pc): void
    {
        $oscId = $this->osc();

        abort_unless(
            ($oscId && $oscId === $pc->osc()?->id) || auth()->user()->can('prestacao_contas')
                || auth()->user()->can('execucao') || auth()->user()->can('monitoramento'),
            403,
            'Esta prestação de contas é de outra parceria.'
        );
    }

    /** Só o setor que está com ela a movimenta — e, na OSC, a OSC da parceria. */
    private function autorizarSetor(PrestacaoContas $pc): void
    {
        abort_if($pc->concluida(), 422, 'Esta prestação de contas já foi encerrada.');

        $user = auth()->user();
        abort_unless($user->setorNoTramite() === $pc->setor, 403,
            'Apenas o setor que está com a prestação de contas pode movimentá-la.');

        if ($pc->setor === 'osc') {
            abort_unless($this->osc() === $pc->osc()?->id, 403, 'Esta prestação de contas é de outra OSC.');
        }
    }

    public function index(): View
    {
        $oscId = $this->osc();

        $prestacoes = PrestacaoContas::with(['instrumento.proposta.osc'])
            ->when($oscId, fn ($q) => $q->whereHas('instrumento.proposta', fn ($p) => $p->where('osc_id', $oscId)))
            ->latest('periodo_fim')
            ->get();

        // Parcerias que ainda podem receber uma prestação: vigentes, e da OSC
        // quando quem olha é a OSC.
        $instrumentos = Instrumento::with('proposta.osc')
            ->whereIn('status', ['vigente', 'assinado', 'encerrado'])
            ->when($oscId, fn ($q) => $q->whereHas('proposta', fn ($p) => $p->where('osc_id', $oscId)))
            ->get();

        return view('prestacao-contas.index', compact('prestacoes', 'instrumentos', 'oscId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'instrumento_id' => ['required', 'exists:instrumentos,id'],
            'tipo'           => ['required', Rule::in(array_keys(PrestacaoContas::TIPOS))],
            'periodo_inicio' => ['required', 'date'],
            'periodo_fim'    => ['required', 'date', 'after_or_equal:periodo_inicio'],
        ], [
            'periodo_fim.after_or_equal' => 'O fim do período não pode ser anterior ao início.',
        ]);

        $instrumento = Instrumento::with('proposta')->findOrFail($dados['instrumento_id']);

        // Quem presta contas é a organização parceira.
        abort_unless($this->osc() === $instrumento->proposta?->osc_id, 403,
            'Apenas a OSC da parceria abre a prestação de contas.');

        $pc = PrestacaoContas::create([
            ...$dados,
            'numero'      => $dados['tipo'] === 'parcial'
                ? PrestacaoContas::where('instrumento_id', $instrumento->id)->where('tipo', 'parcial')->count() + 1
                : null,
            'iniciada_em' => now(),
            'created_by'  => auth()->id(),
            // O responsável pela prestação começa sendo quem a abriu; a OSC muda se quiser.
            'responsavel_nome'  => auth()->user()->name,
            'responsavel_email' => auth()->user()->email,
        ]);

        return redirect()->route('prestacao-contas.show', $pc)
            ->with('success', 'Prestação de contas aberta. Preencha os campos e anexe os documentos.');
    }

    public function show(PrestacaoContas $pc): View
    {
        $this->autorizarVer($pc);

        Peca::sincronizar($pc, 'prestacao_contas');
        $this->semear($pc);
        $this->regerarDocumentos($pc);

        $pc->load(['instrumento.proposta.osc', 'metas', 'glosas', 'bens',
            'pecas.assinante.roles', 'pecas.assinante.osc', 'tramitacoes.remetente']);

        return view('prestacao-contas.show', [
            'pc'        => $pc,
            'pecas'     => $pc->pecas,
            'progresso' => Peca::progresso($pc->pecas),
            'despesas'  => $pc->despesas(),
            'repasses'  => $pc->repasses(),
        ]);
    }

    /**
     * Primeira abertura: traz as metas do Plano de Trabalho e cria a linha de
     * cada bloco financeiro. Sem isso a OSC começaria com tabelas vazias e
     * teria de redigitar o que já foi aprovado.
     */
    private function semear(PrestacaoContas $pc): void
    {
        if ($pc->metas()->doesntExist()) {
            foreach ($pc->instrumento?->proposta?->metas ?? [] as $i => $meta) {
                $pc->metas()->create([
                    'meta_id'             => $meta->id,
                    'descricao'           => $meta->descricao,
                    'quantidade_prevista' => $meta->meta_quantitativa,
                    'ordem'               => $i,
                ]);
            }
        }

        // O "aprovado" de cada bloco vem do plano de aplicação da proposta —
        // é exatamente o que o Anexo VI compara com o executado. Antes era
        // digitado à mão, e a OSC tinha de copiar o próprio plano.
        $aprovado = $this->aprovadoPorBloco($pc);

        foreach (array_keys(PrestacaoContas::BLOCOS) as $bloco) {
            PrestacaoGlosa::firstOrCreate(
                ['prestacao_id' => $pc->id, 'natureza' => $bloco],
                ['valor_aprovado' => $aprovado[$bloco] ?? 0],
            );
        }
    }

    /**
     * Quanto o plano de aplicação aprovou em cada bloco do Anexo VI.
     *
     * Os blocos da prestação agrupam naturezas de despesa (ver
     * PrestacaoContas::BLOCOS), e o plano lança item a item por natureza —
     * então a conversão é somar os itens de cada natureza do bloco.
     */
    private function aprovadoPorBloco(PrestacaoContas $pc): array
    {
        $proposta = $pc->instrumento?->proposta;
        if (!$proposta) {
            return [];
        }

        $porNatureza = $proposta->planoPorNatureza();
        $porBloco    = [];

        foreach (PrestacaoContas::BLOCOS as $bloco => $def) {
            $soma = 0.0;
            foreach ($def['naturezas'] as $natureza) {
                $soma += $porNatureza[$natureza] ?? 0;
            }
            $porBloco[$bloco] = $soma;
        }

        return $porBloco;
    }

    /** Regera os documentos que nascem dos campos — nunca os já assinados. */
    private function regerarDocumentos(PrestacaoContas $pc, ?string $apenas = null): void
    {
        $pc->load(['metas', 'glosas', 'bens', 'instrumento.proposta.osc']);

        foreach (self::GERADOS as $chave => $metodo) {
            if ($apenas && $apenas !== $chave) {
                continue;
            }

            $peca = $pc->pecas()->where('chave', $chave)->first();
            if (!$peca || $peca->assinado()) {
                continue;
            }

            $peca->update(['conteudo' => \App\Support\Modelo::preencher(
                PrestacaoDocumento::{$metodo}($pc),
                ['data_extenso' => now()->locale('pt_BR')->translatedFormat('j \d\e F \d\e Y')],
            )]);
        }
    }

    /**
     * Grava um bloco de campos. Cada aba da tela manda o seu, e só o dela — é
     * o que permite salvar o ofício sem mexer no relatório.
     */
    public function atualizar(Request $request, PrestacaoContas $pc): RedirectResponse
    {
        $this->autorizarSetor($pc);
        abort_unless($pc->setor === 'osc', 403, 'Estes campos são preenchidos pela OSC.');

        $bloco = $request->validate(['bloco' => ['required', Rule::in(['oficio', 'reo', 'ref', 'folha'])]])['bloco'];

        $regras = match ($bloco) {
            'oficio' => [
                'folhas'                => ['nullable', 'integer', 'min:1'],
                'parcelas_recebidas'    => ['nullable', 'integer', 'min:0'],
                'responsavel_nome'      => ['nullable', 'string', 'max:255'],
                'responsavel_email'     => ['nullable', 'email', 'max:255'],
                'responsavel_telefone'  => ['nullable', 'string', 'max:30'],
            ],
            'reo' => [
                'objetivo_geral'        => ['nullable', 'string'],
                'objetivos_especificos' => ['nullable', 'string'],
                'conclusao'             => ['nullable', 'string'],
                'metas'                 => ['nullable', 'array'],
            ],
            'ref' => [
                'banco'              => ['nullable', 'string', 'max:60'],
                'agencia'            => ['nullable', 'string', 'max:20'],
                'conta_corrente'     => ['nullable', 'string', 'max:30'],
                'saldo_anterior'     => ['nullable', 'numeric'],
                'outros_creditos'    => ['nullable', 'numeric'],
                'recursos_proprios'  => ['nullable', 'numeric'],
                'despesas_bancarias' => ['nullable', 'numeric'],
                'valor_ressarcido'   => ['nullable', 'numeric'],
                'glosas'             => ['nullable', 'array'],
            ],
            'folha' => [
                'folha_funcionarios' => ['nullable', 'integer', 'min:0'],
                'folha_salarios'     => ['nullable', 'numeric'],
                'folha_vantagens'    => ['nullable', 'numeric'],
                'folha_adicionais'   => ['nullable', 'numeric'],
                'folha_inss'         => ['nullable', 'numeric'],
                'folha_irrf'         => ['nullable', 'numeric'],
                'folha_plano_saude'  => ['nullable', 'numeric'],
                'folha_fgts'         => ['nullable', 'numeric'],
                'folha_ferias'       => ['nullable', 'numeric'],
                'folha_rescisao'     => ['nullable', 'numeric'],
            ],
        };

        $dados = $request->validate($regras);
        $pc->update(collect($dados)->except(['metas', 'glosas'])->all());

        foreach ($request->input('metas', []) as $id => $campos) {
            $pc->metas()->where('id', $id)->update([
                'quantidade_atendida' => $campos['quantidade_atendida'] ?? null,
                'cumpriu'             => (bool) ($campos['cumpriu'] ?? false),
                'justificativa'       => $campos['justificativa'] ?? null,
            ]);
        }

        foreach ($request->input('glosas', []) as $natureza => $campos) {
            $pc->glosas()->where('natureza', $natureza)->update(
                collect($campos)->only(['valor_aprovado', 'glosa_mes_1', 'glosa_mes_2', 'glosa_mes_3',
                    'glosa_mes_4', 'glosa_mes_5', 'glosa_mes_6'])->map(fn ($v) => $v ?: 0)->all()
            );
        }

        $this->regerarDocumentos($pc->fresh(), $bloco === 'oficio' ? 'oficio_encaminhamento'
            : ($bloco === 'folha' ? 'resumo_folha' : 'relatorio'));

        return back()->with('success', 'Dados salvos — o documento foi atualizado com as somas.');
    }

    public function adicionarBem(Request $request, PrestacaoContas $pc): RedirectResponse
    {
        $this->autorizarSetor($pc);

        $dados = $request->validate([
            'especificacao'  => ['required', 'string', 'max:255'],
            'quantidade'     => ['required', 'numeric', 'min:0.01'],
            'valor_unitario' => ['required', 'numeric', 'min:0'],
            'documento'      => ['nullable', 'string', 'max:60'],
        ]);

        $pc->bens()->create($dados);
        $this->regerarDocumentos($pc->fresh(), 'relatorio');

        return back()->with('success', 'Bem móvel incluído.');
    }

    public function removerBem(PrestacaoContas $pc, PrestacaoBem $bem): RedirectResponse
    {
        $this->autorizarSetor($pc);
        abort_unless($bem->prestacao_id === $pc->id, 404);

        $bem->delete();
        $this->regerarDocumentos($pc->fresh(), 'relatorio');

        return back()->with('success', 'Bem móvel removido.');
    }

    public function avancar(Request $request, PrestacaoContas $pc): RedirectResponse
    {
        $this->autorizarSetor($pc);
        abort_unless($pc->podeAvancar(), 422, 'Não é possível encaminhar a partir desta etapa.');

        $pendentes = $pc->pendencias();
        abort_unless(empty($pendentes), 422, 'Conclua antes de encaminhar: ' . implode(', ', $pendentes) . '.');

        $data = $request->validate(['parecer' => ['nullable', 'string']]);
        $proxima = $pc->etapa + 1;
        $destino = PrestacaoContas::ETAPAS[$proxima]['setor'];

        $pc->tramitacoes()->create([
            'de_setor' => $pc->setor, 'para_setor' => $destino,
            'enviado_por' => auth()->id(), 'enviado_em' => now(),
            'parecer' => $data['parecer'] ?? null, 'status' => 'enviado',
        ]);
        $pc->update(['etapa' => $proxima, 'setor' => $destino]);

        return back()->with('success', 'Prestação de contas encaminhada para ' . PrestacaoContas::SETORES[$destino] . '.');
    }

    public function devolver(Request $request, PrestacaoContas $pc): RedirectResponse
    {
        $this->autorizarSetor($pc);
        abort_if($pc->etapa === 0, 422, 'Não há etapa anterior para devolver.');
        // Devolver é decisão da Administração; a OSC cumpre a sua etapa e encaminha.
        abort_if($pc->setor === 'osc', 403, 'A OSC não devolve o trâmite.');

        $data = $request->validate(['parecer' => ['required', 'string']], [
            'parecer.required' => 'Informe o motivo da devolução — é o que a OSC vai ler para corrigir.',
        ]);

        $anterior = $pc->etapa - 1;
        $destino  = PrestacaoContas::ETAPAS[$anterior]['setor'];

        $pc->tramitacoes()->create([
            'de_setor' => $pc->setor, 'para_setor' => $destino,
            'enviado_por' => auth()->id(), 'enviado_em' => now(),
            'parecer' => $data['parecer'], 'status' => 'devolvido',
        ]);
        $pc->update(['etapa' => $anterior, 'setor' => $destino]);

        return back()->with('success', 'Prestação de contas devolvida para ' . PrestacaoContas::SETORES[$destino] . '.');
    }

    public function concluir(PrestacaoContas $pc): RedirectResponse
    {
        $this->autorizarSetor($pc);
        abort_unless($pc->ultimaEtapa(), 422, 'A prestação de contas só é encerrada na última etapa.');

        $pendentes = $pc->pendencias();
        abort_unless(empty($pendentes), 422, 'Conclua antes de encerrar: ' . implode(', ', $pendentes) . '.');

        $pc->tramitacoes()->create([
            'de_setor' => $pc->setor, 'para_setor' => 'ug',
            'enviado_por' => auth()->id(), 'enviado_em' => now(), 'status' => 'concluido',
        ]);
        $pc->update(['concluida_em' => now()]);

        return back()->with('success', 'Prestação de contas encerrada.');
    }
}
