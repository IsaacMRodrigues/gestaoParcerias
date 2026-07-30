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
        'selecao_etapa', 'selecao_setor', 'selecao_concluida_em',
    ];

    protected function casts(): array
    {
        return [
            'data_publicacao'       => 'date',
            'data_inicio_inscricao' => 'date',
            'data_fim_inscricao'    => 'date',
            'data_resultado'        => 'date',
            'valor_disponivel'      => 'decimal:2',
            'selecao_concluida_em'  => 'datetime',
        ];
    }

    /**
     * Setores que atuam na Seleção. Além dos setores do trâmite do Processo,
     * entra o Gabinete do Prefeito (PM), que assina a homologação.
     */
    public const SETORES_SELECAO = [
        'ug'  => 'Unidade Gestora',
        'scp' => 'Setor de Convênios e Parcerias (SCP)',
        'pm'  => 'Gabinete do Prefeito (PM)',
    ];

    /**
     * Etapas do trâmite da Seleção (Fluxo Seleção confirmado pelo cliente).
     * Só se aplica ao Chamamento Público — Dispensa/Inexigibilidade não tem
     * julgamento de propostas nem recurso.
     */
    public const ETAPAS_SELECAO = [
        ['setor' => 'ug',  'acao' => 'Analisar as propostas: emitir o Relatório da Comissão, a Ata e o Resultado Provisório (assinar) e encaminhar à SCP'],
        ['setor' => 'scp', 'acao' => 'Anexar o comprovante de publicação do Resultado Provisório e devolver à UG'],
        ['setor' => 'ug',  'acao' => 'Analisar os recursos (se houver) e emitir o Resultado Definitivo (assinar), encaminhando à SCP'],
        ['setor' => 'scp', 'acao' => 'Anexar o comprovante de publicação do Resultado Definitivo e emitir o Termo de Adjudicação e Homologação'],
        ['setor' => 'pm',  'acao' => 'Assinar o Termo de Adjudicação e Homologação (encerra a Seleção)'],
    ];

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

    // ------------------------------------------------------------------
    // Trâmite da Seleção (Fluxo Seleção: UG → SCP → UG → SCP → Prefeito)
    // ------------------------------------------------------------------

    public function selecaoTramitacoes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SelecaoTramitacao::class)->latest('id');
    }

    /** O trâmite da Seleção só existe no Chamamento Público. */
    public function temTramiteSelecao(): bool
    {
        return $this->tipo === 'chamamento_publico';
    }

    public function selecaoConcluida(): bool
    {
        return !is_null($this->selecao_concluida_em);
    }

    public function etapaSelecaoInfo(?int $i = null): array
    {
        $i ??= (int) $this->selecao_etapa;

        return self::ETAPAS_SELECAO[$i] ?? ['setor' => $this->selecao_setor, 'acao' => '—'];
    }

    public function totalEtapasSelecao(): int
    {
        return count(self::ETAPAS_SELECAO);
    }

    public function ultimaEtapaSelecao(): bool
    {
        return (int) $this->selecao_etapa >= $this->totalEtapasSelecao() - 1;
    }

    public function podeAvancarSelecao(): bool
    {
        return $this->temTramiteSelecao() && !$this->selecaoConcluida() && !$this->ultimaEtapaSelecao();
    }

    public function setorAnteriorSelecao(): ?string
    {
        return (int) $this->selecao_etapa > 0
            ? (self::ETAPAS_SELECAO[$this->selecao_etapa - 1]['setor'] ?? null)
            : null;
    }

    /**
     * Peças que precisam estar prontas antes de encaminhar a etapa atual da
     * Seleção. Retorna os rótulos pendentes (vazio = pode encaminhar).
     */
    public function pendenciasSelecao(): array
    {
        $pend  = [];
        $etapa = (int) $this->selecao_etapa;

        // As peças exigidas em cada etapa, conforme o Fluxo Seleção.
        $exigidas = [
            0 => ['relatorio_comissao', 'ata_comissao', 'resultado_parcial'],
            1 => ['pub_resultado_parcial'],
            2 => ['resultado_definitivo'],
            3 => ['pub_resultado_definitivo', 'termo_homologacao'],
            4 => ['termo_homologacao'],
        ];

        foreach ($exigidas[$etapa] ?? [] as $chave) {
            $peca = $this->pecaSelecao($chave);
            if (!$peca) {
                continue;
            }

            // Modelo: precisa estar assinado — exceto o Termo, que a SCP só
            // emite na etapa 3 (a assinatura é do Prefeito, na etapa 4).
            if ($peca->tipo === 'modelo') {
                $soPreencher = $chave === 'termo_homologacao' && $etapa === 3;
                $ok = $soPreencher ? !empty($peca->conteudo) : $peca->assinado();
                if (!$ok) {
                    $pend[] = $peca->rotulo . ($soPreencher ? ' (emitir)' : ' (assinar)');
                }
            } elseif (!$peca->temArquivo()) {
                $pend[] = $peca->rotulo . ' (anexar arquivo)';
            }
        }

        return $pend;
    }

    /** Peça da Seleção por chave (usa a coleção já carregada quando houver). */
    public function pecaSelecao(string $chave): ?Peca
    {
        return $this->relationLoaded('pecas')
            ? $this->pecas->firstWhere('chave', $chave)
            : $this->pecas()->where('chave', $chave)->first();
    }
}
