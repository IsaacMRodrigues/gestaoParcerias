<?php

namespace App\Http\Controllers;

use App\Models\Peca;
use App\Models\Proposta;
use App\Models\User;
use App\Support\Devolucao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Trâmite da Celebração, ancorado na proposta aprovada (etapas em Proposta::ETAPAS_CELEBRACAO). */
class CelebracaoController extends Controller
{
    /** Só quem tem a vez na Celebração pode movimentá-la (pela OSC, a OSC desta proposta). */
    private function autorizarSetor(Proposta $proposta): void
    {
        abort_unless($proposta->temTramiteCelebracao(), 422,
            'A Celebração começa após a aprovação da proposta.');
        abort_if($proposta->celebracaoConcluida(), 422, 'A Celebração desta parceria já foi concluída.');

        $user = auth()->user();
        // setorNoTramite(): a OSC atua como setor 'osc' e não tem lotação. Na
        // etapa conjunta, qualquer dos setores que ainda não concluiu a parte.
        abort_unless($proposta->usuarioTemAVezNaCelebracao($user), 403,
            $proposta->etapaConjuntaCelebracao() && in_array($user->setorNoTramite(), Proposta::setoresDaEtapaCelebracao((int) $proposta->celebracao_etapa), true)
                ? 'Seu setor já concluiu a parte dele nesta etapa; falta o outro.'
                : 'Apenas o setor que está com a Celebração pode movimentá-la.');

        if ($proposta->celebracao_setor === 'osc') {
            abort_unless($user->ehRepresentanteOsc() && $user->osc->id === $proposta->osc_id, 403,
                'Esta parceria pertence a outra OSC.');

            // E, dentro da OSC, quem tem a função da Celebração marcada.
            abort_if($user->oscSemFuncao('osc_celebracao'), 403,
                'Sua conta não tem a função "Celebração da parceria". '
                .'Peça ao responsável legal da OSC para marcá-la em Usuários da Organização.');
        }
    }

    /** Lista do trâmite: quem participa da Celebração, no recorte por órgão de sempre. */
    public function index(): View
    {
        $user = auth()->user();
        abort_unless($user->participaDaCelebracao(), 403,
            'Seu setor não participa do trâmite da Celebração.');

        $propostas = Proposta::with(['osc', 'chamamento.programa.orgao'])
            ->visiveisPara($user)
            ->comTramiteCelebracao()
            // Em andamento primeiro; dentro de cada grupo, o que se moveu por último.
            ->orderByRaw('celebracao_concluida_em IS NULL DESC')
            ->orderByDesc('updated_at')
            ->paginate(15);

        // Um chamamento pode ter várias vencedoras, cada uma com a sua Celebração: a lista as reúne
        // sob o chamamento.
        $grupos = $propostas->getCollection()->groupBy('chamamento_id');
        $vencedorasPorChamamento = Proposta::comTramiteCelebracao()
            ->whereIn('chamamento_id', $grupos->keys()->filter())
            ->selectRaw('chamamento_id, count(*) as total')->groupBy('chamamento_id')
            ->pluck('total', 'chamamento_id');

        return view('celebracao.index', compact('propostas', 'grupos', 'vencedorasPorChamamento'));
    }

    /** Tela da Celebração. No primeiro acesso cria (idempotente) o checklist e marca o início. */
    public function show(Proposta $proposta): View
    {
        // A visibilidade já foi conferida em ParceriaVisivel; aqui, do lado da Prefeitura, só quem
        // participa do trâmite (abrir a tela grava: sincroniza peças e marca o início).
        $user = auth()->user();
        abort_unless($user->ehRepresentanteOsc() || $user->participaDaCelebracao(), 403,
            'Seu setor não participa do trâmite da Celebração.');

        abort_unless($proposta->temTramiteCelebracao(), 404,
            'Esta proposta ainda não foi aprovada.');

        Peca::sincronizar($proposta, 'celebracao');
        $this->regerarPlanoDeTrabalho($proposta);

        if (!$proposta->celebracaoIniciada()) {
            $proposta->update(['celebracao_iniciada_em' => now()]);
        }

        $proposta->load([
            'chamamento.programa.orgao', 'chamamento.processo', 'osc',
            'pecas.assinante.roles', 'pecas.assinante.orgao',
            // O carimbo do Termo nomeia também quem contra-assinou pela OSC.
            'pecas.contraAssinante.roles', 'pecas.contraAssinante.osc',
            // E as assinaturas em sequência do Termo.
            'pecas.assinaturasPartes', 'pecas.arquivoDaOsc.osc', 'gestorDaCelebracao',
            'celebracaoTramitacoes.remetente',
        ]);

        $pecas     = $proposta->pecas;
        $progresso = Peca::progresso($pecas);

        // As outras vencedoras do mesmo chamamento, cada uma na sua Celebração.
        // Só do lado da Prefeitura: a OSC acompanha a própria parceria.
        $outrasVencedoras = $user->ehRepresentanteOsc() || !$proposta->chamamento_id
            ? collect()
            : Proposta::with('osc')->comTramiteCelebracao()
                ->where('chamamento_id', $proposta->chamamento_id)->whereKeyNot($proposta->id)
                ->orderBy('id')->get();

        return view('celebracao.show', compact('proposta', 'pecas', 'progresso', 'outrasVencedoras'));
    }

    /**
     * O Plano de Trabalho do checklist acompanha o plano lançado no Portal até ser assinado;
     * a peça antiga, de quando era anexo, segue como arquivo.
     */
    private function regerarPlanoDeTrabalho(Proposta $proposta): void
    {
        $peca = $proposta->pecas()->where('chave', 'plano_trabalho')->first();

        if (!$peca || $peca->tipo !== 'modelo' || $peca->assinado()) {
            return;
        }

        $peca->update(['conteudo' => \App\Support\PlanoDocumento::render($proposta)]);
    }

    /** Anexo avulso na etapa corrente; nasce opcional, para não travar o encaminhamento. */
    public function adicionarAnexo(Request $request, Proposta $proposta): RedirectResponse
    {
        $this->autorizarSetor($proposta);

        $data = $request->validate([
            'rotulo' => ['required', 'string', 'max:120'],
        ], [
            'rotulo.required' => 'Dê um nome ao anexo (ex.: "Publicação — 2ª edição").',
        ]);

        $etapa = (int) $proposta->celebracao_etapa;
        $pecas = $proposta->pecas()->get();

        // Entra no fim do bloco da própria etapa: a ordem da última peça de lá,
        // que o desempate por id resolve.
        $ordem = $pecas->filter(fn (Peca $p) => $p->selecaoEtapa() === $etapa)->max('ordem')
            ?? $pecas->max('ordem')
            ?? 0;

        $peca = $proposta->pecas()->create([
            'categoria'   => 'celebracao',
            'chave'       => 'extra_' . Str::uuid()->toString(),
            'rotulo'      => $data['rotulo'],
            'tipo'        => 'arquivo',
            'obrigatorio' => false,
            'ordem'       => $ordem,
            'extra'       => true,
            // Na etapa conjunta, o anexo é de quem o abriu (UG ou SCP).
            'setor'       => auth()->user()->setorNoTramite(),
            'etapa'       => $etapa,
            'criado_por'  => auth()->id(),
        ]);

        return back()->withFragment('peca-' . $peca->id)
            ->with('success', 'Espaço de anexo criado. Envie o arquivo abaixo.');
    }

    public function avancar(Request $request, Proposta $proposta): RedirectResponse
    {
        $this->autorizarSetor($proposta);
        abort_unless($proposta->podeAvancarCelebracao(), 422,
            'Não é possível encaminhar a partir desta etapa.');

        $meuSetor  = auth()->user()->setorNoTramite();
        $conjunta  = $proposta->etapaConjuntaCelebracao();
        $pendentes = $proposta->pendenciasCelebracao($conjunta ? $meuSetor : null);
        abort_unless(empty($pendentes), 422,
            'Conclua antes de encaminhar: ' . implode(', ', $pendentes) . '.');

        $data = $request->validate(['parecer' => ['nullable', 'string']]);

        // Etapa conjunta: cada setor conclui a sua parte; a Celebração só
        // avança quando o último concluir.
        if ($conjunta) {
            $concluidas = array_values(array_unique(array_merge($proposta->celebracao_partes_concluidas ?? [], [$meuSetor])));
            $faltam = array_diff(Proposta::setoresDaEtapaCelebracao((int) $proposta->celebracao_etapa), $concluidas);

            if ($faltam) {
                $proposta->celebracaoTramitacoes()->create([
                    'de_setor'    => $meuSetor,
                    'para_setor'  => reset($faltam),
                    'enviado_por' => auth()->id(),
                    'enviado_em'  => now(),
                    'parecer'     => trim('Parte concluída na etapa conjunta. ' . ($data['parecer'] ?? '')),
                    'status'      => 'enviado',
                ]);
                $proposta->update(['celebracao_partes_concluidas' => $concluidas]);

                return redirect()->route('celebracao.show', $proposta)
                    ->with('success', 'Sua parte foi concluída. A Celebração avança quando '
                        . implode(' e ', array_map(fn ($s) => Proposta::SETORES_CELEBRACAO[$s] ?? $s, $faltam)) . ' concluir a dela.');
            }
        }

        $proxEtapa = (int) $proposta->celebracao_etapa + 1;
        $proxSetor = Proposta::ETAPAS_CELEBRACAO[$proxEtapa]['setor'];

        // O Gestor da Parceria é escolhido pela SCP; escolhido para o Termo, vale também para a OP Global.
        $gestor = null;
        if ($proxSetor === 'gestor') {
            $gestorId = $request->validate(
                ['gestor_id' => [$proposta->celebracao_gestor_id ? 'nullable' : 'required', Rule::in($proposta->gestoresElegiveis()->pluck('id')->all())]],
                ['gestor_id.required' => 'Escolha o Gestor da Parceria.', 'gestor_id.in' => 'Escolha um Gestor da Parceria da Secretaria.'],
            )['gestor_id'] ?? $proposta->celebracao_gestor_id;
            $gestor = User::find($gestorId);
        }

        $proposta->celebracaoTramitacoes()->create([
            'de_setor'    => $meuSetor,
            'para_setor'  => $proxSetor,
            'enviado_por' => auth()->id(),
            'enviado_em'  => now(),
            'parecer'     => $data['parecer'] ?? null,
            'status'      => 'enviado',
        ]);

        $proposta->update(array_merge([
            'celebracao_etapa'             => $proxEtapa,
            'celebracao_setor'             => $proxSetor,
            'celebracao_partes_concluidas' => null,
        ], $gestor ? ['celebracao_gestor_id' => $gestor->id] : []));

        $para = implode(' e ', array_map(fn ($s) => Proposta::SETORES_CELEBRACAO[$s] ?? $s, Proposta::setoresDaEtapaCelebracao($proxEtapa)))
            . ($gestor ? ' — ' . $gestor->name : '');

        return redirect()->route('celebracao.show', $proposta)
            ->with('success', 'Celebração encaminhada para ' . $para . '.');
    }

    public function devolver(Request $request, Proposta $proposta): RedirectResponse
    {
        $this->autorizarSetor($proposta);
        abort_if((int) $proposta->celebracao_etapa === 0, 422, 'Não há etapa anterior para devolver.');
        // A devolução é uma decisão da Administração — a OSC apenas cumpre a sua etapa.
        abort_if($proposta->celebracao_setor === 'osc', 403,
            'A OSC não devolve o trâmite; conclua a sua etapa e encaminhe.');

        $atual = (int) $proposta->celebracao_etapa;

        $data = $request->validate([
            'parecer' => ['required', 'string'],
            // Devolução dirigida: o erro nem sempre está na etapa anterior.
            'etapa_destino' => ['nullable', 'integer', 'min:0', 'lt:' . $atual],
        ], [
            'parecer.required'   => 'Informe o motivo da devolução.',
            'etapa_destino.lt'   => 'A devolução só volta para uma etapa já vencida.',
            'etapa_destino.min'  => 'Etapa de destino inválida.',
        ]);

        // Com documentos marcados, só eles reabrem e o trâmite volta à etapa do mais antigo. Sem marcar,
        // a etapa escolhida (ou a anterior).
        $escolhidas   = Devolucao::escolhidas($request, $proposta->documentosDevolviveis());
        $destino      = Devolucao::etapaDestino($escolhidas, $data['etapa_destino'] ?? $atual - 1);
        $setorDestino = Proposta::ETAPAS_CELEBRACAO[$destino]['setor'];
        $data['parecer'] = Devolucao::reabrir($escolhidas, $data['parecer'], auth()->user());

        $proposta->celebracaoTramitacoes()->create([
            'de_setor'    => auth()->user()->setorNoTramite(),
            'para_setor'  => $setorDestino,
            'enviado_por' => auth()->id(),
            'enviado_em'  => now(),
            // O salto fica escrito no histórico: quem lê depois precisa saber
            // que não foi uma devolução de um passo.
            'parecer'     => $destino < $atual - 1
                ? 'Devolvido da etapa ' . ($atual + 1) . ' para a etapa ' . ($destino + 1) . '. ' . $data['parecer']
                : $data['parecer'],
            'status'      => 'devolvido',
        ]);

        // Voltar a uma etapa conjunta reabre as duas partes.
        $proposta->update([
            'celebracao_etapa'             => $destino,
            'celebracao_setor'             => $setorDestino,
            'celebracao_partes_concluidas' => null,
        ]);

        return redirect()->route('celebracao.show', $proposta)
            ->with('success', 'Celebração devolvida para a etapa ' . ($destino + 1) . ' — '
                . Proposta::SETORES_CELEBRACAO[$setorDestino] . '.');
    }

    /** Conclui a Celebração na última etapa (SCP, após anexar o empenho). */
    public function concluir(Proposta $proposta): RedirectResponse
    {
        $this->autorizarSetor($proposta);
        abort_unless($proposta->ultimaEtapaCelebracao(), 422,
            'A Celebração só pode ser concluída na última etapa.');

        $pendentes = $proposta->pendenciasCelebracao();
        abort_unless(empty($pendentes), 422,
            'Conclua antes de encerrar: ' . implode(', ', $pendentes) . '.');

        $instrumento = DB::transaction(function () use ($proposta) {
            $proposta->celebracaoTramitacoes()->create([
                'de_setor'    => $proposta->celebracao_setor,
                'para_setor'  => 'ug',
                'enviado_por' => auth()->id(),
                'enviado_em'  => now(),
                'status'      => 'concluido',
            ]);

            $proposta->update([
                'celebracao_setor'        => 'ug',
                'celebracao_concluida_em' => now(),
            ]);

            return $proposta->criarInstrumento();
        });

        return redirect()->route('celebracao.show', $proposta)
            ->with('success', "Celebração concluída. A parceria segue para a execução como o instrumento nº {$instrumento->numero}.");
    }
}
