<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Diligencia extends Model
{
    public const STATUS = [
        'pendente'   => 'Pendente',
        'respondida' => 'Respondida',
        'encerrada'  => 'Encerrada',
    ];

    public const STATUS_COLORS = [
        'pendente'   => 'yellow',
        'respondida' => 'blue',
        'encerrada'  => 'gray',
    ];

    protected $fillable = [
        'proposta_id', 'parecer_id', 'descricao',
        'prazo', 'resposta', 'respondida_em', 'status',
    ];

    protected function casts(): array
    {
        return [
            'prazo'         => 'date',
            'respondida_em' => 'datetime',
        ];
    }

    public function proposta(): BelongsTo
    {
        return $this->belongsTo(Proposta::class);
    }

    public function parecer(): BelongsTo
    {
        return $this->belongsTo(Parecer::class);
    }
}
