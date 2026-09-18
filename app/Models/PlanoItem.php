<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Item do plano de aplicação dos recursos (I — Demonstrativo de Recursos).
 *
 * É o que o Parecer Financeiro confere e o que a prestação de contas usa como
 * "aprovado" de cada natureza de despesa.
 */
class PlanoItem extends Model
{
    protected $table = 'plano_itens';

    protected $fillable = [
        'proposta_id', 'manifestacao_id', 'numero', 'descricao',
        'tipo_despesa', 'unidade', 'quantidade', 'valor_unitario',
        'atividades_vinculadas',
    ];

    protected function casts(): array
    {
        return [
            'quantidade'     => 'decimal:2',
            'valor_unitario' => 'decimal:2',
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

    public function total(): float
    {
        return (float) $this->quantidade * (float) $this->valor_unitario;
    }

    public function tipoLabel(): string
    {
        return Despesa::NATUREZAS[$this->tipo_despesa] ?? $this->tipo_despesa;
    }
}
