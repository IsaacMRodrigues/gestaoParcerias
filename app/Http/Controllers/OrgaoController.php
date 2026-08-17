<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrgaoRequest;
use App\Models\Orgao;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrgaoController extends Controller
{
    public function index(): View
    {
        $orgaos = Orgao::orderBy('name')->paginate(15);

        return view('orgaos.index', compact('orgaos'));
    }

    public function create(): View
    {
        return view('orgaos.create');
    }

    public function store(OrgaoRequest $request): RedirectResponse
    {
        Orgao::create($request->validated());

        return redirect()->route('orgaos.index')
            ->with('success', 'Órgão/Secretaria cadastrado com sucesso.');
    }

    public function edit(Orgao $orgao): View
    {
        return view('orgaos.edit', compact('orgao'));
    }

    public function update(OrgaoRequest $request, Orgao $orgao): RedirectResponse
    {
        $orgao->update($request->validated());

        return redirect()->route('orgaos.index')
            ->with('success', 'Órgão/Secretaria atualizado com sucesso.');
    }

    public function destroy(Orgao $orgao): RedirectResponse
    {
        if ($bloqueio = $this->bloqueioDeExclusao($orgao)) {
            return $bloqueio;
        }

        $orgao->delete();

        return redirect()->route('orgaos.index')
            ->with('success', 'Órgão/Secretaria removido com sucesso.');
    }
}
