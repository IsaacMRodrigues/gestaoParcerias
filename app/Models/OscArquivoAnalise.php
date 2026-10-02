<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Análise de um arquivo da OSC numa parceria, da versão analisada (versão nova pede análise nova). */
class OscArquivoAnalise extends Model
{
    protected $table = 'osc_arquivo_analises';

    public const SITUACOES = [
        'aprovado' => 'Aprovado',
        'recusado' => 'Recusado',
    ];

    protected $fillable = ['proposta_id', 'osc_arquivo_id', 'situacao', 'motivo', 'analisado_por', 'analisado_em'];

    protected function casts(): array
    {
        return ['analisado_em' => 'datetime'];
    }

    public function arquivo(): BelongsTo
    {
        return $this->belongsTo(OscArquivo::class, 'osc_arquivo_id');
    }

    public function proposta(): BelongsTo
    {
        return $this->belongsTo(Proposta::class);
    }

    public function analista(): BelongsTo
    {
        return $this->belongsTo(User::class, 'analisado_por');
    }
}
