<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** CPF válido pelos dígitos verificadores, com ou sem máscara. */
class Cpf implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!self::valido((string) $value)) {
            $fail('Informe um CPF válido.');
        }
    }

    public static function valido(string $cpf): bool
    {
        $d = preg_replace('/\D/', '', $cpf);
        if (strlen($d) !== 11 || preg_match('/^(\d)\1{10}$/', $d)) {
            return false;
        }

        foreach ([9, 10] as $n) {
            $soma = 0;
            for ($i = 0; $i < $n; $i++) {
                $soma += (int) $d[$i] * ($n + 1 - $i);
            }
            if ((int) $d[$n] !== ($soma * 10) % 11 % 10) {
                return false;
            }
        }

        return true;
    }

    /** "12345678909" → "123.456.789-09", o formato gravado no cadastro. */
    public static function formatar(string $cpf): string
    {
        $d = preg_replace('/\D/', '', $cpf);

        return substr($d, 0, 3) . '.' . substr($d, 3, 3) . '.' . substr($d, 6, 3) . '-' . substr($d, 9, 2);
    }
}
