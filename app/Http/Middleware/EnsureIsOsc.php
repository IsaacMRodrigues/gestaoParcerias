<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Espelho do EnsureIsStaff: protege o que só a OSC faz — participar de
 * chamamento, submeter e acompanhar proposta, protocolar recurso.
 *
 * Servidor é usuário interno dos setores: analisa, tramita e decide sobre as
 * propostas, nunca as apresenta. Deixar essa regra só nos controllers (que
 * checavam ->osc por conta própria, cada um de um jeito) era frágil — bastava
 * uma rota nova esquecer a checagem. Aqui a porta é única.
 */
class EnsureIsOsc
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->temAcessoInterno()) {
            return redirect()->route('portal.index')->with(
                'info',
                'Esta área é exclusiva das OSCs. Sua conta é de uso interno da Administração: '
                .'você acompanha e analisa as propostas pela área administrativa.'
            );
        }

        // Responsável legal sem OSC vinculada (o cadastro foi removido). Não
        // adianta mandar ao auto-cadastro: aquela tela é só para visitante, e
        // criaria uma segunda conta em vez de consertar esta.
        if (!$user?->ehRepresentanteOsc()) {
            return redirect()->route('portal.index')->with(
                'info',
                'Sua conta não está vinculada a nenhuma OSC. Procure a Administração para regularizar o cadastro.'
            );
        }

        return $next($request);
    }
}
