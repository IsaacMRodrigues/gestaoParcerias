<?php

namespace App\Models;

use App\Models\Concerns\ImpedeExclusaoComVinculos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Chamamento extends Model
{
    use ImpedeExclusaoComVinculos;

    public const TIPOS = [
        'chamamento_publico' => 'Chamamento Público',
        'dispensa'           => 'Dispensa de Chamamento',
        'inexigibilidade'    => 'Inexigibilidade de Chamamento',
    ];

    /**
     * Mesmas cores de Processo::MODALIDADES_COLORS — é a mesma categoria vista
     * do outro lado do fluxo, e a cor tem de bater nas duas telas.
     */
    public const TIPOS_COLORS = [
        'chamamento_publico' => 'brand',
        'dispensa'           => 'accent',
        'inexigibilidade'    => 'slate',
    ];

    public const STATUS = [
        'rascunho'     => 'Rascunho',
        'publicado'    => 'Publicado',
        'em_inscricao' => 'Em Inscrição',
        'em_analise'   => 'Em Análise',
        'encerrado'    => 'Encerrado',
        'cancelado'    => 'Cancelado',
    ];

    /**
     * Ver Processo::STATUS_COLORS para a regra. 'em_inscricao' é o único verde
     * vivo — é o estado em que o chamamento está de fato aberto ao público —, e
     * 'encerrado' recua para o cinza, porque já saiu de cena.
     */
    public const STATUS_COLORS = [
        'rascunho'     => 'gray',
        'publicado'    => 'accent',
        'em_inscricao' => 'brand',
        'em_analise'   => 'accent',
        'encerrado'    => 'slate',
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

    public function recursos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Recurso::class)->latest('id');
    }

    /**
     * A fase recursal está aberta? Vale a partir da publicação do resultado
     * provisório (etapa 1 concluída) e até a UG emitir o resultado definitivo
     * (fim da etapa 2) — é a janela em que a OSC pode protocolar o recurso.
     */
    public function faseRecursalAberta(): bool
    {
        return $this->temTramiteSelecao()
            && !$this->selecaoConcluida()
            && (int) $this->selecao_etapa === 2;
    }

    /** Recursos protocolados que ainda não têm resposta da UG. */
    public function recursosSemResposta(): int
    {
        return $this->relationLoaded('recursos')
            ? $this->recursos->whereNull('respondido_em')->count()
            : $this->recursos()->whereNull('respondido_em')->count();
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

    // Interface uniforme de trâmite, usada pelo motor de peças (ver Peca).
    public function tramiteEtapaAtual(): int
    {
        return (int) $this->selecao_etapa;
    }

    public function tramiteEncerrado(): bool
    {
        return $this->selecaoConcluida();
    }

    public function tramiteSetorLabel(?string $setor): string
    {
        return self::SETORES_SELECAO[$setor] ?? (string) $setor;
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

        // Etapa 2: todo recurso protocolado precisa de resposta antes do
        // resultado definitivo (Fluxo Seleção: "analisa os recursos … emite resposta").
        if ($etapa === 2 && ($semResposta = $this->recursosSemResposta()) > 0) {
            $pend[] = $semResposta === 1
                ? '1 recurso sem resposta'
                : "{$semResposta} recursos sem resposta";
        }

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

    protected function vinculosBloqueantes(): array
    {
        return [
            'propostas' => ['proposta de OSC', 'propostas de OSC'],
        ];
    }

    protected function fraseDeBloqueio(): string
    {
        return 'Este chamamento não pode ser excluído';
    }
}
