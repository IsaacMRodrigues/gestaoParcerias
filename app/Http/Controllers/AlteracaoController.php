<?php

namespace App\Http\Controllers;

use App\Models\Alteracao;
use App\Models\Instrumento;
use App\Models\Peca;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Alterações da Parceria (módulo 3.3).
 *
 * A OSC pede a mudança durante a execução; a Unidade Gestora autoriza; a SCP
 * processa. Enquanto o pedido está com a OSC, ela edita o próprio Plano de
 * Trabalho — é o que o modelo manda —, e o retrato guardado na abertura mostra
 * a quem analisa o que mudou.
 *
 * A Proposta de Alteração não é redigida: nasce dos campos preenchidos e é
 * regerada a cada gravação, até alguém assiná-la.
 */
class AlteracaoController extends Controller
{
    private function osc(): ?int
    {
        return auth()->user()->ehRepresentanteOsc() ? auth()->user()->osc?->id : null;
    }

    /** Vê quem atua na execução da parceria ou a OSC dela. */
    private function autorizarVer(Alteracao $alteracao): void
    {
        $oscId = $this->osc();

        $user = auth()->user();

        if ($oscId) {
            abort_unless($oscId === $alteracao->osc()?->id, 403, 'Esta alteração é de outra organização.');

            return;
        }

        abort_unless($user->can('execucao') || $user->can('formalizacao') || $user->can('monitoramento'),
            403, 'Seu perfil não acompanha a execução das parcerias.');

        // O recorte por Secretaria vale aqui como nas demais telas: a alteração
        // é de uma parceria, e a parceria tem dono.
        abort_unless($user->podeVerTodosOrgaos() || $alteracao->orgao()?->id === $user->orgao_id,
            403, 'Esta alteração é de outra Secretaria.');
    }

    /** Só o setor que está com o pedido o movimenta. */
    private function autorizarSetor(Alteracao $alteracao): void
    {
        abort_if($alteracao->decidida(), 422, 'Esta alteração já foi decidida.');

        $user = auth()->user();
        abort_unless($user->setorNoTramite() === $alteracao->setor_atual, 403,
            'Apenas o setor que está com o pedido pode movimentá-lo.');

        if ($alteracao->setor_atual === 'osc') {
            abort_unless($this->osc() === $alteracao->osc()?->id, 403, 'Esta alteração é de outra OSC.');
        }
    }

    public function index(): View
    {
        $user  = auth()->user();
        $oscId = $this->osc();

        // Quem é do município só chega aqui se atua na parceria; e vê o que é
        // da sua Secretaria, o mesmo recorte das demais listagens.
        abort_unless($oscId || $user->can('execucao') || $user->can('formalizacao') || $user->can('monitoramento'),
            403, 'Seu perfil não acompanha a execução das parcerias.');

        $alteracoes = Alteracao::with(['instrumento.proposta.osc'])
            ->when($oscId, fn ($q) => $q->whereHas('instrumento.proposta', fn ($p) => $p->where('osc_id', $oscId)))
            ->unless($oscId || $user->podeVerTodosOrgaos(), fn ($q) => $q->whereHas(
                'instrumento.proposta.chamamento.programa',
                fn ($p) => $p->where('orgao_id', $user->orgao_id),
            ))
            ->latest('id')
            ->get();

        // Parcerias que podem receber pedido: as que estão em vigor.
        $instrumentos = Instrumento::with('proposta.osc')
            ->whereIn('status', ['vigente', 'assinado'])
            ->when($oscId, fn ($q) => $q->whereHas('proposta', fn ($p) => $p->where('osc_id', $oscId)))
            ->get();

        return view('alteracoes.index', compact('alteracoes', 'instrumentos', 'oscId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'instrumento_id' => ['required', 'exists:instrumentos,id'],
            'titulo'         => ['required', 'string', 'max:255'],
            'descricao'      => ['required', 'string'],
            'justificativa'  => ['required', 'string'],
        ], [
            'descricao.required'     => 'Descreva a alteração desejada.',
            'justificativa.required' => 'A justificativa é o que fundamenta o pedido — sem ela a UG não tem o que analisar.',
        ]);

        $instrumento = Instrumento::with('proposta')->findOrFail($dados['instrumento_id']);

        // Quem pede a alteração é a organização parceira.
        abort_unless($this->osc() === $instrumento->proposta?->osc_id, 403,
            'Apenas a OSC da parceria pede alteração.');

        // Um pedido de cada vez: dois abertos no mesmo termo alterariam o mesmo
        // plano de trabalho em paralelo, e nenhum dos dois retratos valeria.
        abort_if(
            Alteracao::where('instrumento_id', $instrumento->id)->emAndamento()->exists(),
            422,
            'Já existe um pedido de alteração em andamento nesta parceria.'
        );

        $alteracao = Alteracao::create([
            ...$dados,
            'numero'      => Alteracao::where('instrumento_id', $instrumento->id)->count() + 1,
            'status'      => 'rascunho',
            'setor_atual' => 'osc',
            'etapa'       => 0,
            'plano_antes' => $instrumento->proposta ? Alteracao::retratoDoPlano($instrumento->proposta) : null,
            'criada_por'  => auth()->id(),
        ]);

        return redirect()->route('alteracoes.show', $alteracao)
            ->with('success', 'Pedido de alteração aberto. Ajuste o Plano de Trabalho, instrua o checklist e assine.');
    }

    public function show(Alteracao $alteracao): View
    {
        $this->autorizarVer($alteracao);

        Peca::sincronizar($alteracao, 'alteracao');
        $this->regerarProposta($alteracao);

        $alteracao->load(['instrumento.proposta.osc', 'pecas.assinante.roles', 'pecas.assinante.osc',
            'tramitacoes.remetente', 'criador', 'decididaPor']);

        $proposta = $alteracao->proposta();
        $proposta?->load(['metas.etapas', 'planoItens', 'desembolsos', 'enderecosExecucao']);

        return view('alteracoes.show', [
            'alteracao' => $alteracao,
            'proposta'  => $proposta,
            'pecas'     => $alteracao->pecas,
            'progresso' => Peca::progresso($alteracao->pecas),
            'podeEditarPlano' => $alteracao->emElaboracao()
                && $this->osc() === $alteracao->osc()?->id
                && auth()->user()->can('osc_propostas'),
        ]);
    }

    public function atualizar(Request $request, Alteracao $alteracao): RedirectResponse
    {
        $this->autorizarSetor($alteracao);
        abort_unless($alteracao->emElaboracao(), 422, 'O pedido já saiu da elaboração.');

        $alteracao->update($request->validate([
            'titulo'        => ['required', 'string', 'max:255'],
            'descricao'     => ['required', 'string'],
            'justificativa' => ['required', 'string'],
        ]));

        $this->regerarProposta($alteracao);

        return back()->with('success', 'Pedido atualizado.');
    }

    public function avancar(Request $request, Alteracao $alteracao): RedirectResponse
    {
        $this->autorizarSetor($alteracao);
        abort_unless($alteracao->etapa < count(Alteracao::ETAPAS) - 1, 422,
            'Não é possível encaminhar a partir desta etapa.');

        $pendentes = $alteracao->pendencias();
        abort_unless(empty($pendentes), 422, 'Conclua antes de encaminhar: ' . implode(', ', $pendentes) . '.');

        $data    = $request->validate(['parecer' => ['nullable', 'string']]);
        $proxima = $alteracao->etapa + 1;
        $destino = Alteracao::ETAPAS[$proxima]['setor'];

        $alteracao->tramitacoes()->create([
            'de_setor' => $alteracao->setor_atual, 'para_setor' => $destino,
            'enviado_por' => auth()->id(), 'enviado_em' => now(),
            'parecer' => $data['parecer'] ?? null, 'status' => 'enviado',
        ]);

        $alteracao->update([
            'etapa'       => $proxima,
            'setor_atual' => $destino,
            'status'      => 'em_analise',
            // O plano fecha quando o pedido sai da OSC: daí em diante o que a
            // UG e a SCP leem é o mesmo que a OSC apresentou.
            'enviada_em'  => $alteracao->enviada_em ?? now(),
        ]);

        return back()->with('success', 'Pedido encaminhado para ' . Alteracao::SETORES[$destino] . '.');
    }

    public function devolver(Request $request, Alteracao $alteracao): RedirectResponse
    {
        $this->autorizarSetor($alteracao);
        abort_if($alteracao->etapa === 0, 422, 'Não há etapa anterior para devolver.');
        abort_if($alteracao->setor_atual === 'osc', 403, 'A OSC não devolve o trâmite.');

        $data = $request->validate(['parecer' => ['required', 'string']], [
            'parecer.required' => 'Informe o motivo da devolução — é o que a OSC vai ler para corrigir.',
        ]);

        $anterior = $alteracao->etapa - 1;
        $destino  = Alteracao::ETAPAS[$anterior]['setor'];

        $alteracao->tramitacoes()->create([
            'de_setor' => $alteracao->setor_atual, 'para_setor' => $destino,
            'enviado_por' => auth()->id(), 'enviado_em' => now(),
            'parecer' => $data['parecer'], 'status' => 'devolvido',
        ]);

        $alteracao->update([
            'etapa'       => $anterior,
            'setor_atual' => $destino,
            'status'      => $destino === 'osc' ? 'rascunho' : 'em_analise',
        ]);

        return back()->with('success', 'Pedido devolvido para ' . Alteracao::SETORES[$destino] . '.');
    }

    /** Decisão final da SCP: aprovada ou indeferida, sempre com motivo. */
    public function decidir(Request $request, Alteracao $alteracao): RedirectResponse
    {
        $this->autorizarSetor($alteracao);
        abort_unless($alteracao->etapa === count(Alteracao::ETAPAS) - 1, 422,
            'A decisão é da última etapa do fluxo.');

        $data = $request->validate([
            'decisao' => ['required', 'in:aprovada,indeferida'],
            'motivo'  => ['required', 'string'],
        ], [
            'motivo.required' => 'Registre a conclusão — é o que a OSC vai ler.',
        ]);

        if ($data['decisao'] === 'aprovada') {
            $pendentes = $alteracao->pendencias();
            abort_unless(empty($pendentes), 422, 'Conclua antes de aprovar: ' . implode(', ', $pendentes) . '.');
        }

        $alteracao->update([
            'status'         => $data['decisao'],
            'setor_atual'    => null,
            'decisao_motivo' => $data['motivo'],
            'decidida_por'   => auth()->id(),
            'decidida_em'    => now(),
        ]);

        return back()->with('success', $data['decisao'] === 'aprovada'
            ? 'Alteração aprovada. Formalize o aditivo ou o apostilamento na tela da parceria.'
            : 'Alteração indeferida. A OSC verá o motivo no portal.');
    }

    /**
     * A Proposta de Alteração nasce dos campos — nunca depois de assinada.
     *
     * Mesma regra da prestação de contas: enquanto ninguém assinou, o documento
     * acompanha o que está preenchido; assinado, ele congela.
     */
    private function regerarProposta(Alteracao $alteracao): void
    {
        $peca = $alteracao->pecas()->where('chave', 'proposta_alteracao')->first();

        if (!$peca || $peca->assinado()) {
            return;
        }

        $peca->update([
            'conteudo' => \App\Support\Modelo::preencher(
                Peca::modeloTexto('alteracao', 'proposta_alteracao'),
                Peca::tokensPara($alteracao),
            ),
        ]);
    }
}
