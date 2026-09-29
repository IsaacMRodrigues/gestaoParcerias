<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Etapa extends Model
{
    protected $fillable = [
        'meta_id', 'numero', 'descricao',
        'responsavel', 'data_inicio', 'data_fim', 'recursos', 'valor',
    ];

    protected function casts(): array
    {
        return [
            'data_inicio' => 'date',
            'data_fim'    => 'date',
            'valor'       => 'decimal:2',
        ];
    }

    public function meta(): BelongsTo
    {
        return $this->belongsTo(Meta::class);
    }
}
