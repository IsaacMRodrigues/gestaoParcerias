<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Uma parcela do cronograma de desembolso: ano, mês e valor. */
class PlanoDesembolso extends Model
{
    protected $table = 'plano_desembolsos';

    public const MESES = [
        1 => 'Janeiro',   2 => 'Fevereiro', 3  => 'Março',    4  => 'Abril',
        5 => 'Maio',      6 => 'Junho',     7  => 'Julho',    8  => 'Agosto',
        9 => 'Setembro', 10 => 'Outubro',  11 => 'Novembro', 12 => 'Dezembro',
    ];

    protected $fillable = ['proposta_id', 'manifestacao_id', 'ano', 'mes', 'valor'];

    protected function casts(): array
    {
        return ['valor' => 'decimal:2'];
    }

    public function proposta(): BelongsTo
    {
        return $this->belongsTo(Proposta::class);
    }

    public function manifestacao(): BelongsTo
    {
        return $this->belongsTo(ManifestacaoInteresse::class, 'manifestacao_id');
    }

    public function mesLabel(): string
    {
        return self::MESES[(int) $this->mes] ?? (string) $this->mes;
    }
}
