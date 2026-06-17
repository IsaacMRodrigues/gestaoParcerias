<?php

namespace App\Http\Controllers;

use App\Models\Processo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TramitacaoController extends Controller
{
    /**
     * Envia o processo para outro setor.
     */
    public function enviar(Request $request, Processo $processo): RedirectResponse
    {
        $data = $request->validate([
            'para_setor' => ['required', Rule::in(array_keys(Processo::SETORES))],
            'parecer'    => ['nullable', 'string'],
        ]);

        // Marca a tramitação atual (se houver) como recebida/concluída com o parecer
        $atual = $processo->tramitacaoAtual();
        if ($atual && is_null($atual->recebido_em)) {
            $atual->update([
                'recebido_por' => auth()->id(),
                'recebido_em'  => now(),
                'status'       => 'recebido',
            ]);
        }

        $processo->tramitacoes()->create([
            'de_setor'    => $processo->setor_atual,
            'para_setor'  => $data['para_setor'],
            'enviado_por' => auth()->id(),
            'enviado_em'  => now(),
            'parecer'     => $data['parecer'] ?? null,
            'status'      => 'enviado',
        ]);

        $processo->update([
            'setor_atual' => $data['para_setor'],
            'status'      => 'em_tramite',
        ]);

        return redirect()->route('processos.show', $processo)
            ->with('success', 'Processo enviado para ' . Processo::SETORES[$data['para_setor']] . '.');
    }

    /**
     * Setor de destino registra o recebimento.
     */
    public function receber(Processo $processo): RedirectResponse
    {
        $atual = $processo->tramitacaoAtual();
        abort_unless($atual, 404);

        $atual->update([
            'recebido_por' => auth()->id(),
            'recebido_em'  => now(),
            'status'       => 'recebido',
        ]);

        return redirect()->route('processos.show', $processo)
            ->with('success', 'Recebimento registrado.');
    }

    /**
     * UG abre o processo (conclui o planejamento) quando estiver apto.
     */
    public function abrir(Processo $processo): RedirectResponse
    {
        abort_unless($processo->estaApto(), 422, 'O planejamento ainda possui pendências.');

        $processo->update(['status' => 'apto']);

        return redirect()->route('processos.show', $processo)
            ->with('success', 'Processo apto para abertura — pronto para gerar o chamamento/edital.');
    }
}
