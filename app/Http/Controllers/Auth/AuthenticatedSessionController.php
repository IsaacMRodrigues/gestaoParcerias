<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Mesma régua de EnsureIsStaff: por papel específico, quebrou no dia em
        // que a OSC ganhou equipe (membro_osc não caía aqui e entrava direto no
        // dashboard interno, para ser barrado e devolvido ao portal um passo
        // depois, com um aviso de "área restrita" que não fazia sentido para
        // quem acabou de ganhar acesso).
        if (! auth()->user()->temAcessoInterno()) {
            return redirect()->intended(route('portal.index'));
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
