<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Converte dinheiro escrito em português ("40.000,00", "R$ 1.234,56") em número. O ponto só é
 * decimal quando sobram uma ou duas casas depois dele; a vírgula resolve sozinha.
 */
class NormalizaValoresMonetarios
{
    /** Campos monetários do sistema (todos decimal(15,2)). */
    private const CAMPOS = [
        'valor',
        'valor_solicitado',
        'valor_proprio',
        'valor_outras_fontes',
        'valor_disponivel',
        'valor_total',
        'valor_repasse',
        'valor_adicional',
        'valor_unitario',
        'saldo_anterior',
        'outros_creditos',
        'recursos_proprios',
        'despesas_bancarias',
        'valor_ressarcido',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $normalizados = [];

        foreach (self::CAMPOS as $campo) {
            $valor = $request->input($campo);

            if (!is_string($valor) || trim($valor) === '') {
                continue;
            }

            $normalizados[$campo] = self::paraDecimal($valor);
        }

        if ($normalizados) {
            $request->merge($normalizados);
        }

        return $next($request);
    }

    /** "R$ 40.000,00" → "40000.00" · "40.000" → "40000" · "40.00" → "40.00" */
    public static function paraDecimal(string $valor): string
    {
        $limpo = preg_replace('/[^\d,.\-]/', '', $valor);

        if (str_contains($limpo, ',')) {
            // Vírgula presente: ela é o decimal, e o ponto só pode ser milhar.
            return str_replace(',', '.', str_replace('.', '', $limpo));
        }

        // Sem vírgula: o ponto é milhar quando separa grupos de três dígitos.
        if (preg_match('/\.\d{3}(\.|$)/', $limpo)) {
            return str_replace('.', '', $limpo);
        }

        return $limpo;
    }
}
