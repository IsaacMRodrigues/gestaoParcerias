<?php

namespace App\Http\Controllers;

use App\Models\Processo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TermoReferenciaController extends Controller
{
    /**
     * O Termo de Referência é de responsabilidade da UG, enquanto o processo
     * estiver com a UG e em andamento.
     */
    private function podeEditar(Processo $processo): bool
    {
        return auth()->user()->setor === 'ug'
            && $processo->setor_atual === 'ug'
            && $processo->etapa === 0   // o TR é preenchido na 1ª etapa (junto do Ofício)
            && in_array($processo->status, ['em_planejamento', 'em_tramite']);
    }

    private function autorizar(Processo $processo): void
    {
        abort_unless($this->podeEditar($processo), 403,
            'O Termo de Referência é de responsabilidade da Unidade Gestora e só pode ser editado quando o processo estiver com ela.');
    }

    public function edit(Processo $processo): View
    {
        $processo->load('termoReferencia');
        $podeEditar = $this->podeEditar($processo) && !$processo->termoReferencia?->assinado();

        return view('processos.termo', compact('processo', 'podeEditar'));
    }

    public function update(Request $request, Processo $processo): RedirectResponse
    {
        $this->autorizar($processo);
        abort_if($processo->termoReferencia?->assinado(), 403, 'Termo já assinado não pode ser alterado.');

        $data = $request->validate([
            'descricao_realidade'   => ['nullable', 'string'],
            'justificativa'         => ['nullable', 'string'],
            'objeto'                => ['nullable', 'string'],
            'objetivos_especificos' => ['nullable', 'string'],
            'valor_total'           => ['nullable', 'numeric', 'min:0'],
            'dotacao'               => ['nullable', 'string', 'max:255'],
            'ficha'                 => ['nullable', 'string', 'max:255'],
            'fonte'                 => ['nullable', 'string', 'max:255'],
            'prazo_meses'           => ['nullable', 'integer', 'min:0'],
        ]);

        $processo->termoReferencia()->update($data);

        return redirect()->route('processos.show', $processo)
            ->with('success', 'Termo de Referência salvo.');
    }

    public function assinar(Processo $processo): RedirectResponse
    {
        $this->autorizar($processo);
        abort_if($processo->termoReferencia?->assinado(), 403, 'Termo já assinado.');

        $processo->termoReferencia()->update([
            'assinado_por' => auth()->id(),
            'assinado_em'  => now(),
        ]);

        return redirect()->route('processos.show', $processo)
            ->with('success', 'Termo de Referência assinado.');
    }
}
