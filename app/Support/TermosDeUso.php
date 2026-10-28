<?php

namespace App\Support;

use App\Models\TermoAceite;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Termos de Uso da Plataforma: a versão vigente e o registro de cada aceite. Toda conta aceita a versão vigente —
 * no cadastro ou no primeiro acesso depois dela (ExigeAceiteDosTermos).
 */
class TermosDeUso
{
    public const VERSAO = '1.0';

    public const VIGENTE_DESDE = '28/10/2026';

    /** SHA-256 do texto desta versão (termos/_texto): texto mudado sem versão nova não passa nos testes. */
    public const HASH = '568788aa3bbb8684f9bf9af6a8195f2b6bb10431a42f522895433d0a37873219';

    public static function texto(): string
    {
        return view('termos._texto')->render();
    }

    public static function aceitou(User $user): bool
    {
        return $user->termos_versao === self::VERSAO;
    }

    /** Guarda o aceite com o hash do texto lido, o IP e o navegador; a conta passa a valer a versão vigente. */
    public static function registrarAceite(User $user, string $origem): TermoAceite
    {
        $request = request();

        return DB::transaction(function () use ($user, $origem, $request) {
            $aceite = TermoAceite::create([
                'user_id'    => $user->id,
                'nome'       => $user->name,
                'email'      => $user->email,
                'versao'     => self::VERSAO,
                'hash_texto' => hash('sha256', self::texto()),
                'origem'     => $origem,
                'ip'         => $request->ip(),
                'navegador'  => mb_substr(mb_scrub((string) $request->userAgent()), 0, 500),
                'aceito_em'  => now(),
            ]);

            $user->forceFill(['termos_versao' => self::VERSAO, 'termos_aceitos_em' => $aceite->aceito_em])->save();

            return $aceite;
        });
    }
}
