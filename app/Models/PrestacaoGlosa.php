<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Valor aprovado e glosas mensais de um bloco do Anexo VI. */
class PrestacaoGlosa extends Model
{
    protected $table = 'prestacao_glosas';

    protected $fillable = [
        'prestacao_id', 'natureza', 'valor_aprovado',
        'glosa_mes_1', 'glosa_mes_2', 'glosa_mes_3', 'glosa_mes_4', 'glosa_mes_5', 'glosa_mes_6',
    ];

    public function prestacao(): BelongsTo
    {
        return $this->belongsTo(PrestacaoContas::class, 'prestacao_id');
    }

    /** A planilha soma as glosas do semestre; aqui é a mesma conta. */
    public function totalGlosado(): float
    {
        return collect(range(1, 6))->sum(fn (int $m) => (float) $this->{"glosa_mes_{$m}"});
    }
}
