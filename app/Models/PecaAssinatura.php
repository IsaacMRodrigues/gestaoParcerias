<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Uma das assinaturas de documento assinado em sequência; nome e cargo gravados no ato. */
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
