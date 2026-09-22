<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

/**
 * A conta de quem é da equipe da OSC, nas mãos da própria pessoa.
 *
 * A tela de perfil do sistema (/profile) vive atrás do middleware `staff`: é
 * do servidor. Quem entra pelo portal não tinha para onde ir — e como quem
 * cadastra o integrante é o responsável legal, que define a senha inicial,
 * essa senha nascia conhecida por outra pessoa e sem como ser trocada.
 *
 * O e-mail fica de fora: é o endereço de acesso que o responsável legal
 * cadastrou e por onde a organização responde. Trocá-lo é caso de falar com
 * quem administra a equipe.
 */
class PerfilOscController extends Controller
{
    public function edit(): View
    {
        return view('portal.perfil', ['usuario' => auth()->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $usuario = $request->user();

        $data = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ], [
            'name.required' => 'Informe o seu nome.',
        ]);

        $usuario->update($data);

        return back()->with('success', 'Dados atualizados.');
    }

    /**
     * Troca de senha. Pede a senha atual porque é o que impede que um
     * computador deixado aberto vire uma conta tomada.
     */
    public function senha(Request $request): RedirectResponse
    {
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
