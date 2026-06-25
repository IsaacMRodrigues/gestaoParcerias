<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Instrumento extends Model
{
    public const TIPOS = [
        'termo_fomento'     => 'Termo de Fomento',
        'termo_colaboracao' => 'Termo de Colaboração',
        'acordo_cooperacao' => 'Acordo de Cooperação',
    ];

    public const STATUS = [
        'minuta'     => 'Minuta',
        'assinado'   => 'Assinado',
        'vigente'    => 'Vigente',
        'encerrado'  => 'Encerrado',
        'rescindido' => 'Rescindido',
    ];

    public const STATUS_COLORS = [
        'minuta'     => 'gray',
        'assinado'   => 'blue',
        'vigente'    => 'green',
        'encerrado'  => 'indigo',
        'rescindido' => 'red',
    ];

    protected $fillable = [
        'proposta_id', 'numero', 'tipo', 'objeto',
        'valor_repasse', 'valor_proprio',
        'data_assinatura', 'data_inicio', 'data_fim',
        'publicado_doe', 'data_publicacao_doe', 'numero_doe',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'data_assinatura'     => 'date',
            'data_inicio'         => 'date',
            'data_fim'            => 'date',
            'data_publicacao_doe' => 'date',
            'publicado_doe'       => 'boolean',
            'valor_repasse'       => 'decimal:2',
            'valor_proprio'       => 'decimal:2',
        ];
    }

    public function proposta(): BelongsTo
    {
        return $this->belongsTo(Proposta::class);
    }

    public function aditivos(): HasMany
    {
        return $this->hasMany(Aditivo::class)->orderBy('numero');
    }

    public function ordensPagamento(): HasMany
    {
        return $this->hasMany(OrdemPagamento::class)->orderBy('numero');
    }

    public function repasses(): HasMany
    {
        return $this->hasMany(Repasse::class)->orderBy('data_repasse');
    }

    public function despesas(): HasMany
    {
        return $this->hasMany(Despesa::class)->orderBy('data_despesa');
    }

    // ----- Controle de saldo (4.4 Execução) -----

    public function totalRepassado(): float
    {
        return (float) $this->repasses()->sum('valor');
    }

    public function totalGasto(): float
    {
        return (float) $this->despesas()->sum('valor');
    }

    public function saldo(): float
    {
        return $this->totalRepassado() - $this->totalGasto();
    }

    public function percentualExecutado(): int
    {
        $repassado = $this->totalRepassado();

        return $repassado > 0 ? (int) round($this->totalGasto() / $repassado * 100) : 0;
    }

    public function pecas(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Peca::class, 'pecaable')->orderBy('ordem');
    }

    public function valorTotal(): float
    {
        return (float) $this->valor_repasse + (float) $this->valor_proprio;
    }

    // Data de fim considerando o último aditivo de prazo
    public function dataFimVigente(): \Carbon\Carbon
    {
        $ultimo = $this->aditivos
            ->whereNotNull('nova_data_fim')
            ->last();

        return $ultimo ? $ultimo->nova_data_fim : $this->data_fim;
    }
}
