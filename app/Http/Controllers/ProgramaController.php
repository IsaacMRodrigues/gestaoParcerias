<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProgramaRequest;
use App\Models\Orgao;
use App\Models\Programa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgramaController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->only(['busca', 'orgao_id', 'tipo', 'status']);

        $programas = Programa::with('orgao')
            ->withCount('chamamentos')
            ->when($filtros['busca'] ?? null, function ($q, $busca) {
                $q->where(function ($sub) use ($busca) {
                    $sub->where('name', 'like', "%{$busca}%")
                        ->orWhere('sigla', 'like', "%{$busca}%");
                });
            })
            ->when($filtros['orgao_id'] ?? null, fn ($q, $v) => $q->where('orgao_id', $v))
            ->when($filtros['tipo'] ?? null, fn ($q, $v) => $q->where('tipo', $v))
            ->when($filtros['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $orgaos = Orgao::orderBy('name')->get();

        return view('programas.index', compact('programas', 'orgaos', 'filtros'));
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
        if ($bloqueio = $this->bloqueioDeExclusao($programa)) {
            return $bloqueio;
        }

        $programa->delete();

        return redirect()->route('programas.index')
            ->with('success', 'Programa removido com sucesso.');
    }
}
