<?php

namespace App\Models;

use App\Models\Concerns\ImpedeExclusaoComVinculos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Proposta extends Model
{
    use ImpedeExclusaoComVinculos;

    public const STATUS = [
        'rascunho'      => 'Rascunho',
        'submetida'     => 'Submetida',
        'em_analise'    => 'Em Análise',
        'em_negociacao' => 'Em Negociação',
        'aprovada'      => 'Aprovada',
        'reprovada'     => 'Reprovada',
        'cancelada'     => 'Cancelada',
    ];

    /**
     * Ver Processo::STATUS_COLORS para a regra (cinza inerte, laranja em
     * andamento, verde positivo, vermelho negativo).
     *
     * Submetida, em análise e em negociação dividem o laranja: são três
     * momentos do mesmo estado — a proposta está com a Administração e espera
     * alguém agir. A paleta da Prefeitura tem duas matizes, então quem separa
     * esses três é o rótulo do selo, não a cor.
     */
    public const STATUS_COLORS = [
        'rascunho'      => 'gray',
        'submetida'     => 'accent',
        'em_analise'    => 'accent',
        'em_negociacao' => 'accent',
        'aprovada'      => 'brand',
        'reprovada'     => 'red',
        'cancelada'     => 'red',
    ];

    protected $fillable = [
        'chamamento_id', 'osc_id', 'titulo', 'objeto', 'justificativa',
        'valor_solicitado', 'valor_proprio',
        'data_inicio_prevista', 'data_fim_prevista',
        'status', 'submitted_at',
        'celebracao_etapa', 'celebracao_setor', 'celebracao_iniciada_em', 'celebracao_concluida_em',
    ];

    protected function casts(): array
    {
        return [
            'data_inicio_prevista'    => 'date',
            'data_fim_prevista'       => 'date',
            'submitted_at'            => 'datetime',
            'valor_solicitado'        => 'decimal:2',
            'valor_proprio'           => 'decimal:2',
            'celebracao_iniciada_em'  => 'datetime',
            'celebracao_concluida_em' => 'datetime',
        ];
    }

    /**
     * Setores que atuam na Celebração — inclui a própria OSC, que envia o plano
     * de trabalho, os documentos de habilitação e os dados bancários.
     */
    public const SETORES_CELEBRACAO = [
        'ug'     => 'Unidade Gestora',
        'osc'    => 'Organização da Sociedade Civil',
        'scp'    => 'Setor de Convênios e Parcerias (SCP)',
        'seplan' => 'Secretaria de Planejamento (SEPLAN)',
        'pj'     => 'Procuradoria Jurídica (PJ)',
    ];

    /**
     * Etapas da Celebração (Fluxo Etapa de Celebração confirmado pelo cliente).
     */
    public const ETAPAS_CELEBRACAO = [
        ['setor' => 'ug',     'acao' => 'Encaminhar o Termo de Homologação e convocar a OSC a apresentar o Plano de Trabalho e os documentos de habilitação'],
        ['setor' => 'osc',    'acao' => 'Elaborar o Plano de Trabalho e anexar os documentos de habilitação'],
        ['setor' => 'ug',     'acao' => 'Analisar e emitir/assinar a Aprovação do Plano de Trabalho'],
        ['setor' => 'scp',    'acao' => 'Analisar e solicitar o Parecer Financeiro à SEPLAN'],
        ['setor' => 'seplan', 'acao' => 'Analisar, elaborar e assinar o Parecer Financeiro'],
        ['setor' => 'ug',     'acao' => 'Anexar as portarias do Gestor e da Comissão de Monitoramento e emitir o Parecer Técnico'],
        ['setor' => 'scp',    'acao' => 'Conferir o processo e emitir/assinar o Protocolo na Unidade Jurídica'],
        ['setor' => 'pj',     'acao' => 'Analisar e emitir/assinar o Parecer Jurídico'],
        ['setor' => 'scp',    'acao' => 'Emitir o Parecer da SCP e o Termo, assinando-o pelo Município'],
        ['setor' => 'osc',    'acao' => 'Assinar o Termo (contra-assinatura da OSC — assinatura das partes)'],
        ['setor' => 'scp',    'acao' => 'Anexar o comprovante de publicação (Diário Oficial e site) e emitir a Autorização de Início de Execução'],
        ['setor' => 'osc',    'acao' => 'Informar os dados bancários da conta específica da parceria'],
        ['setor' => 'scp',    'acao' => 'Elaborar a Ordem de Pagamento Global e encaminhar à UG'],
        ['setor' => 'ug',     'acao' => 'Assinar a Ordem de Pagamento Global'],
        ['setor' => 'scp',    'acao' => 'Anexar o comprovante de empenho global (encerra a Celebração)'],
    ];

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

    // ------------------------------------------------------------------
    // Trâmite da Celebração (UG → OSC → UG → SCP → SEPLAN → … → SCP)
    // ------------------------------------------------------------------

    /** Peças da Celebração (checklist documental desta parceria). */
    public function pecas(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Peca::class, 'pecaable')->orderBy('ordem');
    }

    public function celebracaoTramitacoes(): HasMany
    {
        return $this->hasMany(CelebracaoTramitacao::class)->latest('id');
    }

    /** A Celebração só existe para a proposta aprovada. */
    public function temTramiteCelebracao(): bool
    {
        return $this->status === 'aprovada' || !is_null($this->celebracao_iniciada_em);
    }

    public function celebracaoIniciada(): bool
    {
        return !is_null($this->celebracao_iniciada_em);
    }

    public function celebracaoConcluida(): bool
    {
        return !is_null($this->celebracao_concluida_em);
    }

    public function etapaCelebracaoInfo(?int $i = null): array
    {
        $i ??= (int) $this->celebracao_etapa;

        return self::ETAPAS_CELEBRACAO[$i] ?? ['setor' => $this->celebracao_setor, 'acao' => '—'];
    }

    public function totalEtapasCelebracao(): int
    {
        return count(self::ETAPAS_CELEBRACAO);
    }

    public function ultimaEtapaCelebracao(): bool
    {
        return (int) $this->celebracao_etapa >= $this->totalEtapasCelebracao() - 1;
    }

    public function podeAvancarCelebracao(): bool
    {
        return $this->temTramiteCelebracao()
            && !$this->celebracaoConcluida()
            && !$this->ultimaEtapaCelebracao();
    }

    public function setorAnteriorCelebracao(): ?string
    {
        return (int) $this->celebracao_etapa > 0
            ? (self::ETAPAS_CELEBRACAO[$this->celebracao_etapa - 1]['setor'] ?? null)
            : null;
    }

    public function pecaCelebracao(string $chave): ?Peca
    {
        return $this->relationLoaded('pecas')
            ? $this->pecas->firstWhere('chave', $chave)
            : $this->pecas()->where('chave', $chave)->first();
    }

    /**
     * Peças que precisam estar prontas antes de encaminhar a etapa atual.
     * A Ordem de Pagamento Global é apenas emitida pela SCP na etapa 11 — a
     * assinatura é da UG, na etapa 12.
     */
    public function pendenciasCelebracao(): array
    {
        $pend  = [];
        $etapa = (int) $this->celebracao_etapa;

        foreach (Peca::CELEBRACAO_ETAPA as $chave => $etapaPeca) {
            if ($etapaPeca !== $etapa) {
                continue;
            }

            $peca = $this->pecaCelebracao($chave);
            if (!$peca || !$peca->obrigatorio) {
                continue;
            }

            if ($peca->tipo === 'modelo') {
                // A OP Global é apenas elaborada pela SCP na etapa 12 — a
                // assinatura é da UG, na etapa 13.
                $soPreencher = $chave === 'op_global' && $etapa === 12;
                $ok = $soPreencher ? !empty($peca->conteudo) : $peca->assinado();
                if (!$ok) {
                    $pend[] = $peca->rotulo . ($soPreencher ? ' (emitir)' : ' (assinar)');
                }
            } elseif (!$peca->temArquivo()) {
                $pend[] = $peca->rotulo . ' (anexar arquivo)';
            }
        }

        // Etapa 9: assinatura das partes — a OSC contra-assina o Termo.
        if ($etapa === 9 && !$this->pecaCelebracao('termo')?->contraAssinado()) {
            $pend[] = 'Termo de Parceria (contra-assinatura da OSC)';
        }

        // Etapa 13: a UG assina a OP Global elaborada pela SCP.
        if ($etapa === 13 && !$this->pecaCelebracao('op_global')?->assinado()) {
            $pend[] = 'Ordem de Pagamento Global (assinar)';
        }

        return $pend;
    }

    // Interface uniforme de trâmite, usada pelo motor de peças (ver Peca).
    public function tramiteEtapaAtual(): int
    {
        return (int) $this->celebracao_etapa;
    }

    public function tramiteEncerrado(): bool
    {
        return $this->celebracaoConcluida();
    }

    public function tramiteSetorLabel(?string $setor): string
    {
        return self::SETORES_CELEBRACAO[$setor] ?? (string) $setor;
    }

    protected function vinculosBloqueantes(): array
    {
        return [
            'instrumento' => ['instrumento celebrado', 'instrumentos celebrados'],
        ];
    }

    protected function fraseDeBloqueio(): string
    {
        return 'Esta proposta não pode ser excluída';
    }
}
