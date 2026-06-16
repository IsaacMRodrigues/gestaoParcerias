<?php

namespace App\Http\Controllers;

use App\Http\Requests\AditivoRequest;
use App\Models\Aditivo;
use App\Models\Instrumento;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AditivoController extends Controller
{
    public function create(Instrumento $instrumento): View
    {
        $numero = $instrumento->aditivos()->max('numero') + 1;

        return view('aditivos.create', compact('instrumento', 'numero'));
    }

    public function store(AditivoRequest $request, Instrumento $instrumento): RedirectResponse
    {
        $instrumento->aditivos()->create($request->validated());

        return redirect()->route('instrumentos.show', $instrumento)
            ->with('success', 'Termo aditivo cadastrado com sucesso.');
    }

    public function edit(Instrumento $instrumento, Aditivo $aditivo): View
    {
        return view('aditivos.edit', compact('instrumento', 'aditivo'));
    }

    public function update(AditivoRequest $request, Instrumento $instrumento, Aditivo $aditivo): RedirectResponse
    {
        $aditivo->update($request->validated());

        return redirect()->route('instrumentos.show', $instrumento)
            ->with('success', 'Termo aditivo atualizado.');
    }

    public function destroy(Instrumento $instrumento, Aditivo $aditivo): RedirectResponse
    {
        $aditivo->delete();

        return redirect()->route('instrumentos.show', $instrumento)
            ->with('success', 'Termo aditivo removido.');
    }
}
