<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Proposta extends Model
{
    public const STATUS = [
        'rascunho'      => 'Rascunho',
        'submetida'     => 'Submetida',
        'em_analise'    => 'Em Análise',
        'em_negociacao' => 'Em Negociação',
        'aprovada'      => 'Aprovada',
        'reprovada'     => 'Reprovada',
        'cancelada'     => 'Cancelada',
    ];

    public const STATUS_COLORS = [
        'rascunho'      => 'gray',
        'submetida'     => 'blue',
        'em_analise'    => 'yellow',
        'em_negociacao' => 'purple',
        'aprovada'      => 'green',
        'reprovada'     => 'red',
        'cancelada'     => 'red',
    ];

    protected $fillable = [
        'chamamento_id', 'osc_id', 'titulo', 'objeto', 'justificativa',
        'valor_solicitado', 'valor_proprio',
        'data_inicio_prevista', 'data_fim_prevista',
        'status', 'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'data_inicio_prevista' => 'date',
            'data_fim_prevista'    => 'date',
            'submitted_at'         => 'datetime',
            'valor_solicitado'     => 'decimal:2',
            'valor_proprio'        => 'decimal:2',
        ];
    }

    public function chamamento(): BelongsTo
    {
        return $this->belongsTo(Chamamento::class);
    }

    /**
     * Restringe às propostas visíveis ao usuário: quem é lotado numa Secretaria
     * (órgão) vê só as propostas dos chamamentos do seu órgão; admin/auditoria e
     * papéis transversais veem todas.
     */
    public function scopeVisiveisPara($query, User $user)
    {
        if ($user->podeVerTodosOrgaos()) {
            return $query;
        }

        return $query->whereHas('chamamento.programa', fn ($q) => $q->where('orgao_id', $user->orgao_id));
    }

    public function osc(): BelongsTo
    {
        return $this->belongsTo(Osc::class);
    }

    public function metas(): HasMany
    {
        return $this->hasMany(Meta::class)->orderBy('numero');
    }

    public function instrumento(): HasOne
    {
        return $this->hasOne(Instrumento::class);
    }

    public function pareceres(): HasMany
    {
        return $this->hasMany(Parecer::class)->orderBy('created_at');
    }

    public function diligencias(): HasMany
    {
        return $this->hasMany(Diligencia::class)->orderBy('created_at');
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(Documento::class)->latest();
    }

    public function parecer(string $tipo): ?Parecer
    {
        return $this->pareceres->firstWhere('tipo', $tipo);
    }

    public function valorTotal(): float
    {
        return (float) $this->valor_solicitado + (float) $this->valor_proprio;
    }
}
