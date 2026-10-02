<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

/**
 * A conta de quem é da equipe da OSC, nas mãos da própria pessoa (nome, telefone e senha).
 * O e-mail de acesso é do responsável legal.
 */
class PerfilOscController extends Controller
{
    public function edit(): View|RedirectResponse
    {
        // Servidor tem a tela de perfil dele, na área interna.
        if (auth()->user()->temAcessoInterno()) {
            return redirect()->route('profile.edit');
        }

        return view('portal.perfil', ['usuario' => auth()->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $usuario = $request->user();
        abort_if($usuario->temAcessoInterno(), 403, 'Use o seu perfil na área interna.');

        $data = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ], [
            'name.required' => 'Informe o seu nome.',
        ]);

        $usuario->update($data);

        return back()->with('success', 'Dados atualizados.');
    }

    /** Troca de senha, pedindo a atual. */
    public function senha(Request $request): RedirectResponse
    {
        abort_if($request->user()->temAcessoInterno(), 403, 'Use o seu perfil na área interna.');

        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'current_password.required'     => 'Informe a senha atual.',
            'current_password.current_password' => 'A senha atual não confere.',
            'password.required'             => 'Informe a nova senha.',
        ]);

        $request->user()->update(['password' => Hash::make($data['password'])]);

        return back()->with('success', 'Senha alterada.');
    }
}
