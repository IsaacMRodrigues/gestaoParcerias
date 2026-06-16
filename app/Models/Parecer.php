<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Parecer extends Model
{
    protected $table = 'pareceres';

    public const TIPOS = [
        'tecnico'  => 'Parecer Técnico',
        'juridico' => 'Parecer Jurídico',
        'decisao'  => 'Decisão Final',
    ];

    public const RESULTADOS = [
        'aprovado'            => 'Aprovado',
        'aprovado_ressalvas'  => 'Aprovado com Ressalvas',
        'reprovado'           => 'Reprovado',
        'diligencia'          => 'Diligência Solicitada',
    ];

    public const RESULTADO_COLORS = [
        'aprovado'           => 'green',
        'aprovado_ressalvas' => 'yellow',
        'reprovado'          => 'red',
        'diligencia'         => 'blue',
    ];

    // Mapa: tipo + resultado → novo status da proposta
    public const STATUS_TRANSITIONS = [
        'tecnico'  => [
            'aprovado'           => 'em_analise',
            'aprovado_ressalvas' => 'em_analise',
            'reprovado'          => 'reprovada',
            'diligencia'         => 'em_negociacao',
        ],
        'juridico' => [
            'aprovado'           => 'em_analise',
            'aprovado_ressalvas' => 'em_analise',
            'reprovado'          => 'reprovada',
            'diligencia'         => 'em_negociacao',
        ],
        'decisao'  => [
            'aprovado'           => 'aprovada',
            'aprovado_ressalvas' => 'aprovada',
            'reprovado'          => 'reprovada',
            'diligencia'         => 'em_negociacao',
        ],
    ];

    protected $fillable = [
        'proposta_id', 'tipo', 'resultado',
        'texto', 'responsavel', 'data_parecer', 'observacoes',
    ];

    protected function casts(): array
    {
        return ['data_parecer' => 'date'];
    }

    public function proposta(): BelongsTo
    {
        return $this->belongsTo(Proposta::class);
    }

    public function diligencias(): HasMany
    {
        return $this->hasMany(Diligencia::class);
    }
}
