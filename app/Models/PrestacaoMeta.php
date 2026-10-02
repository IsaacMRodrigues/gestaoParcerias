<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Linha do monitoramento de metas do REO (Anexo III): cópia do plano na abertura da prestação. */
class PrestacaoMeta extends Model
{
    protected $table = 'prestacao_metas';

    protected $fillable = [
        'prestacao_id', 'meta_id', 'descricao', 'quantidade_prevista',
        'quantidade_atendida', 'cumpriu', 'justificativa', 'ordem',
    ];

    protected function casts(): array
    {
        return ['cumpriu' => 'boolean'];
    }

    public function prestacao(): BelongsTo
    {
        return $this->belongsTo(PrestacaoContas::class, 'prestacao_id');
    }
}
