<?php

namespace App\Http\Controllers;

use App\Models\Peca;
use App\Models\Proposta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        abort_unless($user->setor === $proposta->celebracao_setor, 403,
            'Apenas o setor que está com a Celebração pode movimentá-la.');

        if ($proposta->celebracao_setor === 'osc') {
            abort_unless($user->osc && $user->osc->id === $proposta->osc_id, 403,
                'Esta parceria pertence a outra OSC.');
        }
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
            'celebracaoTramitacoes.remetente',
        ]);

        $pecas     = $proposta->pecas;
        $progresso = Peca::progresso($pecas);

        return view('celebracao.show', compact('proposta', 'pecas', 'progresso'));
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

        $data = $request->validate(['parecer' => ['required', 'string']], [
            'parecer.required' => 'Informe o motivo da devolução.',
        ]);

        $etapaAnterior = (int) $proposta->celebracao_etapa - 1;
        $setorAnterior = Proposta::ETAPAS_CELEBRACAO[$etapaAnterior]['setor'];

        $proposta->celebracaoTramitacoes()->create([
            'de_setor'    => $proposta->celebracao_setor,
            'para_setor'  => $setorAnterior,
            'enviado_por' => auth()->id(),
            'enviado_em'  => now(),
            'parecer'     => $data['parecer'],
            'status'      => 'devolvido',
        ]);

        $proposta->update([
            'celebracao_etapa' => $etapaAnterior,
            'celebracao_setor' => $setorAnterior,
        ]);

        return redirect()->route('celebracao.show', $proposta)
            ->with('success', 'Celebração devolvida para ' . Proposta::SETORES_CELEBRACAO[$setorAnterior] . '.');
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
