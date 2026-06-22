<?php

namespace App\Http\Controllers;

use App\Models\Processo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TramitacaoController extends Controller
{
    /**
     * Garante que o usuário logado é do setor que está com o processo agora.
     */
    private function autorizarSetor(Processo $processo): void
    {
        abort_unless(auth()->user()->setor === $processo->setor_atual, 403,
            'Apenas o setor que está com o processo pode movimentá-lo.');
    }

    /**
     * O setor de destino registra o recebimento antes de atuar.
     */
    public function receber(Processo $processo): RedirectResponse
    {
        $this->autorizarSetor($processo);

        $atual = $processo->tramitacaoAtual();
        abort_unless($atual, 404);

        $atual->update([
            'recebido_por' => auth()->id(),
            'recebido_em'  => now(),
            'status'       => 'recebido',
        ]);

        return back()->with('success', 'Recebimento registrado.');
    }

    /**
     * Avança para a próxima etapa do fluxo (envia para o próximo setor).
     */
    public function avancar(Request $request, Processo $processo): RedirectResponse
    {
        $this->autorizarSetor($processo);

        abort_unless($processo->podeAvancar(), 422, 'Não é possível avançar a partir desta etapa.');

        // recebimento pendente precisa ser registrado antes
        $atual = $processo->tramitacaoAtual();
        abort_if($atual && is_null($atual->recebido_em), 422, 'Registre o recebimento antes de encaminhar.');

        $data = $request->validate(['parecer' => ['nullable', 'string']]);

        $proxEtapa = $processo->etapa + 1;
        $proxSetor = Processo::ETAPAS[$proxEtapa]['setor'];

        $processo->tramitacoes()->create([
            'de_setor'    => $processo->setor_atual,
            'para_setor'  => $proxSetor,
            'enviado_por' => auth()->id(),
            'enviado_em'  => now(),
            'parecer'     => $data['parecer'] ?? null,
            'status'      => 'enviado',
        ]);

        $processo->update([
            'etapa'       => $proxEtapa,
            'setor_atual' => $proxSetor,
            'status'      => 'em_tramite',
        ]);

        return redirect()->route('processos.show', $processo)
            ->with('success', 'Processo encaminhado para ' . Processo::SETORES[$proxSetor] . '.');
    }

    /**
     * Devolve para a etapa anterior (quando há pendência a corrigir).
     */
    public function devolver(Request $request, Processo $processo): RedirectResponse
    {
        $this->autorizarSetor($processo);
        abort_if($processo->etapa === 0, 422, 'Não há etapa anterior para devolver.');

        $data = $request->validate(['parecer' => ['required', 'string']], [
            'parecer.required' => 'Informe o motivo da devolução.',
        ]);

        $etapaAnterior = $processo->etapa - 1;
        $setorAnterior = Processo::ETAPAS[$etapaAnterior]['setor'];

        $processo->tramitacoes()->create([
            'de_setor'    => $processo->setor_atual,
            'para_setor'  => $setorAnterior,
            'enviado_por' => auth()->id(),
            'enviado_em'  => now(),
            'parecer'     => $data['parecer'],
            'status'      => 'devolvido',
        ]);

        $processo->update([
            'etapa'       => $etapaAnterior,
            'setor_atual' => $setorAnterior,
            'status'      => 'em_tramite',
        ]);

        return redirect()->route('processos.show', $processo)
            ->with('success', 'Processo devolvido para ' . Processo::SETORES[$setorAnterior] . '.');
    }

    /**
     * Conclui o trâmite na última etapa (SCP publica — trâmite externo).
     */
    public function concluir(Processo $processo): RedirectResponse
    {
        $this->autorizarSetor($processo);
        abort_unless($processo->ultimaEtapa(), 422, 'O processo ainda não chegou à etapa final.');

        $atual = $processo->tramitacaoAtual();
        abort_if($atual && is_null($atual->recebido_em), 422, 'Registre o recebimento antes de concluir.');

        $processo->update(['status' => 'concluido']);

        return redirect()->route('processos.show', $processo)
            ->with('success', 'Processo concluído — encaminhado para publicação no site oficial.');
    }
}
