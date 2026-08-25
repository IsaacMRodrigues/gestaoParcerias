<?php

namespace App\Http\Controllers;

use App\Models\Peca;
use App\Models\Proposta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Trâmite da Celebração (Fluxo Etapa de Celebração), ancorado na proposta
 * aprovada: UG convoca → OSC (plano + habilitação) → UG (aprova) → SCP →
 * SEPLAN → UG → SCP → PJ → SCP (termo + publicação) → SCP (autorização) →
 * OSC (dados bancários) → SCP (OP global) → UG (assina) → SCP (empenho).
 */
class CelebracaoController extends Controller
{
    /**
     * Só o setor que está com a Celebração pode movimentá-la. Quando a vez é da
     * OSC, exige-se ainda que seja a OSC desta proposta.
     */
    private function autorizarSetor(Proposta $proposta): void
    {
        abort_unless($proposta->temTramiteCelebracao(), 422,
            'A Celebração começa após a aprovação da proposta.');
        abort_if($proposta->celebracaoConcluida(), 422, 'A Celebração desta parceria já foi concluída.');

        $user = auth()->user();
        // setorNoTramite(): a OSC atua como setor 'osc' e não tem lotação.
        abort_unless($user->setorNoTramite() === $proposta->celebracao_setor, 403,
            'Apenas o setor que está com a Celebração pode movimentá-la.');

        if ($proposta->celebracao_setor === 'osc') {
            abort_unless($user->ehRepresentanteOsc() && $user->osc->id === $proposta->osc_id, 403,
                'Esta parceria pertence a outra OSC.');

            // E, dentro da OSC, quem tem a função da Celebração marcada.
            abort_if($user->oscSemFuncao('osc_celebracao'), 403,
                'Sua conta não tem a função "Celebração da parceria". '
                .'Peça ao responsável legal da OSC para marcá-la em Usuários da Organização.');
        }
    }

    /**
     * Listagem do trâmite — a porta de entrada da Celebração no menu.
     *
     * Existe porque o item "Celebração" do menu apontava para a lista de
     * Instrumentos, protegida por `formalizacao`: SCP, SEPLAN e PJ conduzem
     * etapas do fluxo e mesmo assim viam cadeado. Aqui a régua é participar do
     * trâmite; o recorte por órgão continua sendo o de sempre (visiveisPara).
     */
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

        return view('celebracao.index', compact('propostas'));
    }

    /**
     * Tela da Celebração. No primeiro acesso cria (idempotente) o checklist e
     * marca o início do trâmite.
     */
    public function show(Proposta $proposta): View
    {
        abort_unless($proposta->temTramiteCelebracao(), 404,
            'Esta proposta ainda não foi aprovada.');

        Peca::sincronizar($proposta, 'celebracao');

        if (!$proposta->celebracaoIniciada()) {
            $proposta->update(['celebracao_iniciada_em' => now()]);
        }

        $proposta->load([
            'chamamento.programa.orgao', 'osc',
            'pecas.assinante.roles', 'pecas.assinante.orgao',
            // O carimbo do Termo nomeia também quem contra-assinou pela OSC.
            'pecas.contraAssinante.roles', 'pecas.contraAssinante.osc',
            'celebracaoTramitacoes.remetente',
        ]);

        $pecas     = $proposta->pecas;
        $progresso = Peca::progresso($pecas);

        return view('celebracao.show', compact('proposta', 'pecas', 'progresso'));
    }

    /**
     * Anexo avulso na etapa corrente.
     *
     * O checklist é fechado — vem do template — e a etapa da publicação é o
     * caso claro do que faltava: são dois veículos previstos (Diário Oficial e
     * site), mas às vezes a publicação sai em mais de uma edição, ou a
     * Procuradoria pede um documento a mais. Sem espaço no checklist, isso
     * ficava fora do sistema.
     *
     * O anexo nasce opcional de propósito: complementa a instrução, não pode
     * travar o encaminhamento como as peças obrigatórias do fluxo.
     */
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
            'setor'       => $proposta->celebracao_setor,
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

        $pendentes = $proposta->pendenciasCelebracao();
        abort_unless(empty($pendentes), 422,
            'Conclua antes de encaminhar: ' . implode(', ', $pendentes) . '.');

        $data = $request->validate(['parecer' => ['nullable', 'string']]);

        $proxEtapa = (int) $proposta->celebracao_etapa + 1;
        $proxSetor = Proposta::ETAPAS_CELEBRACAO[$proxEtapa]['setor'];

        $proposta->celebracaoTramitacoes()->create([
            'de_setor'    => $proposta->celebracao_setor,
            'para_setor'  => $proxSetor,
            'enviado_por' => auth()->id(),
            'enviado_em'  => now(),
            'parecer'     => $data['parecer'] ?? null,
            'status'      => 'enviado',
        ]);

        $proposta->update([
            'celebracao_etapa' => $proxEtapa,
            'celebracao_setor' => $proxSetor,
        ]);

        return redirect()->route('celebracao.show', $proposta)
            ->with('success', 'Celebração encaminhada para ' . Proposta::SETORES_CELEBRACAO[$proxSetor] . '.');
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
            // Devolução dirigida: o erro nem sempre está na etapa anterior. Se o
            // documento da etapa 6 saiu errado e o trâmite já vai na 9, voltar de
            // uma em uma obrigaria três setores a reprocessar o que estava certo.
            'etapa_destino' => ['nullable', 'integer', 'min:0', 'lt:' . $atual],
        ], [
            'parecer.required'   => 'Informe o motivo da devolução.',
            'etapa_destino.lt'   => 'A devolução só volta para uma etapa já vencida.',
            'etapa_destino.min'  => 'Etapa de destino inválida.',
        ]);

        // Sem escolha, devolve para a etapa imediatamente anterior — o
        // comportamento de sempre.
        $destino     = $data['etapa_destino'] ?? $atual - 1;
        $setorDestino = Proposta::ETAPAS_CELEBRACAO[$destino]['setor'];

        $proposta->celebracaoTramitacoes()->create([
            'de_setor'    => $proposta->celebracao_setor,
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

        $proposta->update([
            'celebracao_etapa' => $destino,
            'celebracao_setor' => $setorDestino,
        ]);

        return redirect()->route('celebracao.show', $proposta)
            ->with('success', 'Celebração devolvida para a etapa ' . ($destino + 1) . ' — '
                . Proposta::SETORES_CELEBRACAO[$setorDestino] . '.');
    }

    /**
     * Conclui a Celebração na última etapa (SCP, após anexar o empenho global).
     */
    public function concluir(Proposta $proposta): RedirectResponse
    {
        $this->autorizarSetor($proposta);
        abort_unless($proposta->ultimaEtapaCelebracao(), 422,
            'A Celebração só pode ser concluída na última etapa.');

        $pendentes = $proposta->pendenciasCelebracao();
        abort_unless(empty($pendentes), 422,
            'Conclua antes de encerrar: ' . implode(', ', $pendentes) . '.');

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

        return redirect()->route('celebracao.show', $proposta)
            ->with('success', 'Celebração concluída. A parceria está apta a iniciar a execução.');
    }
}
