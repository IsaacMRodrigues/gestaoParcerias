<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Repasse extends Model
{
    protected $fillable = [
        'instrumento_id', 'parcela', 'data_repasse', 'valor', 'documento', 'observacao',
    ];

    protected function casts(): array
    {
        return [
            'data_repasse' => 'date',
            'valor'        => 'decimal:2',
        ];
    }

    public function instrumento(): BelongsTo
    {
        return $this->belongsTo(Instrumento::class);
    }
}
