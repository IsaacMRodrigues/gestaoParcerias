<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Chamamento extends Model
{
    public const TIPOS = [
        'chamamento_publico' => 'Chamamento Público',
        'dispensa'           => 'Dispensa de Chamamento',
        'inexigibilidade'    => 'Inexigibilidade de Chamamento',
    ];

    public const STATUS = [
        'rascunho'     => 'Rascunho',
        'publicado'    => 'Publicado',
        'em_inscricao' => 'Em Inscrição',
        'em_analise'   => 'Em Análise',
        'encerrado'    => 'Encerrado',
        'cancelado'    => 'Cancelado',
    ];

    public const STATUS_COLORS = [
        'rascunho'     => 'gray',
        'publicado'    => 'blue',
        'em_inscricao' => 'green',
        'em_analise'   => 'yellow',
        'encerrado'    => 'indigo',
        'cancelado'    => 'red',
    ];

    protected $fillable = [
        'programa_id', 'processo_id', 'numero', 'titulo', 'objeto', 'tipo',
        'valor_disponivel', 'data_publicacao', 'data_inicio_inscricao',
        'data_fim_inscricao', 'data_resultado', 'requisitos', 'status',
    ];

    protected function casts(): array
    {
        return [
            'data_publicacao'       => 'date',
            'data_inicio_inscricao' => 'date',
            'data_fim_inscricao'    => 'date',
            'data_resultado'        => 'date',
            'valor_disponivel'      => 'decimal:2',
        ];
    }

    public function programa(): BelongsTo
    {
        return $this->belongsTo(Programa::class);
    }

    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class);
    }

    public function pecas(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Peca::class, 'pecaable')->orderBy('ordem');
    }

    public function propostas(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Proposta::class);
    }

    /**
     * Categoria de peças aplicável conforme o tipo do chamamento.
     */
    public function categoriaPecas(): string
    {
        return $this->tipo === 'chamamento_publico'
            ? 'chamamento_publico'
            : 'dispensa_inexigibilidade';
    }

    /**
     * Status derivado das datas quando o admin não mudou manualmente.
     * publicado + dentro do período de inscrição → trata como em_inscricao.
     */
    public function getStatusEfetivoAttribute(): string
    {
        if ($this->status === 'publicado'
            && $this->data_inicio_inscricao
            && $this->data_fim_inscricao
        ) {
            $hoje = now()->startOfDay();
            if ($hoje->between($this->data_inicio_inscricao, $this->data_fim_inscricao)) {
                return 'em_inscricao';
            }
        }

        return $this->status;
    }

    /** Chamamento competitivo aberto a propostas da OSC no portal. */
    public function aceitaPropostas(): bool
    {
        return $this->tipo === 'chamamento_publico'
            && $this->status_efetivo === 'em_inscricao';
    }

    /** Dispensa/Inexigibilidade — publicação pública, sem inscrição competitiva. */
    public function ehDispensa(): bool
    {
        return in_array($this->tipo, ['dispensa', 'inexigibilidade'], true);
    }
}
