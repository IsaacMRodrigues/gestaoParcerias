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

        $podeEditar = $peca->podeEditarConteudo($processo, auth()->user());
        $podeAssinar = $peca->podeAssinar($processo, auth()->user());

        // QR Code da validação (apontando para a página pública)
        $qrValidacao = null;
        if ($peca->assinado() && $peca->codigo_validacao) {
            $qrValidacao = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                ->size(110)->margin(0)
                ->generate(route('validacao.mostrar', $peca->codigo_validacao));
        }

        return view('processos.peca', compact('processo', 'peca', 'podeEditar', 'podeAssinar', 'qrValidacao'));
    }

    public function update(Request $request, Processo $processo, ProcessoPeca $peca): RedirectResponse
    {
        abort_unless($peca->processo_id === $processo->id, 404);
        abort_unless($peca->podeEditarConteudo($processo, auth()->user()), 403,
            'Esta peça é de responsabilidade do setor '
            . (Processo::SETORES[$peca->setorResponsavel()] ?? $peca->setorResponsavel())
            . ' e só pode ser editada na etapa correspondente.');

        $peca->update($request->validate(['conteudo' => ['nullable', 'string']]));

        return redirect()->route('processos.show', $processo)
            ->with('success', ProcessoPeca::TIPOS[$peca->tipo] . ' salvo.');
    }

    public function assinar(Processo $processo, ProcessoPeca $peca): RedirectResponse
    {
        abort_unless($peca->processo_id === $processo->id, 404);
        abort_unless($peca->podeAssinar($processo, auth()->user()), 403,
            'Você não pode assinar esta peça nesta etapa.');

        $peca->update([
            'assinado_por'     => auth()->id(),
            'assinado_em'      => now(),
            'codigo_validacao' => $peca->codigo_validacao ?: ProcessoPeca::gerarCodigoValidacao(),
        ]);

        return redirect()->route('processos.show', $processo)
            ->with('success', ProcessoPeca::TIPOS[$peca->tipo] . ' assinado.');
    }
}
