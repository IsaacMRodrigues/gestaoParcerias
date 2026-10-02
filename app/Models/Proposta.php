<?php

namespace App\Models;

use App\Models\Concerns\ImpedeExclusaoComVinculos;
use App\Models\Concerns\TemPlanoDeTrabalho;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Proposta extends Model
{
    use ImpedeExclusaoComVinculos;
    use TemPlanoDeTrabalho;

    public function chavePlano(): string
    {
        return 'proposta_id';
    }

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
     * Ver Processo::STATUS_COLORS. Submetida, em análise e em negociação dividem o laranja
     * (a proposta espera a Administração); quem os separa é o rótulo.
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

    /** Recurso da OSC contra o resultado provisório do chamamento (um por OSC). */
    public function recursos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Recurso::class)->latest('id');
    }

    protected $fillable = [
        'chamamento_id', 'osc_id', 'titulo', 'objeto', 'justificativa',
        'descricao_realidade', 'publico_alvo', 'objetivos', 'objetivos_especificos', 'metodologia',
        'valor_solicitado', 'valor_proprio', 'valor_outras_fontes',
        'data_inicio_prevista', 'data_fim_prevista', 'vigencia_dias',
        'atuacao_rede', 'rede_cnpj', 'rede_razao_social', 'rede_municipio', 'rede_data_termo',
        'status', 'submitted_at',
        'celebracao_etapa', 'celebracao_setor', 'celebracao_partes_concluidas', 'celebracao_gestor_id', 'celebracao_iniciada_em', 'celebracao_concluida_em',
    ];

    protected function casts(): array
    {
        return [
            'celebracao_partes_concluidas' => 'array',
            'data_inicio_prevista'    => 'date',
            'data_fim_prevista'       => 'date',
            'submitted_at'            => 'datetime',
            'valor_solicitado'        => 'decimal:2',
            'valor_proprio'           => 'decimal:2',
            'valor_outras_fontes'     => 'decimal:2',
            'atuacao_rede'            => 'boolean',
            'rede_data_termo'         => 'date',
            'celebracao_iniciada_em'  => 'datetime',
            'celebracao_concluida_em' => 'datetime',
        ];
    }

    /** Setores que atuam na Celebração, incluindo a própria OSC. */
    public const SETORES_CELEBRACAO = [
        'ug'     => 'Unidade Gestora',
        'osc'    => 'Organização da Sociedade Civil',
        'scp'    => 'Setor de Convênios e Parcerias (SCP)',
        'seplan' => 'Secretaria de Planejamento (SEPLAN)',
        'pj'     => 'Procuradoria Jurídica (PJ)',
        // O Gestor da Parceria é uma pessoa, escolhida pela SCP.
        'gestor' => 'Gestor da Parceria',
        'pm'     => 'Gabinete do Prefeito',
    ];

    /** Etapas da Celebração. */
    public const ETAPAS_CELEBRACAO = [
        ['setor' => 'ug',     'acao' => 'Encaminhar o Termo de Homologação e convocar a OSC a apresentar o Plano de Trabalho e os documentos de habilitação'],
        ['setor' => 'osc',    'acao' => 'Elaborar o Plano de Trabalho e anexar os documentos de habilitação'],
        // Etapa conjunta: vai à UG e à SCP ao mesmo tempo e só avança quando as duas concluírem.
        // 'setor' é o principal, para o que só entende um setor por etapa.
        ['setor' => 'ug', 'setores' => ['ug', 'scp'], 'acao' => 'Em paralelo — UG: analisar e emitir/assinar a Aprovação do Plano de Trabalho; SCP: analisar o Plano de Trabalho e os documentos de habilitação'],
        ['setor' => 'scp',    'acao' => 'Analisar e solicitar o Parecer Financeiro à SEPLAN'],
        ['setor' => 'seplan', 'acao' => 'Analisar, elaborar e assinar o Parecer Financeiro'],
        ['setor' => 'ug',     'acao' => 'Anexar as portarias do Gestor e da Comissão de Monitoramento e emitir o Parecer Técnico'],
        ['setor' => 'scp',    'acao' => 'Conferir o processo, emitir a Minuta do Termo e a Certidão de Autuação e emitir/assinar o Protocolo na Unidade Jurídica'],
        ['setor' => 'pj',     'acao' => 'Analisar e emitir/assinar o Parecer Jurídico'],
        // O Termo é assinado em sequência (ver Peca::ASSINATURAS_EM_SEQUENCIA); entre uma
        // assinatura e outra, volta à SCP.
        ['setor' => 'scp',    'acao' => 'Emitir o Parecer da SCP e o Termo (sem assinar o Termo) e encaminhar à OSC'],
        ['setor' => 'osc',    'acao' => 'Assinar o Termo e devolver à SCP'],
        ['setor' => 'scp',    'acao' => 'Conferir a assinatura da OSC e encaminhar o Termo à UG'],
        ['setor' => 'ug',     'perfil' => 'responsavel_unidade_gestora', 'acao' => 'Responsável da UG: assinar o Termo e devolver à SCP'],
        ['setor' => 'scp',    'acao' => 'Escolher o Gestor da Parceria e encaminhar o Termo a ele'],
        ['setor' => 'gestor', 'acao' => 'Gestor da Parceria: assinar o Termo e devolver à SCP'],
        ['setor' => 'scp',    'acao' => 'Encaminhar o Termo ao Gabinete'],
        ['setor' => 'pm',     'acao' => 'Gabinete: fazer a última assinatura do Termo e devolver à SCP'],
        ['setor' => 'scp',    'acao' => 'Anexar o comprovante de publicação (Diário Oficial e site) e emitir a Autorização de Início de Execução'],
        ['setor' => 'osc',    'acao' => 'Informar os dados bancários da conta específica da parceria'],
        // A OP Global com duas assinaturas, nesta ordem: o Gestor da Parceria e o Responsável da UG.
        ['setor' => 'scp',    'acao' => 'Elaborar a Ordem de Pagamento Global e encaminhar ao Gestor da Parceria'],
        ['setor' => 'gestor', 'acao' => 'Gestor da Parceria: assinar a Ordem de Pagamento Global'],
        ['setor' => 'ug',     'perfil' => 'responsavel_unidade_gestora', 'acao' => 'Responsável da UG: assinar a Ordem de Pagamento Global'],
        ['setor' => 'scp',    'acao' => 'Anexar o comprovante de empenho global (encerra a Celebração)'],
    ];

    public function chamamento(): BelongsTo
    {
        return $this->belongsTo(Chamamento::class);
    }

    /** Propostas visíveis ao usuário: a UG vê as do seu órgão; admin, auditoria e transversais, todas. */
    public function scopeVisiveisPara($query, User $user)
    {
        if ($user->podeVerTodosOrgaos()) {
            return $query;
        }

        return $query->whereHas('chamamento.programa', fn ($q) => $q->where('orgao_id', $user->orgao_id));
    }

    /**
     * Esta parceria é visível ao usuário? A versão de scopeVisiveisPara() para um registro,
     * mais o lado da OSC. Aplicada pelo middleware ParceriaVisivel.
     */
    public function visivelPara(User $user): bool
    {
        if ($user->ehRepresentanteOsc()) {
            return $this->osc_id === $user->osc_id;
        }

        if (!$user->temAcessoInterno()) {
            return false;
        }

        if ($user->podeVerTodosOrgaos()) {
            return true;
        }

        $orgao = $this->chamamento?->programa?->orgao_id;

        return $orgao !== null && $orgao === $user->orgao_id;
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

    /**
     * O processo inteiro como a OSC o vê: o que está pronto e marcado como visível, das
     * fases a partir da Seleção. O Planejamento é interno e não entra.
     *
     * @return array<string, \Illuminate\Support\Collection>
     */
    public function dossieParaOsc(): array
    {
        $pronta = fn ($p) => $p->assinado() || (method_exists($p, 'temArquivo') && $p->temArquivo());
        $abertas = fn ($colecao) => collect($colecao)
            ->filter(fn ($p) => $p->visivel_osc)
            ->reject(fn ($p) => $p instanceof Peca && $p->vemDoPlanejamento())
            ->filter($pronta)
            ->values();

        $chamamento = $this->chamamento;

        $grupos = [
            'Seleção'      => $abertas($chamamento?->pecas ?? []),
            'Celebração'   => $abertas($this->pecas),
            'Execução'     => $abertas($this->instrumento?->pecas ?? []),
        ];

        return array_filter($grupos, fn ($g) => $g->isNotEmpty());
    }

    /**
     * Tudo o que pode ser aberto ou fechado à OSC nesta parceria, pronto ou não
     * (a lista da tela de curadoria).
     *
     * @return array<string, \Illuminate\Support\Collection>
     */
    public function dossieParaCuradoria(): array
    {
        $chamamento = $this->chamamento;

        // Sem o Planejamento e sem o que o puxa: não há o que decidir sobre o
        // que é interno por regra (ver dossieParaOsc).
        $grupos = [
            'Seleção'      => collect($chamamento?->pecas ?? [])->reject(fn ($p) => $p->vemDoPlanejamento())->values(),
            'Celebração'   => collect($this->pecas),
            'Execução'     => collect($this->instrumento?->pecas ?? []),
        ];

        return array_filter($grupos, fn ($g) => $g->isNotEmpty());
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

    /** A OSC ainda pode apresentar documentos? Até o caminho acabar (inclusive na Celebração). */
    public function aceitaDocumentosDaOsc(): bool
    {
        return !in_array($this->status, ['reprovada', 'cancelada'], true);
    }

    /** A Celebração só existe para a proposta aprovada. */
    public function temTramiteCelebracao(): bool
    {
        return $this->status === 'aprovada' || !is_null($this->celebracao_iniciada_em);
    }

    /** Versão em consulta de temTramiteCelebracao() — para a listagem do trâmite. */
    public function scopeComTramiteCelebracao($query)
    {
        return $query->where(fn ($q) => $q->where('status', 'aprovada')
            ->orWhereNotNull('celebracao_iniciada_em'));
    }

    /**
     * Plano de trabalho aberto à edição na Celebração (OSC e UG), até o documento do plano
     * ser assinado. Depois, só por Alteração.
     */
    public function planoAbertoNaCelebracao(): bool
    {
        if (!$this->temTramiteCelebracao() || $this->celebracaoConcluida()) {
            return false;
        }

        $documento = $this->pecas()->where('categoria', 'celebracao')->where('chave', 'plano_trabalho')->first();

        // A peça antiga, de quando o plano era anexo, congela com o arquivo.
        return !$documento || !($documento->assinado() || ($documento->tipo === 'arquivo' && $documento->temArquivo()));
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

    /** Os setores de uma etapa da Celebração — dois, na etapa conjunta. */
    public static function setoresDaEtapaCelebracao(int $etapa): array
    {
        $def = self::ETAPAS_CELEBRACAO[$etapa] ?? null;

        return $def ? ($def['setores'] ?? [$def['setor']]) : [];
    }

    /** As etapas em que o setor atua em conjunto com outro. */
    public static function etapasConjuntasDoSetor(?string $setor): array
    {
        return collect(self::ETAPAS_CELEBRACAO)
            ->filter(fn ($e) => isset($e['setores']) && in_array($setor, $e['setores'], true))
            ->keys()->all();
    }

    public function etapaConjuntaCelebracao(): bool
    {
        return count(self::setoresDaEtapaCelebracao((int) $this->celebracao_etapa)) > 1;
    }

    /** Setores da etapa atual que ainda não concluíram a sua parte. */
    public function setoresComAVezNaCelebracao(): array
    {
        if (!$this->temTramiteCelebracao() || $this->celebracaoConcluida()) {
            return [];
        }

        if (!$this->etapaConjuntaCelebracao()) {
            return array_filter([$this->celebracao_setor]);
        }

        return array_values(array_diff(
            self::setoresDaEtapaCelebracao((int) $this->celebracao_etapa),
            $this->celebracao_partes_concluidas ?? [],
        ));
    }

    public function setorTemAVezNaCelebracao(?string $setor): bool
    {
        return $setor !== null && in_array($setor, $this->setoresComAVezNaCelebracao(), true);
    }

    /**
     * A pessoa tem a vez na Celebração agora? O setor e, quando a etapa pede, o Gestor escolhido
     * ou o perfil exigido (da Secretaria da parceria).
     */
    public function usuarioTemAVezNaCelebracao(?User $user): bool
    {
        if (!$user || !$this->temTramiteCelebracao() || $this->celebracaoConcluida()) {
            return false;
        }

        $etapa = self::ETAPAS_CELEBRACAO[(int) $this->celebracao_etapa] ?? null;

        if (($etapa['setor'] ?? null) === 'gestor') {
            return $this->celebracao_gestor_id !== null && (int) $this->celebracao_gestor_id === (int) $user->id;
        }

        if (!$this->setorTemAVezNaCelebracao($user->setorNoTramite())) {
            return false;
        }

        if ($perfil = $etapa['perfil'] ?? null) {
            return $user->hasRole($perfil) && $this->visivelPara($user);
        }

        return true;
    }

    public function gestorDaCelebracao(): BelongsTo
    {
        return $this->belongsTo(User::class, 'celebracao_gestor_id');
    }

    /** Gestores da Parceria que a SCP pode escolher: o perfil, ativos, da Secretaria da parceria. */
    public function gestoresElegiveis(): \Illuminate\Support\Collection
    {
        $orgao = $this->chamamento?->programa?->orgao_id;

        return User::role('gestor_parceria')->where('status', true)->where('orgao_id', $orgao)->orderBy('name')->get();
    }

    public function podeAvancarCelebracao(): bool
    {
        return $this->temTramiteCelebracao()
            && !$this->celebracaoConcluida()
            && !$this->ultimaEtapaCelebracao();
    }

    public function pecaCelebracao(string $chave): ?Peca
    {
        return $this->relationLoaded('pecas')
            ? $this->pecas->firstWhere('chave', $chave)
            : $this->pecas()->where('chave', $chave)->first();
    }

    /**
     * Pendências para encaminhar a etapa atual da Celebração.
     *
     * @param string|null $setor na etapa conjunta, só as da parte deste setor.
     */
    public function pendenciasCelebracao(?string $setor = null): array
    {
        $pend  = [];
        $etapa = (int) $this->celebracao_etapa;

        foreach (Peca::CELEBRACAO_ETAPA as $chave => $etapaPeca) {
            if ($etapaPeca !== $etapa) {
                continue;
            }

            if ($setor !== null && (Peca::CELEBRACAO_SETOR[$chave] ?? null) !== $setor) {
                continue;
            }

            $peca = $this->pecaCelebracao($chave);
            if (!$peca || !$peca->obrigatorio) {
                continue;
            }

            // A Minuta do Termo não se assina: basta estar redigida.
            if ($peca->semAssinatura()) {
                if (!$peca->redigida() || $peca->devolvida()) {
                    $pend[] = $peca->rotulo . ' (preencher)';
                }

                continue;
            }

            // Assinado em sequência (o Termo, a OP Global): na etapa dele a SCP
            // só o emite; as assinaturas são cobradas nas etapas de cada parte.
            if ($peca->temAssinaturasEmSequencia()) {
                if (empty($peca->conteudo) || $peca->devolvida()) {
                    $pend[] = $peca->rotulo . ' (emitir)';
                }

                continue;
            }

            if ($peca->tipo === 'modelo') {
                if (!$peca->assinado()) {
                    $pend[] = $peca->rotulo . ' (assinar)';
                }
            } elseif (!$peca->temArquivo() || $peca->devolvida()) {
                $pend[] = $peca->rotulo . ($peca->devolvida() ? ' (devolvido — enviar o arquivo corrigido)' : ' (anexar arquivo)');
            }
        }

        // Etapa 2 (da OSC): a área "Arquivos da OSC" completa e em dia — as
        // certidões e as declarações saíram do checklist para lá.
        if ($etapa === 1 && ($setor === null || $setor === 'osc')) {
            foreach ($this->osc?->pendenciasDosArquivos() ?? [] as $pendencia) {
                $pend[] = 'Arquivos da OSC: ' . $pendencia;
            }
        }

        // Etapas de assinatura dos documentos em sequência — o Termo (OSC, UG,
        // Gestor, Gabinete) e a OP Global (Gestor, UG): a da vez.
        foreach (array_keys(Peca::ASSINATURAS_EM_SEQUENCIA['celebracao'] ?? []) as $chave) {
            $doc = $this->pecaCelebracao($chave);
            foreach ($doc?->sequenciaDeAssinaturas() ?? [] as $papel => $regra) {
                if ($regra['etapa'] === $etapa && !$doc->assinaturaDe($papel)) {
                    $pend[] = $doc->rotulo . ' (assinatura: ' . $regra['rotulo'] . ')';
                }
            }
        }

        return $pend;
    }

    /** Documentos que quem devolve pode marcar como errados (ver App\Support\Devolucao). */
    public function documentosDevolviveis(): \Illuminate\Support\Collection
    {
        return \App\Support\Devolucao::candidatas($this->pecas()->where('categoria', 'celebracao')->get(), (int) $this->celebracao_etapa);
    }

    // Interface uniforme de trâmite, usada pelo motor de peças (ver Peca).
    public function tramiteEtapaAtual(): int
    {
        return (int) $this->celebracao_etapa;
    }

    /** As etapas do trâmite, na ordem — cada uma ['setor' => ..., 'acao' => ...]. */
    public function tramiteEtapas(): array
    {
        return self::ETAPAS_CELEBRACAO;
    }

    public function tramiteEncerrado(): bool
    {
        return $this->celebracaoConcluida();
    }

    public function tramiteSetorLabel(?string $setor): string
    {
        // Setor de fora deste trâmite (a PJ, por exemplo): cai na lotação.
        return self::SETORES_CELEBRACAO[$setor]
            ?? (User::LOTACOES[$setor] ?? strtoupper((string) $setor));
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
