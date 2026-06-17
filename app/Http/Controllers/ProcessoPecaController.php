<?php

namespace App\Http\Controllers;

use App\Models\Processo;
use App\Models\ProcessoPeca;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProcessoPecaController extends Controller
{
    public function edit(Processo $processo, ProcessoPeca $peca): View
    {
        abort_unless($peca->processo_id === $processo->id, 404);

        return view('processos.peca', compact('processo', 'peca'));
    }

    public function update(Request $request, Processo $processo, ProcessoPeca $peca): RedirectResponse
    {
        abort_unless($peca->processo_id === $processo->id, 404);

        $data = $request->validate([
            'conteudo' => ['nullable', 'string'],
        ]);

        $peca->update($data);

        return redirect()->route('processos.show', $processo)
            ->with('success', ProcessoPeca::TIPOS[$peca->tipo] . ' salvo.');
    }

    public function assinar(Processo $processo, ProcessoPeca $peca): RedirectResponse
    {
        abort_unless($peca->processo_id === $processo->id, 404);

        $peca->update([
            'assinado_por' => auth()->id(),
            'assinado_em'  => now(),
        ]);

        return redirect()->route('processos.show', $processo)
            ->with('success', ProcessoPeca::TIPOS[$peca->tipo] . ' assinado.');
    }
}
