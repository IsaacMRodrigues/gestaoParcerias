<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Com users.deve_trocar_senha acesa (senha definida por outra pessoa), toda tela leva à troca. */
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
