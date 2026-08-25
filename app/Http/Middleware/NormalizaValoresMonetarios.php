<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aceita dinheiro escrito como se escreve em português.
 *
 * Os campos monetários passaram a ser digitados com máscara ("40.000,00"), e a
 * validação `numeric` recusa isso. Converter em cada FormRequest espalharia a
 * mesma regra por uma dezena de arquivos — e esqueceríamos um. Aqui o request
 * chega ao controller já com o número, venha ele do campo com máscara, de um
 * `type="number"` antigo ou de alguém que colou "R$ 1.234,56".
 *
 * A conversão é conservadora com o ponto, que em português é milhar e em
 * inglês é decimal: só o trata como decimal quando sobram uma ou duas casas
 * depois dele ("40.00"); três casas viram milhar ("40.000"). Vírgula, quando
 * existe, resolve a ambiguidade sozinha.
 */
class NormalizaValoresMonetarios
{
    /**
     * Campos monetários do sistema (todos `decimal(15,2)` no banco).
     * Ver as colunas em propostas, chamamentos, programas, instrumentos,
     * aditivos, manifestações, repasses, despesas e ordens de pagamento.
     */
    private const CAMPOS = [
        'valor',
        'valor_solicitado',
        'valor_proprio',
        'valor_disponivel',
        'valor_total',
        'valor_repasse',
        'valor_adicional',
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
