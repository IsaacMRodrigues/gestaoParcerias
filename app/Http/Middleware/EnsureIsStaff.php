<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->hasRole('representante_legal')) {
            return redirect()->route('portal.index')
                ->with('info', 'Esta área é restrita a servidores. Use o portal para gerenciar suas propostas.');
        }

        return $next($request);
    }
}
