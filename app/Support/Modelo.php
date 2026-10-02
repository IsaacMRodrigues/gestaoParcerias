<?php

namespace App\Support;

/** Substitui os marcadores {{token}} dos modelos pelos dados conhecidos; o resto fica como está. */
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
