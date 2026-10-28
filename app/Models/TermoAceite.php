<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/** Um aceite dos Termos de Uso: quem, qual versão, o hash do texto lido, quando e de onde. Não se altera. */
class TermoAceite extends Model
{
    protected $table = 'termos_aceites';

    public $timestamps = false;

    protected $guarded = ['id'];

    public const ORIGENS = [
        'cadastro'        => 'no cadastro',
        'primeiro_acesso' => 'no primeiro acesso',
        'nova_versao'     => 'na atualização dos Termos',
    ];

    protected function casts(): array
    {
        return ['aceito_em' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('O registro de aceite dos Termos de Uso não se altera.'));
    }
}
