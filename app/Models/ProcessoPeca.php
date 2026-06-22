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

    /**
     * Etapa do fluxo em que cada peça é preenchida (índice em Processo::ETAPAS).
     * Ofício = etapa 0 (UG) · Parecer Financeiro = etapa 2 (SEPLAN) · Abertura = etapa 3 (UG).
     */
    public const ETAPA = [
        'oficio'             => 0,
        'parecer_financeiro' => 2,
        'abertura'           => 3,
    ];

    public function setorResponsavel(): string
    {
        return self::SETOR_RESPONSAVEL[$this->tipo] ?? 'ug';
    }

    public function etapaDesignada(): int
    {
        return self::ETAPA[$this->tipo] ?? 0;
    }

    /**
     * Pode ser preenchida/assinada agora? (setor responsável, na etapa certa,
     * com o processo em andamento e a peça ainda não assinada)
     */
    public function podeEditar(Processo $processo, ?User $user): bool
    {
        return $user
            && in_array($processo->status, ['em_planejamento', 'em_tramite'])
            && !$this->assinado()
            && $user->setor === $this->setorResponsavel()
            && $processo->setor_atual === $this->setorResponsavel()
            && $processo->etapa === $this->etapaDesignada();
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
