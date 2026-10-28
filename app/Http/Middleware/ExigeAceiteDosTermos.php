<?php

namespace App\Http\Middleware;

use App\Support\TermosDeUso;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Conta sem o aceite da versão vigente dos Termos de Uso: toda tela leva ao aceite, e depois volta para onde ia. */
class ExigeAceiteDosTermos
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !TermosDeUso::aceitou($user)
            && !$request->routeIs('termos.*', 'logout', 'senha.trocar', 'senha.trocar.salvar')) {
            return redirect()->guest(route('termos.aceitar'));
        }

        return $next($request);
    }
}
