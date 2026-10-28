<?php

namespace App\Http\Controllers;

use App\Support\TermosDeUso;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Os Termos de Uso: a página pública e o aceite de quem entra sem ter aceitado a versão vigente. */
class TermosDeUsoController extends Controller
{
    public function index(): View
    {
        return view('termos.index');
    }

    public function aceitar(Request $request): View|RedirectResponse
    {
        return TermosDeUso::aceitou($request->user()) ? $this->inicio($request) : view('termos.aceitar');
    }

    public function salvar(Request $request): RedirectResponse
    {
        $request->validate(['aceite' => ['accepted']], ['aceite.accepted' => 'Para continuar, marque que leu e aceita os Termos de Uso.']);

        $user = $request->user();
        if (!TermosDeUso::aceitou($user)) {
            TermosDeUso::registrarAceite($user, $user->termos_versao === null ? 'primeiro_acesso' : 'nova_versao');
        }

        return redirect()->intended($this->inicio($request)->getTargetUrl());
    }

    /** Para onde o login levaria — a mesma régua do TrocaDeSenhaController. */
    private function inicio(Request $request): RedirectResponse
    {
        return $request->user()->temAcessoInterno()
            ? redirect()->route('dashboard')
            : redirect()->route('portal.index');
    }
}
