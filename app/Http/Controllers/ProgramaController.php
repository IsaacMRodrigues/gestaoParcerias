<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProgramaRequest;
use App\Models\Orgao;
use App\Models\Programa;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProgramaController extends Controller
{
    public function index(): View
    {
        $programas = Programa::with('orgao')
            ->withCount('chamamentos')
            ->orderBy('name')
            ->paginate(15);

        return view('programas.index', compact('programas'));
    }

    public function create(): View
    {
        $orgaos = Orgao::where('status', true)->orderBy('name')->get();

        return view('programas.create', compact('orgaos'));
    }

    public function store(ProgramaRequest $request): RedirectResponse
    {
        Programa::create($request->validated());

        return redirect()->route('programas.index')
            ->with('success', 'Programa cadastrado com sucesso.');
    }

    public function show(Programa $programa): View
    {
        $programa->load('orgao', 'chamamentos');

        return view('programas.show', compact('programa'));
    }

    public function edit(Programa $programa): View
    {
        $orgaos = Orgao::where('status', true)->orderBy('name')->get();

        return view('programas.edit', compact('programa', 'orgaos'));
    }

    public function update(ProgramaRequest $request, Programa $programa): RedirectResponse
    {
        $programa->update($request->validated());

        return redirect()->route('programas.index')
            ->with('success', 'Programa atualizado com sucesso.');
    }

    public function destroy(Programa $programa): RedirectResponse
    {
        $programa->delete();

        return redirect()->route('programas.index')
            ->with('success', 'Programa removido com sucesso.');
    }
}
