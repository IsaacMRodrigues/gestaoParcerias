<?php

namespace App\Http\Controllers;

use App\Http\Requests\EtapaRequest;
use App\Models\Etapa;
use App\Models\Meta;
use App\Models\Proposta;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EtapaController extends Controller
{
    public function create(Proposta $proposta, Meta $meta): View
    {
        $numero = $meta->etapas()->max('numero') + 1;

        return view('etapas.create', compact('proposta', 'meta', 'numero'));
    }

    public function store(EtapaRequest $request, Proposta $proposta, Meta $meta): RedirectResponse
    {
        $meta->etapas()->create($request->validated());

        return redirect()->route('propostas.show', $proposta)
            ->with('success', 'Etapa adicionada à meta.');
    }

    public function edit(Proposta $proposta, Meta $meta, Etapa $etapa): View
    {
        return view('etapas.edit', compact('proposta', 'meta', 'etapa'));
    }

    public function update(EtapaRequest $request, Proposta $proposta, Meta $meta, Etapa $etapa): RedirectResponse
    {
        $etapa->update($request->validated());

        return redirect()->route('propostas.show', $proposta)
            ->with('success', 'Etapa atualizada com sucesso.');
    }

    public function destroy(Proposta $proposta, Meta $meta, Etapa $etapa): RedirectResponse
    {
        $etapa->delete();

        return redirect()->route('propostas.show', $proposta)
            ->with('success', 'Etapa removida.');
    }
}
