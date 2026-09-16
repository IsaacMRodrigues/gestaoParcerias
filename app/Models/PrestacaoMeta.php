<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Uma linha do monitoramento de metas do REO (Anexo III).
 *
 * A meta e a quantidade prevista são cópia do Plano de Trabalho no momento em
 * que a prestação é aberta — e não uma referência viva: o relatório precisa
 * dizer o que estava prometido naquele período, mesmo que o plano mude depois.
 */
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
