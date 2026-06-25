<?php

namespace App\Support;

/**
 * Preenchimento automático ("puxar") dos modelos padrão: substitui os
 * marcadores {{token}} pelos dados já conhecidos no sistema na hora de preencher.
 * O que não tiver dado disponível permanece como está (ex.: "XXXX" para digitar).
 */
class Modelo
{
    public static function preencher(?string $html, array $tokens): ?string
    {
        if ($html === null) {
            return null;
        }

        $map = [];
        foreach ($tokens as $chave => $valor) {
            $map['{{' . $chave . '}}'] = (string) ($valor ?? '');
        }

        return strtr($html, $map);
    }
}
