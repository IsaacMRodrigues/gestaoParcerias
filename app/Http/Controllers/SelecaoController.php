<?php

namespace App\Http\Controllers;

use App\Models\Chamamento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
    public function concluir(Chamamento $chamamento): RedirectResponse
    {
        $this->autorizarSetor($chamamento);
        abort_unless($chamamento->ultimaEtapaSelecao(), 422,
            'A Seleção só pode ser encerrada na última etapa.');

        $pendentes = $chamamento->pendenciasSelecao();
        abort_unless(empty($pendentes), 422,
            'Conclua antes de encerrar: ' . implode(', ', $pendentes) . '.');

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

        return redirect()->route('chamamentos.selecao', $chamamento)
            ->with('success', 'Seleção encerrada e homologada. O chamamento segue para a Celebração.');
    }
}
