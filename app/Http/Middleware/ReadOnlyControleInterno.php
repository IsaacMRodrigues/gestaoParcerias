<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controle Interno tem acesso de leitura a tudo, mas não pode alterar dados.
 * Bloqueia qualquer método de escrita (POST/PUT/PATCH/DELETE), exceto no próprio perfil.
 */
class ReadOnlyControleInterno
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->somenteLeitura()
            && !$request->isMethodSafe()
            && !$request->routeIs('profile.*')
            && !$request->routeIs('logout')
        ) {
            abort(403, 'Seu perfil (auditoria) tem acesso somente de leitura.');
        }

        return $next($request);
    }
}
