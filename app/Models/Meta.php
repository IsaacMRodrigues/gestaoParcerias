<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Meta extends Model
{
    protected $fillable = [
        // A meta nasce na proposta ou na manifestação de interesse — no
        // deferimento, a mesma linha ganha a proposta criada e segue valendo.
        'proposta_id', 'manifestacao_id', 'numero', 'descricao', 'atividades',
        'indicador', 'meios_verificacao', 'resultados_esperados', 'valor',
        'meta_quantitativa', 'data_inicio', 'data_fim',
    ];

    protected function casts(): array
    {
        return [
            'data_inicio' => 'date',
            'data_fim'    => 'date',
            'valor'       => 'decimal:2',
        ];
    }

    public function proposta(): BelongsTo
    {
        return $this->belongsTo(Proposta::class);
    }

    public function manifestacao(): BelongsTo
    {
        return $this->belongsTo(ManifestacaoInteresse::class, 'manifestacao_id');
    }

    public function etapas(): HasMany
    {
        return $this->hasMany(Etapa::class)->orderBy('numero');
    }
}
