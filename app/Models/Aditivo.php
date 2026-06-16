<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Aditivo extends Model
{
    public const TIPOS = [
        'prazo'          => 'Prorrogação de Prazo',
        'valor'          => 'Acréscimo de Valor',
        'objeto'         => 'Alteração de Objeto',
        'apostilamento'  => 'Apostilamento',
        'outro'          => 'Outro',
    ];

    public const STATUS = [
        'minuta'   => 'Minuta',
        'assinado' => 'Assinado',
    ];

    protected $fillable = [
        'instrumento_id', 'numero', 'tipo', 'descricao',
        'data_assinatura', 'nova_data_fim', 'valor_adicional', 'status',
    ];

    protected function casts(): array
    {
        return [
            'data_assinatura' => 'date',
            'nova_data_fim'   => 'date',
            'valor_adicional' => 'decimal:2',
        ];
    }

    public function instrumento(): BelongsTo
    {
        return $this->belongsTo(Instrumento::class);
    }
}
