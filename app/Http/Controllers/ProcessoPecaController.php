<?php

namespace App\Http\Controllers;

use App\Models\Processo;
use App\Models\ProcessoPeca;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProcessoPecaController extends Controller
{
    private function autorizar(Processo $processo, ProcessoPeca $peca): void
    {
        abort_unless($peca->processo_id === $processo->id, 404);
        abort_unless($peca->podeEditar($processo, auth()->user()), 403,
            'Esta peça é de responsabilidade do setor '
            . (Processo::SETORES[$peca->setorResponsavel()] ?? $peca->setorResponsavel())
            . ' e só pode ser editada na etapa correspondente do fluxo.');
    }

    public function edit(Processo $processo, ProcessoPeca $peca): View
    {
        abort_unless($peca->processo_id === $processo->id, 404);

        $podeEditar = $peca->podeEditar($processo, auth()->user());

        return view('processos.peca', compact('processo', 'peca', 'podeEditar'));
    }

    public function update(Request $request, Processo $processo, ProcessoPeca $peca): RedirectResponse
    {
        $this->autorizar($processo, $peca);

        $data = $request->validate([
            'conteudo' => ['nullable', 'string'],
        ]);

        $peca->update($data);

        return redirect()->route('processos.show', $processo)
            ->with('success', ProcessoPeca::TIPOS[$peca->tipo] . ' salvo.');
    }

    public function assinar(Processo $processo, ProcessoPeca $peca): RedirectResponse
    {
        $this->autorizar($processo, $peca);

        $peca->update([
            'assinado_por' => auth()->id(),
            'assinado_em'  => now(),
        ]);

        return redirect()->route('processos.show', $processo)
            ->with('success', ProcessoPeca::TIPOS[$peca->tipo] . ' assinado.');
    }
}
