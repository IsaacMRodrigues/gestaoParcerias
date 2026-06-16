<?php

namespace App\Http\Controllers;

use App\Models\Diligencia;
use App\Models\Proposta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiligenciaController extends Controller
{
    public function show(Proposta $proposta, Diligencia $diligencia): View
    {
        return view('diligencias.show', compact('proposta', 'diligencia'));
    }

    public function responder(Request $request, Proposta $proposta, Diligencia $diligencia): RedirectResponse
    {
        $request->validate([
            'resposta' => ['required', 'string'],
        ]);

        $diligencia->update([
            'resposta'      => $request->resposta,
            'respondida_em' => now(),
            'status'        => 'respondida',
        ]);

        // Se todas as diligências da proposta foram respondidas, volta para em_analise
        $pendentes = $proposta->diligencias()->where('status', 'pendente')->count();
        if ($pendentes === 0 && $proposta->status === 'em_negociacao') {
            $proposta->update(['status' => 'em_analise']);
        }

        return redirect()->route('propostas.show', $proposta)
            ->with('success', 'Resposta à diligência registrada.');
    }
}
