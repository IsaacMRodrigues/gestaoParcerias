<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Uma das assinaturas de um documento assinado por várias partes, em
 * sequência (ver Peca::ASSINATURAS_EM_SEQUENCIA). Nome e cargo ficam gravados
 * no ato, como no carimbo das demais peças.
 */
class PecaAssinatura extends Model
{
    protected $table = 'peca_assinaturas';

    protected $fillable = ['peca_id', 'papel', 'ordem', 'assinado_por', 'assinante_nome', 'assinante_cargo', 'assinado_em', 'codigo_validacao'];

    protected function casts(): array
    {
        return ['assinado_em' => 'datetime'];
    }

    public function peca(): BelongsTo
    {
        return $this->belongsTo(Peca::class);
    }

    public function assinante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assinado_por');
    }
}
