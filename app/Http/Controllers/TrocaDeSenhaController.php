<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

/**
 * A troca de senha obrigatória do primeiro acesso — ver ExigeTrocaDeSenha.
 *
 * Não pede a senha atual: a pessoa acabou de entrar com ela. Pede, sim, que a
 * nova seja outra — repetir a provisória manteria a senha que alguém conhece.
 */
class TrocaDeSenhaController extends Controller
{
    public function edit(Request $request): View|RedirectResponse
    {
        if (!$request->user()->deve_trocar_senha) {
            return $this->inicio($request);
        }

        return view('auth.trocar-senha');
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $dados = $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'password.required'  => 'Defina a sua nova senha.',
            'password.confirmed' => 'A confirmação não confere com a nova senha.',
        ]);

        if (Hash::check($dados['password'], $user->password)) {
            return back()->withErrors(['password' => 'Escolha uma senha diferente da que você recebeu.']);
        }

        $user->forceFill(['password' => $dados['password'], 'deve_trocar_senha' => false])->save();

        return $this->inicio($request)->with('success', 'Senha definida. A partir de agora, só você a conhece.');
    }

    /** Para onde o login levaria — a mesma régua do AuthenticatedSessionController. */
    private function inicio(Request $request): RedirectResponse
    {
        return $request->user()->temAcessoInterno()
            ? redirect()->route('dashboard')
            : redirect()->route('portal.index');
    }
}
