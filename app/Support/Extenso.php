<?php

namespace App\Support;

/**
 * Número inteiro por extenso, em português.
 *
 * Existe para as declarações que pedem a quantidade escrita duas vezes, como
 * "ativo há 12 (doze) anos". O PHP faz isso com a extensão intl, mas ela não
 * está no ambiente de desenvolvimento — e um modelo que funciona em produção e
 * quebra na máquina de quem o mantém é um modelo que ninguém consegue testar.
 *
 * Cobre de 0 a 999.999, que sobra para anos de existência, quantidades e
 * contagens de folhas. Valores em reais ficam para outra hora: pedem
 * centavos, "de reais" e as regras de milhão.
 */
class Extenso
{
    private const UNIDADES = ['zero', 'um', 'dois', 'três', 'quatro', 'cinco', 'seis', 'sete', 'oito', 'nove',
        'dez', 'onze', 'doze', 'treze', 'quatorze', 'quinze', 'dezesseis', 'dezessete', 'dezoito', 'dezenove'];

    private const DEZENAS = [2 => 'vinte', 'trinta', 'quarenta', 'cinquenta', 'sessenta', 'setenta', 'oitenta', 'noventa'];

    private const CENTENAS = [1 => 'cento', 'duzentos', 'trezentos', 'quatrocentos', 'quinhentos',
        'seiscentos', 'setecentos', 'oitocentos', 'novecentos'];

    public static function inteiro(int $n): string
    {
        if ($n < 0 || $n > 999_999) {
            return (string) $n;
        }

        if ($n < 1000) {
            return self::ate999($n);
        }

        $milhares = intdiv($n, 1000);
        $resto    = $n % 1000;
        $mil      = $milhares === 1 ? 'mil' : self::ate999($milhares) . ' mil';

        if ($resto === 0) {
            return $mil;
        }

        // "mil e cem", "mil e vinte", mas "mil cento e vinte": o "e" só entra
        // quando o que sobra é uma centena redonda ou cabe em duas casas.
        $liga = ($resto < 100 || $resto % 100 === 0) ? ' e ' : ' ';

        return $mil . $liga . self::ate999($resto);
    }

    private static function ate999(int $n): string
    {
        if ($n < 20) {
            return self::UNIDADES[$n];
        }

        if ($n < 100) {
            $d = intdiv($n, 10);
            $u = $n % 10;

            return self::DEZENAS[$d] . ($u ? ' e ' . self::UNIDADES[$u] : '');
        }

        if ($n === 100) {
            return 'cem';
        }

        $c = intdiv($n, 100);
        $r = $n % 100;

        return self::CENTENAS[$c] . ($r ? ' e ' . self::ate999($r) : '');
    }
}
