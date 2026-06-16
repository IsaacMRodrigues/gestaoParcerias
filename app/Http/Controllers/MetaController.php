<?php

namespace App\Http\Controllers;

use App\Http\Requests\MetaRequest;
use App\Models\Meta;
use App\Models\Proposta;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MetaController extends Controller
{
    public function create(Proposta $proposta): View
    {
        $numero = $proposta->metas()->max('numero') + 1;

        return view('metas.create', compact('proposta', 'numero'));
    }

    public function store(MetaRequest $request, Proposta $proposta): RedirectResponse
    {
        $proposta->metas()->create($request->validated());

        return redirect()->route('propostas.show', $proposta)
            ->with('success', 'Meta adicionada ao Plano de Trabalho.');
    }

    public function edit(Proposta $proposta, Meta $meta): View
    {
        return view('metas.edit', compact('proposta', 'meta'));
    }

    public function update(MetaRequest $request, Proposta $proposta, Meta $meta): RedirectResponse
    {
        $meta->update($request->validated());

        return redirect()->route('propostas.show', $proposta)
            ->with('success', 'Meta atualizada com sucesso.');
    }

    public function destroy(Proposta $proposta, Meta $meta): RedirectResponse
    {
        $meta->delete();

        return redirect()->route('propostas.show', $proposta)
            ->with('success', 'Meta removida.');
    }
}
