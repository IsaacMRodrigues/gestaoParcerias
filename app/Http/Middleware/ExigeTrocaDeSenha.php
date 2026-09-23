<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Senha definida por outra pessoa se troca antes de qualquer outra coisa.
 *
 * A marca `users.deve_trocar_senha` vem de quem definiu a senha por alguém:
 * o suporte (senha provisória), o administrador, a chefia do setor e o
 * responsável legal da OSC ao cadastrar a equipe. Enquanto ela estiver
 * acesa, toda tela leva à troca — só a própria troca e o sair passam.
 */
class ExigeTrocaDeSenha
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->deve_trocar_senha && !$request->routeIs('senha.trocar', 'senha.trocar.salvar', 'logout')) {
            return redirect()->route('senha.trocar');
        }

        return $next($request);
    }
}
