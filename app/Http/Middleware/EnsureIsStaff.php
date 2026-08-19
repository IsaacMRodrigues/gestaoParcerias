<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Barra quem é da OSC nas telas internas da Administração.
 *
 * Antes comparava com o nome do papel ('responsavel_legal'). Isso quebrou no
 * dia em que a OSC ganhou equipe: o papel novo (membro_osc) não estava na
 * comparação e a conta entrava no dashboard interno. Agora pergunta a
 * definição única — User::temAcessoInterno() —, espelho exato do EnsureIsOsc,
 * de modo que criar mais um papel de OSC não reabre o buraco.
 */
class EnsureIsStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && ! auth()->user()->temAcessoInterno()) {
            return redirect()->route('portal.index')
                ->with('info', 'Esta área é restrita a servidores. Use o portal para gerenciar suas propostas.');
        }

        return $next($request);
    }
}
