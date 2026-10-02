<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Chamado de suporte: abre quem está logado; atende quem tem a permissão suporte. */
class Chamado extends Model
{
    protected $fillable = [
        'numero', 'user_id', 'conta_id', 'contato', 'autor_nome', 'autor_vinculo',
        'categoria', 'assunto', 'status', 'origem_url',
        'respondido_em', 'resolvido_em', 'resolvido_por',
    ];

    protected function casts(): array
    {
        return [
            'respondido_em' => 'datetime',
            'resolvido_em'  => 'datetime',
        ];
    }

    /** As naturezas de chamado (a urgência de um problema não é a de uma sugestão). */
    public const CATEGORIAS = [
        'problema'  => 'Problema no sistema',
        'duvida'    => 'Dúvida de uso',
        'sugestao'  => 'Sugestão',
        // Pedido de nova senha. Nasce na tela de entrada, sem login — ver
        // PedidoDeSenhaController —, e quem atende define a senha provisória.
        'acesso'    => 'Acesso e senha',
    ];

    public const CATEGORIAS_COLORS = [
        'problema' => 'red',
        'duvida'   => 'accent',
        'sugestao' => 'brand',
        'acesso'   => 'accent',
    ];

    public const STATUS = [
        'aberto'       => 'Aberto',
        'em_andamento' => 'Em andamento',
        'resolvido'    => 'Resolvido',
    ];

    /** Ver Processo::STATUS_COLORS: laranja espera alguém, verde encerrado. */
    public const STATUS_COLORS = [
        'aberto'       => 'accent',
        'em_andamento' => 'accent',
        'resolvido'    => 'brand',
    ];

    // ------------------------------------------------------------------ elos

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** A conta cuja senha o pedido quer trocar — não é o autor: quem pede não está logado. */
    public function conta(): BelongsTo
    {
        return $this->belongsTo(User::class, 'conta_id');
    }

    /** Pedido de nova senha que a equipe pode atender definindo uma provisória. */
    public function ehPedidoDeSenha(): bool
    {
        return $this->categoria === 'acesso' && $this->conta_id !== null;
    }

    public function resolvidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolvido_por');
    }

    public function mensagens(): HasMany
    {
        return $this->hasMany(ChamadoMensagem::class)->orderBy('id');
    }

    /** O texto de abertura é a primeira mensagem — não há outro lugar de texto. */
    public function abertura(): ?ChamadoMensagem
    {
        return $this->mensagens->first();
    }

    // ---------------------------------------------------------------- estado

    public function resolvido(): bool
    {
        return $this->status === 'resolvido';
    }

    public function categoriaLabel(): string
    {
        return self::CATEGORIAS[$this->categoria] ?? $this->categoria;
    }

    public function statusLabel(): string
    {
        return self::STATUS[$this->status] ?? $this->status;
    }

    /** Chamados que ainda ocupam o suporte — é o número do selo no menu. */
    public function scopeEmAberto(Builder $query): Builder
    {
        return $query->whereIn('status', ['aberto', 'em_andamento']);
    }

    /** Numeração por ano (2026/0001), pelo maior número do ano, não por contagem. */
    public static function proximoNumero(): string
    {
        $ano = now()->year;

        $ultimo = static::where('numero', 'like', $ano . '/%')
            ->orderByDesc('numero')
            ->value('numero');

        $sequencial = $ultimo ? ((int) substr($ultimo, 5)) + 1 : 1;

        return $ano . '/' . str_pad((string) $sequencial, 4, '0', STR_PAD_LEFT);
    }

    /** De onde a pessoa fala: a Secretaria (servidor) ou a organização (OSC). */
    public static function vinculoDe(User $user): ?string
    {
        if ($user->ehRepresentanteOsc()) {
            return $user->osc?->name;
        }

        $setor = $user->setor ? (Processo::SETORES[$user->setor] ?? strtoupper($user->setor)) : null;

        return $user->orgao?->name ?: $setor;
    }
}
