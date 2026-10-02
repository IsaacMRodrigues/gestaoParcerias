<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use App\Models\Concerns\ImpedeExclusaoComVinculos;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'login', 'cpf', 'matricula', 'phone', 'status', 'setor', 'orgao_id', 'osc_id', 'password', 'deve_trocar_senha', 'approval_status', 'approved_at', 'approved_by', 'created_by', 'solicitacao_obs', 'rejeitado_motivo'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;
    use ImpedeExclusaoComVinculos {
        motivosParaNaoExcluir as motivosDosVinculos;
    }

    public static array $roleLabels = [
        'administrador_setorial'           => 'Administrador Setorial',
        'analista'                         => 'Analista (em descontinuação)',
        'analista_aditivo_apostilamento'   => 'Analista de Aditivo e Apostilamento',
        'analista_prestacao_contas_previa' => 'Analista de Prestação de Contas Prévia',
        'analista_viabilidade_tecnica'     => 'Analista de Viabilidade Técnica',
        'analista_juridico'                => 'Analista Jurídico',
        'analista_orcamentario_financeiro' => 'Analista Orçamentário Financeiro',
        'responsavel_seplan'               => 'Responsável pela SEPLAN',
        'analista_tecnico_scp'             => 'Analista Técnico do SCP',
        'aprovador_assinatura_eletronica'  => 'Aprovador de Assinatura Eletrônica',
        'auditor_externo'                  => 'Auditor Externo',
        'auditor_geral'                    => 'Auditor Geral',
        'cadastrador'                      => 'Cadastrador',
        'chefe_setor'                      => 'Chefe de Setor',
        'contador'                         => 'Contador',
        'comissao_monitoramento'           => 'Comissão de Monitoramento',
        'comissao_avaliacao'               => 'Comissão de Avaliação',
        'comissao_selecao'                 => 'Comissão de Seleção',
        'encaminhador'                     => 'Encaminhador',
        'gestor_parceria'                  => 'Gestor da Parceria',
        'operador_ordem_pagamento'         => 'Operador de Ordem de Pagamento',
        'prefeito_municipal'               => 'Prefeito Municipal',
        'responsavel_unidade_gestora'      => 'Responsável da Unidade Gestora',
        'responsavel_legal'                => 'Responsável Legal',
        'membro_osc'                       => 'Membro da OSC',
        'responsavel_publicacao'           => 'Responsável pela Publicação',

        // Perfis do convenente (equipe da OSC) — módulo 1, aba "Membros".
        'cadastrador_proposta'             => 'Cadastrador de Proposta',
        'cadastrador_prestacao_contas'     => 'Cadastrador de Prestação de Contas',
        'cadastrador_usuario_entidade'     => 'Cadastrador de Usuário do Ente/Entidade',
        'contador_osc'                     => 'Contador',
        'responsavel_execucao_osc'         => 'Responsável por Execução',
    ];

    /**
     * Papéis da OSC, que só acessa o portal. 'contador' fica fora: existe dos dois lados,
     * e quem separa é o vínculo (ver temAcessoInterno).
     */
    public const PAPEIS_OSC = [
        'responsavel_legal',
        'membro_osc',
        'cadastrador_proposta',
        'cadastrador_prestacao_contas',
        'cadastrador_usuario_entidade',
        'contador_osc',
        'responsavel_execucao_osc',
    ];

    /**
     * Perfis que um integrante da OSC pode receber: dizem o que a pessoa é na organização e
     * saem na assinatura (o que ela pode fazer está em FUNCOES_OSC). Nenhum abre telas da
     * Administração; o Responsável Legal é o titular do cadastro (ver ehResponsavelLegalOsc).
     */
    public const PERFIS_OSC = [
        'membro_osc' => [
            'rotulo' => 'Membro da OSC',
            'ajuda'  => 'Integrante da equipe. Todo cadastro recebe este perfil.',
            'fixo'   => true,
        ],
        'cadastrador_proposta' => [
            'rotulo' => 'Cadastrador de Proposta',
            'ajuda'  => 'Monta a proposta e o plano de trabalho da organização.',
        ],
        'cadastrador_prestacao_contas' => [
            'rotulo' => 'Cadastrador de Prestação de Contas',
            'ajuda'  => 'Reúne e envia a prestação de contas da parceria.',
        ],
        'cadastrador_usuario_entidade' => [
            'rotulo' => 'Cadastrador de Usuário do Ente/Entidade',
            'ajuda'  => 'Administra as contas de acesso da própria organização.',
        ],
        // Chaves próprias (_osc): o Contador da Prefeitura abre as prestações de todas as parcerias.
        'contador_osc' => [
            'rotulo' => 'Contador',
            'ajuda'  => 'Responde pela escrituração e pelas demonstrações contábeis da parceria.',
        ],
        'responsavel_execucao_osc' => [
            'rotulo' => 'Responsável por Execução',
            'ajuda'  => 'Acompanha a execução do objeto e o cumprimento das metas.',
        ],
    ];

    /**
     * O que cada integrante da OSC pode fazer (permissões Spatie osc_*, por pessoa).
     * Submeter proposta e protocolar recurso ficam com o responsável legal: não se delegam.
     */
    public const FUNCOES_OSC = [
        'osc_propostas' => [
            'rotulo' => 'Propostas e plano de trabalho',
            'ajuda'  => 'Participar de chamamento aberto e montar a proposta, com metas e etapas.',
        ],
        'osc_documentos' => [
            'rotulo' => 'Documentos da organização',
            'ajuda'  => 'Anexar e retirar estatuto, certidões e demais documentos.',
        ],
        'osc_manifestacoes' => [
            'rotulo' => 'Manifestações de interesse',
            'ajuda'  => 'Propor parceria quando não há chamamento aberto.',
        ],
        'osc_celebracao' => [
            'rotulo' => 'Celebração da parceria',
            'ajuda'  => 'Enviar o plano final, a habilitação e os dados bancários no trâmite.',
        ],
    ];

    /**
     * Setores que atendem o Município inteiro, não uma Secretaria: veem todos os órgãos
     * (ver podeVerTodosOrgaos), qualquer que seja a sede.
     */
    public const SETORES_TRANSVERSAIS = ['scp', 'seplan', 'pj', 'pm', 'ti'];

    /**
     * Setores de lotação do usuário (mais amplo que os setores do trâmite).
     */
    public const LOTACOES = [
        'ug'                 => 'Unidade Gestora',
        'scp'                => 'Setor de Convênios e Parcerias (SCP)',
        'seplan'             => 'Secretaria de Planejamento (SEPLAN)',
        'pj'                 => 'Procuradoria Jurídica (PJ)',
        'pm'                 => 'Gabinete do Prefeito (PM)',
        'ti'                 => 'Tecnologia da Informação (TI)',
        'osc'                => 'OSC (externo)',
    ];

    /**
     * Perfis que o chefe de setor não concede, só o administrador: os que ultrapassam a
     * Secretaria e o próprio posto de chefia (evita escalada de privilégio).
     */
    public const PERFIS_VEDADOS_AO_CHEFE = [
        'administrador_setorial',
        'auditor_externo',
        'auditor_geral',
        'prefeito_municipal',
        'responsavel_unidade_gestora',
        'responsavel_seplan', // assina o Parecer Financeiro e cadastra a equipe: só o administrador designa
        'chefe_setor',   // chefe não nomeia outro chefe: quem designa chefia é o administrador
        'analista',   // em descontinuação: não se concede mais
    ];

    /** Setores com responsável de perfil próprio: nesses, o responsável é o chefe e cadastra a equipe. */
    public const RESPONSAVEL_DO_SETOR = [
        'ug'     => 'responsavel_unidade_gestora',
        'seplan' => 'responsavel_seplan',
    ];

    /** Perfis exclusivos de um setor: só para quem é lotado nele. */
    public const PERFIS_EXCLUSIVOS = [
        'administrador_setorial'           => 'ti',
        'responsavel_unidade_gestora'      => 'ug',
        'analista_tecnico_scp'             => 'scp',
        'responsavel_publicacao'           => 'scp',
        'analista_orcamentario_financeiro' => 'seplan',
        'responsavel_seplan'               => 'seplan',
        'prefeito_municipal'               => 'pm',
        'responsavel_legal'                => 'osc',
    ];

    /**
     * Encargos designados por portaria (Gestor da Parceria e as Comissões), não lotação:
     * a UG os concede a servidores seus, que acumulam o encargo sobre o próprio perfil.
     */
    public const PERFIS_DE_DESIGNACAO = [
        'ug' => [
            'gestor_parceria',
            'comissao_selecao',
            'comissao_monitoramento',
            'comissao_avaliacao',
        ],
    ];

    /**
     * Encargos que a mesma pessoa não acumula, porque cada um fiscaliza o anterior: um, no máximo.
     * Conferido onde se atribui perfil de servidor (conflitoDeEncargos).
     */
    public const ENCARGOS_QUE_NAO_ACUMULAM = [
        'comissao_selecao',
        'gestor_parceria',
        'comissao_monitoramento',
        'comissao_avaliacao',
    ];

    /** Mensagem de erro se os perfis acumulam dois encargos; null se não acumulam. */
    public static function conflitoDeEncargos(array $perfis): ?string
    {
        $acumulados = array_values(array_intersect(self::ENCARGOS_QUE_NAO_ACUMULAM, $perfis));

        if (count($acumulados) < 2) {
            return null;
        }

        $nomes = array_map(fn ($p) => self::$roleLabels[$p] ?? $p, $acumulados);

        return 'A mesma pessoa não pode acumular ' . implode(' e ', [implode(', ', array_slice($nomes, 0, -1)), end($nomes)])
            . '. Gestor da Parceria, Comissão de Seleção, Comissão de Monitoramento e Comissão de Avaliação '
            . 'são encargos que se fiscalizam: escolha um só.';
    }

    /** Perfis com acesso somente de leitura (auditoria). */
    public const PERFIS_SOMENTE_LEITURA = ['auditor_externo', 'auditor_geral'];

    /** Situação da aprovação do cadastro. */
    public const APPROVAL = [
        'pendente' => 'Pendente de aprovação',
        'aprovado' => 'Aprovado',
        'recusado' => 'Recusado',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'status'            => 'boolean',
            'approved_at'       => 'datetime',
            'deve_trocar_senha' => 'boolean',
        ];
    }

    public function isPendente(): bool
    {
        return $this->approval_status === 'pendente';
    }

    public function isAprovado(): bool
    {
        return $this->approval_status === 'aprovado';
    }

    public function isRecusado(): bool
    {
        return $this->approval_status === 'recusado';
    }

    /** Pode efetivamente autenticar (aprovado e ativo). */
    public function podeAutenticar(): bool
    {
        return $this->isAprovado() && $this->status;
    }

    /** Mensagem exibida no login quando o acesso está bloqueado. */
    public function mensagemBloqueioLogin(): string
    {
        if ($this->isPendente()) {
            return 'Seu cadastro está aguardando aprovação do administrador.';
        }
        if ($this->isRecusado()) {
            return 'Seu cadastro foi recusado' . ($this->rejeitado_motivo ? ': ' . $this->rejeitado_motivo : '.');
        }
        return 'Seu acesso está inativo. Procure o administrador.';
    }

    public function scopePendentes($query)
    {
        return $query->where('approval_status', 'pendente');
    }

    public function criadoPor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function subusuarios(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(User::class, 'created_by');
    }

    /** A OSC de que o usuário faz parte (users.osc_id); oscs.user_id é o responsável legal. */
    public function osc(): BelongsTo
    {
        return $this->belongsTo(Osc::class);
    }

    // ------------------------------------------------------------------
    // Rastros do usuário (FKs sem CASCADE), contados antes de excluir a conta.
    // ------------------------------------------------------------------

    public function processosCriados(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Processo::class, 'created_by');
    }

    public function pecasAssinadas(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Peca::class, 'assinado_por');
    }

    public function processoPecasAssinadas(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProcessoPeca::class, 'assinado_por');
    }

    public function documentosEnviados(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Documento::class, 'uploaded_by');
    }

    public function anexosEnviados(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProcessoPecaAnexo::class, 'enviado_por');
    }

    public function tramitacoesEnviadas(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Tramitacao::class, 'enviado_por');
    }

    public function tramitacoesRecebidas(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Tramitacao::class, 'recebido_por');
    }

    protected function vinculosBloqueantes(): array
    {
        return [
            'processosCriados'       => ['processo aberto', 'processos abertos'],
            'pecasAssinadas'         => ['peça assinada', 'peças assinadas'],
            'processoPecasAssinadas' => ['peça de processo assinada', 'peças de processo assinadas'],
            'documentosEnviados'     => ['documento enviado', 'documentos enviados'],
            'anexosEnviados'         => ['anexo enviado', 'anexos enviados'],
            'tramitacoesEnviadas'    => ['tramitação enviada', 'tramitações enviadas'],
            'tramitacoesRecebidas'   => ['tramitação recebida', 'tramitações recebidas'],
        ];
    }

    /**
     * Demais colunas que registram autoria (com nullOnDelete), contadas por tabela: só se exclui
     * conta que nunca fez nada. A regra é desativar, não excluir.
     */
    public const AUTORIA_REGISTRADA = [
        'pecas.contra_assinado_por'              => ['contra-assinatura', 'contra-assinaturas'],
        'peca_assinaturas.assinado_por'          => ['assinatura de Termo', 'assinaturas de Termo'],
        'osc_arquivos.enviado_por'               => ['arquivo da OSC enviado', 'arquivos da OSC enviados'],
        'osc_arquivo_analises.analisado_por'     => ['arquivo da OSC analisado', 'arquivos da OSC analisados'],
        'propostas.celebracao_gestor_id'         => ['parceria da qual é Gestor', 'parcerias das quais é Gestor'],
        'propostas.decidida_por'                 => ['proposta decidida', 'propostas decididas'],
        'pecas.criado_por'                       => ['peça criada', 'peças criadas'],
        'ordens_pagamento.assinado_por'          => ['ordem de pagamento assinada', 'ordens de pagamento assinadas'],
        'selecao_tramitacoes.enviado_por'        => ['tramitação da Seleção', 'tramitações da Seleção'],
        'celebracao_tramitacoes.enviado_por'     => ['tramitação da Celebração', 'tramitações da Celebração'],
        'alteracao_tramitacoes.enviado_por'      => ['tramitação de alteração', 'tramitações de alteração'],
        'prestacao_tramitacoes.enviado_por'      => ['tramitação de prestação de contas', 'tramitações de prestação de contas'],
        'alteracoes.criada_por'                  => ['alteração pedida', 'alterações pedidas'],
        'alteracoes.decidida_por'                => ['alteração decidida', 'alterações decididas'],
        'manifestacoes_interesse.decidida_por'   => ['manifestação decidida', 'manifestações decididas'],
        'manifestacoes_interesse.parecer_por'    => ['parecer em manifestação', 'pareceres em manifestação'],
        'recursos.protocolado_por'               => ['recurso protocolado', 'recursos protocolados'],
        'recursos.respondido_por'                => ['recurso respondido', 'recursos respondidos'],
        'documentos.analisado_por'               => ['documento conferido', 'documentos conferidos'],
        'prestacoes_contas.created_by'           => ['prestação de contas aberta', 'prestações de contas abertas'],
        'oscs.user_id'                           => ['OSC da qual é responsável legal', 'OSCs das quais é responsável legal'],
        'chamados.user_id'                       => ['chamado de suporte aberto', 'chamados de suporte abertos'],
        'chamados.resolvido_por'                 => ['chamado de suporte encerrado', 'chamados de suporte encerrados'],
        'chamados.conta_id'                      => ['pedido de nova senha', 'pedidos de nova senha'],
        'chamamento_cancelamentos.user_id'       => ['cancelamento ou reabertura de chamamento', 'cancelamentos ou reaberturas de chamamento'],
        'chamamento_prorrogacoes.user_id'        => ['prorrogação de chamamento', 'prorrogações de chamamento'],
        'chamado_mensagens.user_id'              => ['mensagem de suporte', 'mensagens de suporte'],
        'users.approved_by'                      => ['conta aprovada', 'contas aprovadas'],
        'users.created_by'                       => ['conta cadastrada', 'contas cadastradas'],
    ];

    public function motivosParaNaoExcluir(): array
    {
        $motivos = $this->motivosDosVinculos();

        foreach (self::AUTORIA_REGISTRADA as $alvo => [$singular, $plural]) {
            [$tabela, $coluna] = explode('.', $alvo);
            $quantos = \Illuminate\Support\Facades\DB::table($tabela)->where($coluna, $this->id)->count();

            if ($quantos > 0) {
                $motivos[] = $quantos.' '.($quantos === 1 ? $singular : $plural);
            }
        }

        return $motivos;
    }

    protected function fraseDeBloqueio(): string
    {
        return 'Este usuário não pode ser excluído';
    }

    protected function sugestaoParaNaoExcluir(): string
    {
        return 'Desative a conta em vez de excluí-la: o histórico do processo precisa '
            .'continuar mostrando quem assinou e quem tramitou.';
    }

    public function orgao(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Orgao::class);
    }

    public function setorLabel(): string
    {
        return self::LOTACOES[$this->setor] ?? '—';
    }

    public function somenteLeitura(): bool
    {
        return $this->hasAnyRole(self::PERFIS_SOMENTE_LEITURA);
    }

    /**
     * Cadastra a equipe do próprio setor? Pela permissão usuarios_setor e com lotação (o novo
     * usuário herda o setor). Quem tem cadastros usa a tela de Cadastros.
     */
    public function podeCadastrarNoSetor(): bool
    {
        return $this->setor
            && $this->can('usuarios_setor')
            && !$this->can('cadastros')
            && !$this->somenteLeitura();
    }

    /**
     * Perfis que este usuário pode conceder: tira os da OSC, os vedados ao chefe e os de outro setor.
     *
     * @return array<string,string> slug => rótulo
     */
    public function perfisQuePodeConceder(): array
    {
        // O administrador já concede qualquer perfil em Cadastros; a régua abaixo é para o chefe de setor.
        if ($this->can('cadastros')) {
            return collect(self::$roleLabels)
                ->reject(fn ($rotulo, $slug) => in_array($slug, self::PAPEIS_OSC, true))
                ->all();
        }

        $meuSetor = $this->setor;

        return collect(self::$roleLabels)
            ->reject(fn ($rotulo, $slug) => in_array($slug, self::PAPEIS_OSC, true))
            ->reject(fn ($rotulo, $slug) => in_array($slug, self::PERFIS_VEDADOS_AO_CHEFE, true))
            ->reject(function ($rotulo, $slug) use ($meuSetor) {
                $exigido = self::PERFIS_EXCLUSIVOS[$slug] ?? null;
                return $exigido !== null && $exigido !== $meuSetor;
            })
            // Encargo por designação: quem concede é quem publica a portaria.
            ->reject(function ($rotulo, $slug) use ($meuSetor) {
                $designa = self::setorQueDesigna($slug);
                return $designa !== null && $designa !== $meuSetor;
            })
            ->all();
    }

    /** Setor que designa este encargo, ou null se o perfil não for encargo. */
    public static function setorQueDesigna(string $perfil): ?string
    {
        foreach (self::PERFIS_DE_DESIGNACAO as $setor => $perfis) {
            if (in_array($perfil, $perfis, true)) {
                return $setor;
            }
        }

        return null;
    }

    /** Usuário interno (Administração)? O vínculo com OSC decide antes do papel. */
    public function temAcessoInterno(): bool
    {
        // Quem é de uma OSC não é servidor, tenha o perfil que tiver (o Contador existe dos dois lados).
        if ($this->osc_id !== null) {
            return false;
        }

        return $this->roles->contains(fn ($role) => !in_array($role->name, self::PAPEIS_OSC, true));
    }

    /**
     * Atua como OSC? Exige o papel de responsável legal e o vínculo com a OSC.
     * As telas perguntam por aqui, nunca por ->osc.
     */
    public function ehRepresentanteOsc(): bool
    {
        return !$this->temAcessoInterno() && $this->osc_id !== null;
    }

    /**
     * Cargo e entidade de quem assina ("Analista Técnico do SCP — Planejamento"; pela OSC, a própria
     * OSC), pelo perfil mais específico. Gravado na assinatura (pecas.assinante_cargo).
     */
    public function cargoParaAssinatura(): ?string
    {
        $papel = ($this->roles->first(fn ($r) => $r->name !== 'membro_osc')
            ?? $this->roles->first())?->name;

        $cargo = $papel ? (self::$roleLabels[$papel] ?? null) : null;
        $cargo = $cargo ?: ($this->setor ? (\App\Models\Processo::SETORES[$this->setor] ?? null) : null);

        $entidade = $this->orgao?->name ?: $this->osc?->name;

        return $entidade ? ($cargo ? $cargo . ' — ' . $entidade : $entidade) : $cargo;
    }

    /** O par nome/cargo que se congela numa assinatura. */
    public function identidadeParaAssinatura(): array
    {
        return ['nome' => $this->name, 'cargo' => $this->cargoParaAssinatura()];
    }

    /** Responsável legal da OSC: o titular do cadastro (oscs.user_id), não o papel. */
    public function ehResponsavelLegalOsc(): bool
    {
        return $this->ehRepresentanteOsc() && $this->osc?->user_id === $this->id;
    }

    /** Integrante da OSC sem esta função marcada (servidores não são medidos por esta régua). */
    public function oscSemFuncao(string $funcao): bool
    {
        return $this->ehRepresentanteOsc() && ! $this->can($funcao);
    }

    /** Setor no trâmite: quem representa a OSC atua como 'osc' (a OSC não tem lotação). */
    public function setorNoTramite(): ?string
    {
        return $this->ehRepresentanteOsc() ? 'osc' : $this->setor;
    }

    /** Toma parte na Celebração? Os setores de ETAPAS_CELEBRACAO (a OSC chega pelo portal). */
    public function participaDaCelebracao(): bool
    {
        if (!$this->temAcessoInterno()) {
            return false;
        }

        $setoresDoTramite = array_diff(array_keys(Proposta::SETORES_CELEBRACAO), ['osc']);

        return $this->can('formalizacao')
            || in_array($this->setor, $setoresDoTramite, true);
    }

    /** A OSC que o usuário representa — null para todo usuário interno. */
    public function oscVinculada(): ?Osc
    {
        return $this->ehRepresentanteOsc() ? $this->osc : null;
    }

    /**
     * Vê todos os órgãos? Administrador, auditoria, quem não tem Secretaria ou é de setor
     * transversal. Só a Unidade Gestora é de fato de uma Secretaria.
     */
    public function podeVerTodosOrgaos(): bool
    {
        return is_null($this->orgao_id)
            || in_array($this->setor, self::SETORES_TRANSVERSAIS, true)
            || $this->somenteLeitura()
            || $this->hasRole('administrador_setorial');
    }
}
