<?php

namespace App\Http\Controllers;

use App\Http\Requests\InstrumentoRequest;
use App\Models\Instrumento;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** Instrumentos: nascem na conclusão da Celebração (Proposta::criarInstrumento); aqui se consultam e corrigem. */
class InstrumentoController extends Controller
{
    public function index(): View
    {
        $instrumentos = Instrumento::with(['proposta.osc', 'proposta.chamamento.programa.orgao'])
            ->visiveisPara(auth()->user())
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('instrumentos.index', compact('instrumentos'));
    }

    public function show(Instrumento $instrumento): View
    {
        $instrumento->load(['proposta.osc', 'proposta.chamamento.programa.orgao', 'aditivos']);

        return view('instrumentos.show', compact('instrumento'));
    }

    public function edit(Instrumento $instrumento): View
    {
        return view('instrumentos.edit', compact('instrumento'));
    }

    public function update(InstrumentoRequest $request, Instrumento $instrumento): RedirectResponse
    {
        $instrumento->update($request->validated());

        return redirect()->route('instrumentos.show', $instrumento)
            ->with('success', 'Instrumento atualizado com sucesso.');
    }
}
