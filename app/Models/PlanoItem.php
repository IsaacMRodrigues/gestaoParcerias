<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Item do plano de aplicação: o "aprovado" de cada natureza na prestação de contas. */
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
        return Despesa::rotuloNatureza($this->tipo_despesa);
    }
}
