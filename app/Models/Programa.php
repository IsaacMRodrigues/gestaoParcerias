<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Programa extends Model
{
    public const TIPOS = [
        'termo_fomento'      => 'Termo de Fomento',
        'termo_colaboracao'  => 'Termo de Colaboração',
        'acordo_cooperacao'  => 'Acordo de Cooperação',
    ];

    public const STATUS = [
        'ativo'     => 'Ativo',
        'encerrado' => 'Encerrado',
        'suspenso'  => 'Suspenso',
    ];

    protected $fillable = [
        'orgao_id', 'name', 'sigla', 'tipo', 'objetivo',
        'valor_total', 'data_inicio', 'data_fim', 'status',
    ];

    protected function casts(): array
    {
        return [
            'data_inicio'  => 'date',
            'data_fim'     => 'date',
            'valor_total'  => 'decimal:2',
        ];
    }

    public function orgao(): BelongsTo
    {
        return $this->belongsTo(Orgao::class);
    }

    public function chamamentos(): HasMany
    {
        return $this->hasMany(Chamamento::class);
    }
}
