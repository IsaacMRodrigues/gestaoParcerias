<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Manifestação de Interesse — a OSC propõe sem chamamento aberto.
 *
 * Caminho: a OSC monta o dossiê (plano de trabalho e habilitação) e submete;
 * a SCP recebe e ouve a Secretaria da área, que diz se há interesse público e
 * orçamento; a SCP decide o encaminhamento — dispensa ou inexigibilidade — e o
 * deferimento gera o chamamento e a proposta que seguem pelo fluxo de sempre.
 */
class ManifestacaoInteresse extends Model
{
    use HasFactory;

    protected $table = 'manifestacoes_interesse';

    protected $fillable = [
        'osc_id', 'orgao_id', 'titulo', 'objeto', 'justificativa', 'publico_alvo',
        'valor_solicitado', 'valor_proprio', 'data_inicio_prevista', 'data_fim_prevista',
        'status', 'setor_atual', 'submetida_em',
        'parecer_favoravel', 'parecer_ug', 'parecer_por', 'parecer_em',
        'decisao', 'decisao_motivo', 'decidida_por', 'decidida_em',
        'chamamento_id', 'proposta_id',
    ];

    protected function casts(): array
    {
        return [
            'valor_solicitado'     => 'decimal:2',
            'valor_proprio'        => 'decimal:2',
            'data_inicio_prevista' => 'date',
            'data_fim_prevista'    => 'date',
            'submetida_em'         => 'datetime',
            'parecer_favoravel'    => 'boolean',
            'parecer_em'           => 'datetime',
            'decidida_em'          => 'datetime',
        ];
    }

    public const STATUS = [
        'rascunho'    => 'Rascunho',
        'submetida'   => 'Aguardando o SCP',
        'em_analise'  => 'Em análise na Secretaria',
        'analisada'   => 'Analisada — decisão do SCP',
        'deferida'    => 'Deferida',
        'indeferida'  => 'Indeferida',
    ];

    public const STATUS_COLORS = [
        'rascunho'   => 'gray',
        'submetida'  => 'accent',
        'em_analise' => 'accent',
        'analisada'  => 'accent',
        'deferida'   => 'brand',
        'indeferida' => 'red',
    ];

    /** Encaminhamentos que a SCP pode dar ao deferir. */
    public const ENCAMINHAMENTOS = [
        'dispensa'        => 'Dispensa de chamamento público',
        'inexigibilidade' => 'Inexigibilidade de chamamento público',
    ];

    public function osc(): BelongsTo
    {
        return $this->belongsTo(Osc::class);
    }

    public function orgao(): BelongsTo
    {
        return $this->belongsTo(Orgao::class);
    }

    public function metas(): HasMany
    {
        return $this->hasMany(Meta::class, 'manifestacao_id')->orderBy('numero');
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(Documento::class, 'manifestacao_id')->latest();
    }

    public function chamamento(): BelongsTo
    {
        return $this->belongsTo(Chamamento::class);
    }

    public function proposta(): BelongsTo
    {
        return $this->belongsTo(Proposta::class);
    }

    public function parecerPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parecer_por');
    }

    public function decididaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decidida_por');
    }

    public function statusLabel(): string
    {
        return self::STATUS[$this->status] ?? $this->status;
    }

    /** Ainda é da OSC: só em rascunho ela edita e some da vista do município. */
    public function ehRascunho(): bool
    {
        return $this->status === 'rascunho';
    }

    public function decidida(): bool
    {
        return in_array($this->status, ['deferida', 'indeferida'], true);
    }

    /**
     * O que falta para a OSC poder submeter. Vazio = pode.
     *
     * Dossiê completo é a regra combinada: sem plano de trabalho e sem
     * habilitação, a Secretaria não tem como dizer se há interesse, e a SCP
     * decidiria no escuro.
     */
    public function pendenciasParaSubmeter(): array
    {
        $faltam = [];

        if ($this->metas()->count() === 0) {
            $faltam[] = 'plano de trabalho (ao menos uma meta)';
        }

        if ($this->documentos()->count() === 0) {
            $faltam[] = 'documentos de habilitação';
        }

        if ((float) $this->valor_solicitado <= 0) {
            $faltam[] = 'valor solicitado';
        }

        return $faltam;
    }

    /** Manifestações que o usuário interno pode ver — o mesmo recorte por órgão. */
    public function scopeVisiveisPara($query, User $user)
    {
        if ($user->podeVerTodosOrgaos()) {
            return $query;
        }

        return $query->where('orgao_id', $user->orgao_id);
    }

    /** Fora do rascunho, é trabalho em curso para o município. */
    public function scopeEmTramite($query)
    {
        return $query->whereIn('status', ['submetida', 'em_analise', 'analisada']);
    }
}
