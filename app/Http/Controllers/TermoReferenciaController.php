<?php

namespace App\Http\Controllers;

use App\Models\Processo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TermoReferenciaController extends Controller
{
    public function edit(Processo $processo): View
    {
        $processo->load('termoReferencia');

        return view('processos.termo', compact('processo'));
    }

    public function update(Request $request, Processo $processo): RedirectResponse
    {
        $data = $request->validate([
            'problema_identificado'     => ['nullable', 'string', 'max:255'],
            'publico_alvo'              => ['nullable', 'string', 'max:255'],
            'qtd_beneficiarios'         => ['nullable', 'integer', 'min:0'],
            'area_tematica'             => ['nullable', Rule::in(array_keys(Processo::AREAS_TEMATICAS))],
            'justificativa_necessidade' => ['nullable', 'string'],
            'indicadores'               => ['nullable', 'string'],
            'programa_governo'          => ['nullable', 'string', 'max:255'],
            'acao_governamental'        => ['nullable', 'string', 'max:255'],
            'dotacao_orcamentaria'      => ['nullable', 'string', 'max:255'],
            'objeto_resumido'           => ['nullable', 'string'],
            'vigencia_prevista'         => ['nullable', 'string', 'max:255'],
            'local_execucao'            => ['nullable', 'string', 'max:255'],
            'objetivo_geral'            => ['nullable', 'string'],
            'objetivos_especificos'     => ['nullable', 'string'],
            'justificativa'             => ['nullable', 'string'],
            'valor_total'               => ['nullable', 'numeric', 'min:0'],
            'fonte_recurso'             => ['nullable', 'string', 'max:255'],
        ]);

        $processo->termoReferencia()->update($data);

        return redirect()->route('processos.show', $processo)
            ->with('success', 'Termo de Referência salvo.');
    }

    public function assinar(Processo $processo): RedirectResponse
    {
        $processo->termoReferencia()->update([
            'assinado_por' => auth()->id(),
            'assinado_em'  => now(),
        ]);

        return redirect()->route('processos.show', $processo)
            ->with('success', 'Termo de Referência assinado.');
    }
}
