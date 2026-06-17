<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChamamentoRequest;
use App\Models\Chamamento;
use App\Models\Peca;
use App\Models\Programa;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ChamamentoController extends Controller
{
    public function index(Programa $programa): View
    {
        $chamamentos = $programa->chamamentos()
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('chamamentos.index', compact('programa', 'chamamentos'));
    }

    public function create(Programa $programa): View
    {
        return view('chamamentos.create', compact('programa'));
    }

    public function store(ChamamentoRequest $request, Programa $programa): RedirectResponse
    {
        $programa->chamamentos()->create($request->validated());

        return redirect()->route('programas.chamamentos.index', $programa)
            ->with('success', 'Chamamento cadastrado com sucesso.');
    }

    public function edit(Programa $programa, Chamamento $chamamento): View
    {
        return view('chamamentos.edit', compact('programa', 'chamamento'));
    }

    public function update(ChamamentoRequest $request, Programa $programa, Chamamento $chamamento): RedirectResponse
    {
        $chamamento->update($request->validated());

        return redirect()->route('programas.chamamentos.index', $programa)
            ->with('success', 'Chamamento atualizado com sucesso.');
    }

    public function destroy(Programa $programa, Chamamento $chamamento): RedirectResponse
    {
        $chamamento->delete();

        return redirect()->route('programas.chamamentos.index', $programa)
            ->with('success', 'Chamamento removido com sucesso.');
    }

    /**
     * 2.2 Seleção e Celebração — checklist documental do chamamento.
     */
    public function selecao(Chamamento $chamamento): View
    {
        $categoria = $chamamento->categoriaPecas();
        Peca::sincronizar($chamamento, $categoria);

        $chamamento->load(['programa.orgao', 'pecas.assinante']);
        $pecas = $chamamento->pecas;
        $progresso = Peca::progresso($pecas);

        return view('chamamentos.selecao', compact('chamamento', 'pecas', 'categoria', 'progresso'));
    }
}
