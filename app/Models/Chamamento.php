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
        'selecao_etapa', 'selecao_setor', 'selecao_concluida_em', 'status_antes_cancelar', 'prazo_recurso_ate',
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
            'prazo_recurso_ate'     => 'date',
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
     * Etapa 3 (índice 2), "Recurso e resposta ao recurso" (decisão da gestão,
     * 29/09/2026): a OSC recorre no prazo do edital, com um arquivo; a
     * Comissão de Seleção pode emitir a Resposta ao recurso, peça opcional.
     * Antes o recurso corria junto com a redação do Resultado Definitivo.
     */
    public const ETAPA_PRAZO_RECURSO = 2;

    /**
     * Etapas do trâmite da Seleção (Fluxo Seleção confirmado pelo cliente).
     * Só se aplica ao Chamamento Público — Dispensa/Inexigibilidade não tem
     * julgamento de propostas nem recurso.
     */
    public const ETAPAS_SELECAO = [
        ['setor' => 'ug',  'acao' => 'Analisar as propostas: emitir o Relatório da Comissão, a Ata e o Resultado Provisório (assinar) e encaminhar à SCP'],
        ['setor' => 'scp', 'acao' => 'Anexar o comprovante de publicação do Resultado Provisório, informar o prazo de recurso do edital e devolver à UG'],
        ['setor' => 'ug',  'acao' => 'Recurso e resposta ao recurso: as OSCs podem recorrer do Resultado Provisório até a data do edital; a Comissão de Seleção pode emitir a Resposta ao recurso (opcional); findo o prazo, encerrar a etapa'],
        ['setor' => 'ug',  'acao' => 'Emitir o Resultado Definitivo (assinar) e encaminhar à SCP'],
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

    /**
     * Publicado, mas fora do período de inscrição por já ter passado do fim —
     * distingue de "ainda não começou", que usa o mesmo status_efetivo.
     */
    public function inscricaoEncerrada(): bool
    {
        return $this->data_fim_inscricao !== null && $this->data_fim_inscricao->isPast();
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
     * A OSC pode protocolar recurso agora? Só na etapa do prazo de recurso e
     * até o último dia dele, que vem do edital (a SCP o informa ao publicar o
     * Resultado Provisório). Recorrer é opcional: sem recurso, a etapa só passa.
     */
    public function faseRecursalAberta(): bool
    {
        return $this->naEtapaDaSelecao(self::ETAPA_PRAZO_RECURSO)
            && $this->prazo_recurso_ate !== null
            && !$this->prazo_recurso_ate->endOfDay()->isPast();
    }

    /** O prazo de recurso já acabou (o último dia inteiro já passou)? */
    public function prazoRecursalEncerrado(): bool
    {
        return $this->prazo_recurso_ate !== null && $this->prazo_recurso_ate->endOfDay()->isPast();
    }

    private function naEtapaDaSelecao(int $etapa): bool
    {
        return !$this->cancelado()
            && $this->temTramiteSelecao()
            && !$this->selecaoConcluida()
            && (int) $this->selecao_etapa === $etapa;
    }

    // ------------------------------------------------------------------
    // Cancelamento (decisão da gestão, 28/09/2026)
    // ------------------------------------------------------------------

    /**
     * Cancelado: não recebe inscrição nem recurso, a Seleção não anda e ninguém
     * assina peça dele — mas nada é excluído, e a UG pode reabrir. Ver
     * ChamamentoCancelamentoController.
     */
    public function cancelado(): bool
    {
        return $this->status === 'cancelado';
    }

    public function cancelamentos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ChamamentoCancelamento::class)->latest('id');
    }

    /** Quem cancela e reabre: a Unidade Gestora da Secretaria dona do chamamento. */
    public function geridoPelaUg(?User $user): bool
    {
        return $user !== null
            && $user->setorNoTramite() === 'ug'
            && $user->can('chamamentos')
            && $user->orgao_id !== null
            && $user->orgao_id === $this->programa?->orgao_id;
    }

    // ------------------------------------------------------------------
    // Prorrogação do prazo de inscrições (decisão da gestão, 28/09/2026)
    // ------------------------------------------------------------------

    public function prorrogacoes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ChamamentoProrrogacao::class)->latest('id');
    }

    /** Quem prorroga: a SCP, que conduz o chamamento para o Município inteiro. */
    /**
     * Quem mexe no cadastro do chamamento — criar, editar, remover: só a SCP
     * (decisão da gestão, 29/09/2026). A UG segue com a Seleção e com o botão
     * de cancelar, que têm rotas próprias; o que sai dela é a edição direta de
     * datas, número, objeto e situação.
     */
    public function cadastroEditavelPor(?User $user): bool
    {
        return self::cadastroPermitidoA($user);
    }

    public static function cadastroPermitidoA(?User $user): bool
    {
        return $user !== null && $user->setorNoTramite() === 'scp' && $user->can('chamamentos');
    }

    public function prorrogavelPor(?User $user): bool
    {
        return $user !== null && $user->setorNoTramite() === 'scp' && $user->can('chamamentos');
    }

    /**
     * Por que o prazo não pode ser prorrogado agora; null se pode.
     *
     * Só chamamento público publicado, não cancelado, e com a Seleção ainda na
     * etapa 1: depois dela o julgamento já andou, e reabrir inscrições mudaria
     * um resultado em curso.
     */
    public function motivoParaNaoProrrogar(): ?string
    {
        return match (true) {
            $this->tipo !== 'chamamento_publico' => 'Só chamamento público tem prazo de inscrições.',
            $this->cancelado()                   => 'Este chamamento está cancelado.',
            $this->status !== 'publicado'        => 'Só chamamento publicado tem o prazo de inscrições prorrogado.',
            $this->selecaoConcluida() || (int) $this->selecao_etapa > 0
                => 'A Seleção já passou da análise das propostas: o prazo de inscrições não pode mais ser prorrogado.',
            default => null,
        };
    }

    /** O último cancelamento — o que vale enquanto o chamamento estiver cancelado. */
    public function ultimoCancelamento(): ?ChamamentoCancelamento
    {
        return $this->cancelamentos()->where('acao', 'cancelado')->first();
    }

    /**
     * Por que não dá para cancelar agora; null se dá.
     *
     * Até a Seleção ser homologada. Depois dela já há parceria em Celebração com
     * a OSC vencedora, e desfazer isso é outro ato. Na dispensa, que não tem
     * homologação, o marco é o mesmo: proposta aprovada ou Celebração iniciada.
     */
    public function motivoParaNaoCancelar(): ?string
    {
        if ($this->cancelado()) {
            return 'Este chamamento já está cancelado.';
        }

        if ($this->selecaoConcluida()) {
            return 'A Seleção já foi homologada: há parceria em Celebração, e o chamamento não pode mais ser cancelado.';
        }

        if ($this->propostas()->where(fn ($q) => $q->where('status', 'aprovada')->orWhereNotNull('celebracao_iniciada_em'))->exists()) {
            return 'Já há proposta aprovada ou em Celebração neste chamamento, e ele não pode mais ser cancelado.';
        }

        return null;
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

    /** Documentos que quem devolve pode marcar como errados (ver App\Support\Devolucao). */
    public function documentosDevolviveis(): \Illuminate\Support\Collection
    {
        return \App\Support\Devolucao::candidatas($this->pecas()->get(), (int) $this->selecao_etapa);
    }

    // Interface uniforme de trâmite, usada pelo motor de peças (ver Peca).
    public function tramiteEtapaAtual(): int
    {
        return (int) $this->selecao_etapa;
    }

    /** As etapas do trâmite, na ordem — cada uma ['setor' => ..., 'acao' => ...]. */
    public function tramiteEtapas(): array
    {
        return self::ETAPAS_SELECAO;
    }

    public function tramiteEncerrado(): bool
    {
        return $this->selecaoConcluida();
    }

    public function tramiteSetorLabel(?string $setor): string
    {
        // Setores de fora deste trâmite (a PJ, por exemplo, que atua nas peças
        // da fase do edital) não estão no mapa: cai na lotação, senão a tela
        // escreveria a sigla crua — "o setor pj".
        return self::SETORES_SELECAO[$setor]
            ?? (User::LOTACOES[$setor] ?? strtoupper((string) $setor));
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
        return !$this->cancelado() && $this->temTramiteSelecao() && !$this->selecaoConcluida() && !$this->ultimaEtapaSelecao();
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
            2 => [],  // a Resposta ao recurso é opcional
            3 => ['resultado_definitivo'],
            4 => ['pub_resultado_definitivo', 'termo_homologacao'],
            5 => ['termo_homologacao'],
        ];

        // Prazo de recurso: a etapa só se encerra depois do último dia dele —
        // encerrar antes tiraria da OSC um prazo que o edital lhe deu.
        if ($etapa === self::ETAPA_PRAZO_RECURSO) {
            if ($this->prazo_recurso_ate === null) {
                $pend[] = 'Data final do prazo de recurso (a SCP a informa ao publicar o Resultado Provisório)';
            } elseif (!$this->prazoRecursalEncerrado()) {
                $pend[] = 'Prazo de recurso aberto até ' . $this->prazo_recurso_ate->format('d/m/Y') . ' — a etapa se encerra depois dele';
            }
        }


        foreach ($exigidas[$etapa] ?? [] as $chave) {
            $peca = $this->pecaSelecao($chave);
            if (!$peca) {
                continue;
            }

            // Modelo: precisa estar assinado — exceto o Termo, que a SCP
            // preenche na etapa 4 e o Prefeito só assina, na 5, sem editar.
            // Preenchido quer dizer redigido pela SCP: o texto do modelo, como
            // foi semeado, não vai ao Gabinete (decisão da gestão, 29/09/2026).
            if ($peca->tipo === 'modelo') {
                $soPreencher = $chave === 'termo_homologacao' && $etapa === 4;
                $ok = $soPreencher ? !empty($peca->conteudo) && !$peca->aindaEOModelo() && !$peca->devolvida() : $peca->assinado();
                if (!$ok) {
                    $pend[] = $peca->rotulo . ($soPreencher ? ' (preencher antes de enviar ao Gabinete)' : ' (assinar)');
                }
            } elseif (!$peca->temArquivo() || $peca->devolvida()) {
                $pend[] = $peca->rotulo . ($peca->devolvida() ? ' (devolvido — enviar o arquivo corrigido)' : ' (anexar arquivo)');
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
