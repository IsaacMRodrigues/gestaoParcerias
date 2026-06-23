<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TermoReferencia extends Model
{
    protected $table = 'termo_referencias';

    protected $fillable = [
        'processo_id',
        'descricao_realidade', 'justificativa', 'objeto', 'objetivos_especificos',
        'valor_total', 'dotacao', 'ficha', 'fonte', 'prazo_meses',
        'assinado_por', 'assinado_em',
    ];

    protected function casts(): array
    {
        return [
            'valor_total' => 'decimal:2',
            'assinado_em' => 'datetime',
        ];
    }

    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class);
    }

    public function assinante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assinado_por');
    }

    public function assinado(): bool
    {
        return !is_null($this->assinado_em);
    }
}
