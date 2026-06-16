<?php

namespace App\Http\Controllers;

use App\Http\Requests\PropostaRequest;
use App\Models\Chamamento;
use App\Models\Osc;
use App\Models\Proposta;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PropostaController extends Controller
{
    public function index(): View
    {
        $propostas = Proposta::with(['chamamento.programa', 'osc'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('propostas.index', compact('propostas'));
    }

    public function create(): View
    {
        $chamamentos = Chamamento::with('programa')
            ->whereIn('status', ['publicado', 'em_inscricao'])
            ->orderBy('titulo')
            ->get();
        $oscs = Osc::where('status', true)->orderBy('name')->get();

        return view('propostas.create', compact('chamamentos', 'oscs'));
    }

    public function store(PropostaRequest $request): RedirectResponse
    {
        $proposta = Proposta::create($request->validated());

        return redirect()->route('propostas.show', $proposta)
            ->with('success', 'Proposta criada. Agora adicione as metas do Plano de Trabalho.');
    }

    public function show(Proposta $proposta): View
    {
        $proposta->load(['chamamento.programa', 'osc', 'metas.etapas']);

        return view('propostas.show', compact('proposta'));
    }

    public function edit(Proposta $proposta): View
    {
        $chamamentos = Chamamento::with('programa')
            ->whereIn('status', ['publicado', 'em_inscricao'])
            ->orWhere('id', $proposta->chamamento_id)
            ->orderBy('titulo')
            ->get();
        $oscs = Osc::where('status', true)->orderBy('name')->get();

        return view('propostas.edit', compact('proposta', 'chamamentos', 'oscs'));
    }

    public function update(PropostaRequest $request, Proposta $proposta): RedirectResponse
    {
        $proposta->update($request->validated());

        return redirect()->route('propostas.show', $proposta)
            ->with('success', 'Proposta atualizada com sucesso.');
    }

    public function destroy(Proposta $proposta): RedirectResponse
    {
        $proposta->delete();

        return redirect()->route('propostas.index')
            ->with('success', 'Proposta removida com sucesso.');
    }

    public function submeter(Proposta $proposta): RedirectResponse
    {
        $proposta->update([
            'status'       => 'submetida',
            'submitted_at' => now(),
        ]);

        return redirect()->route('propostas.show', $proposta)
            ->with('success', 'Proposta submetida com sucesso.');
    }
}
