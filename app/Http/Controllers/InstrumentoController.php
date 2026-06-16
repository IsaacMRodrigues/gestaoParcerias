<?php

namespace App\Http\Controllers;

use App\Http\Requests\InstrumentoRequest;
use App\Models\Instrumento;
use App\Models\Proposta;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InstrumentoController extends Controller
{
    public function index(): View
    {
        $instrumentos = Instrumento::with(['proposta.osc', 'proposta.chamamento.programa'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('instrumentos.index', compact('instrumentos'));
    }

    public function create(Proposta $proposta): View
    {
        abort_unless($proposta->status === 'aprovada' && !$proposta->instrumento, 403);

        // Pré-popula a partir da proposta aprovada
        return view('instrumentos.create', compact('proposta'));
    }

    public function store(InstrumentoRequest $request, Proposta $proposta): RedirectResponse
    {
        $instrumento = $proposta->instrumento()->create($request->validated());

        return redirect()->route('instrumentos.show', $instrumento)
            ->with('success', 'Instrumento criado com sucesso.');
    }

    public function show(Instrumento $instrumento): View
    {
        $instrumento->load(['proposta.osc', 'proposta.chamamento.programa', 'aditivos']);

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

    public function minuta(Instrumento $instrumento): View
    {
        $instrumento->load(['proposta.osc', 'proposta.chamamento.programa', 'aditivos']);

        return view('instrumentos.minuta', compact('instrumento'));
    }

    public function assinar(Instrumento $instrumento): RedirectResponse
    {
        $instrumento->update([
            'status'          => 'assinado',
            'data_assinatura' => today(),
        ]);

        return redirect()->route('instrumentos.show', $instrumento)
            ->with('success', 'Instrumento marcado como assinado.');
    }

    public function publicar(Instrumento $instrumento): RedirectResponse
    {
        $instrumento->update([
            'publicado_doe'       => true,
            'data_publicacao_doe' => today(),
            'status'              => 'vigente',
        ]);

        return redirect()->route('instrumentos.show', $instrumento)
            ->with('success', 'Publicação registrada. Instrumento agora está vigente.');
    }
}
