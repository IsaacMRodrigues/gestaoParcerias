<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessoPeca extends Model
{
    public const TIPOS = [
        'oficio'            => 'Ofício',
        'parecer_financeiro'=> 'Parecer Financeiro',
        'abertura'          => 'Abertura de Processo',
    ];

    /**
     * Setor responsável por preencher/assinar cada peça (fluxo do cliente).
     */
    public const SETOR_RESPONSAVEL = [
        'oficio'             => 'ug',
        'parecer_financeiro' => 'seplan',
        'abertura'           => 'ug',
    ];

    public function setorResponsavel(): string
    {
        return self::SETOR_RESPONSAVEL[$this->tipo] ?? 'ug';
    }

    protected $fillable = [
        'processo_id', 'tipo', 'conteudo', 'assinado_por', 'assinado_em',
    ];

    protected function casts(): array
    {
        return ['assinado_em' => 'datetime'];
    }

    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class);
    }

    public function assinante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assinado_por');
    }

    public function assinado(): bool
    {
        return !is_null($this->assinado_em);
    }
}
