<?php

namespace App\Http\Controllers;

use App\Models\Processo;
use App\Models\ProcessoPeca;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProcessoPecaController extends Controller
{
    /**
     * A peça só pode ser preenchida/assinada pelo setor responsável,
     * enquanto o processo estiver com esse setor e ainda em andamento.
     */
    private function podeEditar(Processo $processo, ProcessoPeca $peca): bool
    {
        return auth()->user()->setor === $peca->setorResponsavel()
            && $processo->setor_atual === $peca->setorResponsavel()
            && in_array($processo->status, ['em_planejamento', 'em_tramite']);
    }

    private function autorizar(Processo $processo, ProcessoPeca $peca): void
    {
        abort_unless($peca->processo_id === $processo->id, 404);
        abort_unless($this->podeEditar($processo, $peca), 403,
            'Esta peça é de responsabilidade do setor '
            . (Processo::SETORES[$peca->setorResponsavel()] ?? $peca->setorResponsavel())
            . ' e só pode ser editada quando o processo estiver com ele.');
    }

    public function edit(Processo $processo, ProcessoPeca $peca): View
    {
        abort_unless($peca->processo_id === $processo->id, 404);

        $podeEditar = $this->podeEditar($processo, $peca) && !$peca->assinado();

        return view('processos.peca', compact('processo', 'peca', 'podeEditar'));
    }

    public function update(Request $request, Processo $processo, ProcessoPeca $peca): RedirectResponse
    {
        $this->autorizar($processo, $peca);
        abort_if($peca->assinado(), 403, 'Peça já assinada não pode ser alterada.');

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
        abort_if($peca->assinado(), 403, 'Peça já assinada.');

        $peca->update([
            'assinado_por' => auth()->id(),
            'assinado_em'  => now(),
        ]);

        return redirect()->route('processos.show', $processo)
            ->with('success', ProcessoPeca::TIPOS[$peca->tipo] . ' assinado.');
    }
}
