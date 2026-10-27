<?php

namespace App\Models;

use App\Models\Concerns\TemPlanoDeTrabalho;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Manifestação de Interesse e Nova Proposta. A manifestação é o PMIS (arts. 18 a 21 da Lei 13.019/2014):
 * qualquer pessoa propõe por formulário público, sem plano de trabalho. A Nova Proposta é da OSC, com
 * plano; o deferimento dela gera o chamamento e a proposta, que seguem pelo fluxo de sempre.
 */
class ManifestacaoInteresse extends Model
{
    use HasFactory;
    use TemPlanoDeTrabalho;

    protected $table = 'manifestacoes_interesse';

    public function chavePlano(): string
    {
        return 'manifestacao_id';
    }

    protected $fillable = [
        'tipo', 'protocolo', 'token', 'fundamento_pedido',
        'osc_id', 'proponente_nome', 'proponente_tipo', 'proponente_email',
        'orgao_id', 'titulo', 'objeto', 'justificativa', 'publico_alvo',
        'interesse_publico', 'diagnostico', 'viabilidade', 'arquivo_path', 'arquivo_nome', 'arquivo_tamanho',
        'descricao_realidade', 'objetivos', 'objetivos_especificos', 'metodologia', 'plano_aplicacao', 'valor_outras_fontes', 'vigencia_dias',
        'atuacao_rede', 'rede_cnpj', 'rede_razao_social', 'rede_municipio', 'rede_data_termo',
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
            'valor_outras_fontes'  => 'decimal:2',
            'atuacao_rede'         => 'boolean',
            'rede_data_termo'      => 'date',
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
        // Só o rótulo mudou (homologação, item 8); a chave fica, e com ela os
        // registros antigos. Desde o item 1 nenhuma manifestação nova passa por
        // aqui — o envio já a entrega à Unidade Gestora.
        'submetida'   => 'Aguardando análise',
        'em_analise'  => 'Em análise na Unidade Gestora',
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

    /**
     * Dois processos na mesma tabela, com o mesmo conteúdo e caminhos diferentes:
     * - manifestação: a OSC escolhe a Secretaria, a UG opina e a SCP decide;
     * - Nova Proposta: a SCP decide o fundamento e a UG, e a UG decide.
     * Ver ManifestacaoAnaliseController.
     */
    public const TIPOS = [
        'manifestacao' => 'Manifestação de interesse',
        'proposta'     => 'Nova Proposta',
    ];

    /** Quem assina a manifestação do PMIS. */
    public const TIPOS_DE_PESSOA = [
        'pf' => 'Pessoa Física',
        'pj' => 'Pessoa Jurídica',
    ];

    /** Fundamento da Nova Proposta (arts. 30 e 31 da Lei 13.019/2014), decidido pela SCP. */
    public const FUNDAMENTOS_PEDIDO = [
        'dispensa'        => 'Dispensa de chamamento público (art. 30)',
        'inexigibilidade' => 'Inexigibilidade de chamamento público (art. 31)',
    ];

    /** Próximo protocolo (2026/0001), por ano, um livro só; trava de linha contra envios simultâneos. */
    public static function proximoProtocolo(): string
    {
        $ano = now()->year;

        $ultimo = static::where('protocolo', 'like', $ano . '/%')
            ->orderByDesc('protocolo')->lockForUpdate()->value('protocolo');

        $sequencial = $ultimo ? ((int) substr($ultimo, 5)) + 1 : 1;

        return $ano . '/' . str_pad((string) $sequencial, 4, '0', STR_PAD_LEFT);
    }

    /** Envia: protocola (se ainda não tem número) e entrega ao setor. */
    public function enviar(array $tramite): void
    {
        DB::transaction(function () use ($tramite) {
            $this->update($tramite + [
                'submetida_em' => now(),
                'protocolo'    => $this->protocolo ?? self::proximoProtocolo(),
            ]);
        });
    }

    public function ehNovaProposta(): bool
    {
        return $this->tipo === 'proposta';
    }

    /** Manifestação no formato do PMIS (formulário público), não a antiga, com plano de trabalho. */
    public function ehPmis(): bool
    {
        return !$this->ehNovaProposta() && $this->interesse_publico !== null;
    }

    /** Quem propôs: a OSC vinculada ou quem se identificou no formulário público. */
    public function nomeDoProponente(): ?string
    {
        return $this->osc?->name ?? $this->proponente_nome;
    }

    /** O PMIS não tem título: a lista mostra o protocolo. */
    public function tituloParaExibir(): string
    {
        return $this->titulo ?: 'Manifestação nº ' . ($this->protocolo ?? $this->id);
    }

    public function tipoLabel(): string
    {
        return self::TIPOS[$this->tipo] ?? self::TIPOS['manifestacao'];
    }

    public function scopeDoTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

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

    /** Na manifestação a decisão é acatar ou não a proposta (o PMIS não obriga o chamamento). */
    public function statusLabel(): string
    {
        if (!$this->ehNovaProposta() && $this->decidida()) {
            return $this->status === 'deferida' ? 'Acatada' : 'Não acatada';
        }

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

    /** O que falta para a OSC submeter (vazio = pode): dossiê completo. */
    public function pendenciasParaSubmeter(): array
    {
        $faltam = $this->pendenciasDoPlano();

        // Certidões e documentos institucionais ficam em "Arquivos da OSC", anexados uma vez.
        foreach ($this->osc?->pendenciasDosArquivos() ?? [] as $pendencia) {
            $faltam[] = 'Arquivos da OSC: ' . $pendencia;
        }

        // Na Nova Proposta, comprovantes de experiência e declarações vêm na Celebração (etapa da OSC).
        if (!$this->ehNovaProposta() && $this->documentos()->count() === 0) {
            $faltam[] = 'comprovantes de experiência';
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
