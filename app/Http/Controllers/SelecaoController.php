<?php

namespace App\Http\Controllers;

use App\Models\Chamamento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Trâmite da Seleção (Fluxo Seleção do cliente):
 * UG (relatório + ata + resultado provisório) → SCP (publica o provisório)
 * → UG (recursos + resultado definitivo) → SCP (publica o definitivo e emite
 * o Termo de Adjudicação e Homologação) → Prefeito (assina) → encerra.
 */
class SelecaoController extends Controller
{
    /**
     * Só o setor que está com a Seleção pode movimentá-la.
     */
    private function autorizarSetor(Chamamento $chamamento): void
    {
        abort_unless($chamamento->temTramiteSelecao(), 422,
            'Dispensa/Inexigibilidade não passa por julgamento de propostas.');
        abort_if($chamamento->selecaoConcluida(), 422, 'A Seleção deste chamamento já foi encerrada.');
        abort_unless(auth()->user()->setor === $chamamento->selecao_setor, 403,
            'Apenas o setor que está com a Seleção pode movimentá-la.');
    }

    /**
     * Encaminha a Seleção para o próximo setor do fluxo.
     */
    public function avancar(Request $request, Chamamento $chamamento): RedirectResponse
    {
        $this->autorizarSetor($chamamento);
        abort_unless($chamamento->podeAvancarSelecao(), 422,
            'Não é possível encaminhar a partir desta etapa.');

        $pendentes = $chamamento->pendenciasSelecao();
        abort_unless(empty($pendentes), 422,
            'Conclua antes de encaminhar: ' . implode(', ', $pendentes) . '.');

        $data = $request->validate(['parecer' => ['nullable', 'string']]);

        $proxEtapa = (int) $chamamento->selecao_etapa + 1;
        $proxSetor = Chamamento::ETAPAS_SELECAO[$proxEtapa]['setor'];

        $chamamento->selecaoTramitacoes()->create([
            'de_setor'    => $chamamento->selecao_setor,
            'para_setor'  => $proxSetor,
            'enviado_por' => auth()->id(),
            'enviado_em'  => now(),
            'parecer'     => $data['parecer'] ?? null,
            'status'      => 'enviado',
        ]);

        $chamamento->update([
            'selecao_etapa' => $proxEtapa,
            'selecao_setor' => $proxSetor,
            'status'        => 'em_analise',
        ]);

        return redirect()->route('chamamentos.selecao', $chamamento)
            ->with('success', 'Seleção encaminhada para ' . Chamamento::SETORES_SELECAO[$proxSetor] . '.');
    }

    /**
     * Devolve para a etapa anterior (pendência a corrigir).
     */
    public function devolver(Request $request, Chamamento $chamamento): RedirectResponse
    {
        $this->autorizarSetor($chamamento);
        abort_if((int) $chamamento->selecao_etapa === 0, 422, 'Não há etapa anterior para devolver.');

        $data = $request->validate(['parecer' => ['required', 'string']], [
            'parecer.required' => 'Informe o motivo da devolução.',
        ]);

        $etapaAnterior = (int) $chamamento->selecao_etapa - 1;
        $setorAnterior = Chamamento::ETAPAS_SELECAO[$etapaAnterior]['setor'];

        $chamamento->selecaoTramitacoes()->create([
            'de_setor'    => $chamamento->selecao_setor,
            'para_setor'  => $setorAnterior,
            'enviado_por' => auth()->id(),
            'enviado_em'  => now(),
            'parecer'     => $data['parecer'],
            'status'      => 'devolvido',
        ]);

        $chamamento->update([
            'selecao_etapa' => $etapaAnterior,
            'selecao_setor' => $setorAnterior,
        ]);

        return redirect()->route('chamamentos.selecao', $chamamento)
            ->with('success', 'Seleção devolvida para ' . Chamamento::SETORES_SELECAO[$setorAnterior] . '.');
    }

    /**
     * Encerra a Seleção na última etapa (Prefeito, após assinar a homologação)
     * e devolve o chamamento à Unidade Gestora para a Celebração.
     */
    /**
     * Propostas ainda sem decisão neste chamamento.
     *
     * Quem já foi aprovada ou reprovada saiu do julgamento; rascunho nunca
     * chegou a ser apresentado.
     */
    private function propostasEmJulgamento(Chamamento $chamamento)
    {
        return $chamamento->propostas()
            ->whereIn('status', ['submetida', 'em_analise'])
            ->with('osc')
            ->get();
    }

    /**
     * Adjudicar: declarar quem venceu.
     *
     * O Termo que encerra a Seleção é de *Adjudicação* e Homologação —
     * adjudicar é justamente atribuir o objeto ao vencedor. Até aqui esse ato
     * não existia no sistema: a Seleção era encerrada, a mensagem prometia "segue
     * para a Celebração" e nenhuma proposta mudava de status. Como a Celebração
     * exige proposta 'aprovada', o fluxo morria num vão — o chamamento ficava
     * encerrado e a parceria não tinha por onde continuar.
     *
     * As não escolhidas são reprovadas no mesmo ato: o resultado do julgamento
     * é um só, e deixá-las 'submetida' significaria mantê-las na fila de
     * análise para sempre.
     */
    private function adjudicarPropostas(Chamamento $chamamento, array $vencedoras): void
    {
        foreach ($this->propostasEmJulgamento($chamamento) as $proposta) {
            $proposta->update([
                'status' => in_array($proposta->id, $vencedoras) ? 'aprovada' : 'reprovada',
            ]);
        }
    }

    /**
     * Regras da declaração de vencedoras, comuns ao encerramento e à
     * declaração posterior (chamamentos encerrados antes deste ato existir).
     */
    private function validarVencedoras(Request $request, Chamamento $chamamento): array
    {
        $candidatas = $this->propostasEmJulgamento($chamamento);

        if ($candidatas->isEmpty()) {
            return [];   // chamamento deserto ou já julgado
        }

        $data = $request->validate([
            // Ao menos uma: reprovar todas não pode ser efeito silencioso de um
            // clique em "Encerrar". Chamamento fracassado se resolve reprovando
            // as propostas uma a uma antes, na tela de cada uma.
            'vencedoras'   => ['required', 'array', 'min:1'],
            'vencedoras.*' => ['integer', Rule::in($candidatas->pluck('id')->all())],
        ], [
            'vencedoras.required' => 'Selecione a(s) proposta(s) vencedora(s) para adjudicar.',
            'vencedoras.*.in'     => 'Proposta que não está em julgamento neste chamamento.',
        ]);

        return $data['vencedoras'];
    }

    /**
     * Declara as vencedoras de um chamamento cuja Seleção já foi encerrada —
     * o caso dos que foram homologados antes de a adjudicação existir.
     */
    public function adjudicar(Request $request, Chamamento $chamamento): RedirectResponse
    {
        abort_unless($chamamento->temTramiteSelecao(), 422,
            'Dispensa/Inexigibilidade não passa por julgamento de propostas.');
        abort_unless($chamamento->selecaoConcluida(), 422,
            'A Seleção ainda não foi encerrada — a adjudicação acontece no encerramento.');
        abort_unless(auth()->user()->setor === 'ug', 403,
            'Apenas a Unidade Gestora declara o resultado do julgamento.');

        $vencedoras = $this->validarVencedoras($request, $chamamento);
        abort_if(empty($vencedoras), 422, 'Não há propostas em julgamento neste chamamento.');

        $this->adjudicarPropostas($chamamento, $vencedoras);

        return back()->with('success', count($vencedoras) === 1
            ? 'Proposta adjudicada. A Celebração já pode ser iniciada.'
            : count($vencedoras).' propostas adjudicadas. A Celebração já pode ser iniciada.');
    }

    public function concluir(Request $request, Chamamento $chamamento): RedirectResponse
    {
        $this->autorizarSetor($chamamento);
        abort_unless($chamamento->ultimaEtapaSelecao(), 422,
            'A Seleção só pode ser encerrada na última etapa.');

        $pendentes = $chamamento->pendenciasSelecao();
        abort_unless(empty($pendentes), 422,
            'Conclua antes de encerrar: ' . implode(', ', $pendentes) . '.');

        // Homologar sem dizer quem venceu era o que deixava a parceria sem
        // continuidade — ver adjudicarPropostas().
        $vencedoras = $this->validarVencedoras($request, $chamamento);

        $chamamento->selecaoTramitacoes()->create([
            'de_setor'    => $chamamento->selecao_setor,
            'para_setor'  => 'ug',
            'enviado_por' => auth()->id(),
            'enviado_em'  => now(),
            'status'      => 'concluido',
        ]);

        $chamamento->update([
            'selecao_setor'        => 'ug',
            'selecao_concluida_em' => now(),
            'status'               => 'encerrado',
            'data_resultado'       => $chamamento->data_resultado ?: now()->toDateString(),
        ]);

        $this->adjudicarPropostas($chamamento, $vencedoras);

        return redirect()->route('chamamentos.selecao', $chamamento)
            ->with('success', $vencedoras
                ? 'Seleção encerrada e homologada. A Celebração já pode ser iniciada.'
                : 'Seleção encerrada e homologada (sem propostas em julgamento).');
    }
}
