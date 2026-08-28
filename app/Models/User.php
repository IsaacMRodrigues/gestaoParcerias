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

#[Fillable(['name', 'email', 'cpf', 'matricula', 'phone', 'status', 'setor', 'orgao_id', 'osc_id', 'password', 'approval_status', 'approved_at', 'approved_by', 'created_by', 'solicitacao_obs', 'rejeitado_motivo'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles, ImpedeExclusaoComVinculos;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    public static array $roleLabels = [
        'administrador_setorial'           => 'Administrador Setorial',
        'analista'                         => 'Analista (em descontinuação)',
        'analista_aditivo_apostilamento'   => 'Analista de Aditivo e Apostilamento',
        'analista_prestacao_contas_previa' => 'Analista de Prestação de Contas Prévia',
        'analista_viabilidade_tecnica'     => 'Analista de Viabilidade Técnica',
        'analista_juridico'                => 'Analista Jurídico',
        'analista_orcamentario_financeiro' => 'Analista Orçamentário Financeiro',
        'analista_tecnico_scp'             => 'Analista Técnico do SCP',
        'aprovador_assinatura_eletronica'  => 'Aprovador de Assinatura Eletrônica',
        'auditor_externo'                  => 'Auditor Externo',
        'auditor_geral'                    => 'Auditor Geral',
        'cadastrador'                      => 'Cadastrador',
        'chefe_setor'                      => 'Chefe de Setor',
        'contador'                         => 'Contador',
        'comissao_monitoramento_avaliacao' => 'Comissão de Monitoramento e Avaliação',
        'comissao_selecao'                 => 'Comissão de Seleção',
        'encaminhador'                     => 'Encaminhador',
        'gestor_parceria'                  => 'Gestor da Parceria',
        'operador_ordem_pagamento'         => 'Operador de Ordem de Pagamento',
        'prefeito_municipal'               => 'Prefeito Municipal',
        'responsavel_unidade_gestora'      => 'Responsável da Unidade Gestora',
        'responsavel_legal'                => 'Responsável Legal',
        'membro_osc'                       => 'Membro da OSC',
        'responsavel_publicacao'           => 'Responsável pela Publicação',
    ];

    /**
     * Papéis que NÃO são da Administração: gente da OSC, que só acessa o
     * portal. Quem tiver qualquer papel fora desta lista é usuário interno.
     */
    public const PAPEIS_OSC = ['responsavel_legal', 'membro_osc'];

    /**
     * O que cada integrante da OSC pode fazer — marcado pelo responsável legal
     * no cadastro da equipe.
     *
     * Até aqui a equipe era um bloco só: quem entrava podia tudo o que a OSC
     * pode. Uma entidade não trabalha assim — quem escreve o projeto não é
     * quem cuida das certidões, e os dados bancários da Celebração não são
     * assunto de todo mundo. As chaves são permissões Spatie (prefixo `osc_`),
     * concedidas por pessoa e não pelo papel.
     *
     * O que NÃO está aqui é deliberado: submeter proposta, protocolar recurso
     * e contra-assinar o Termo vinculam juridicamente a entidade e seguem com
     * o responsável legal — não são delegáveis por caixa marcada.
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
     * Perfis exclusivos de um setor: só podem ser atribuídos a quem é lotado nele.
     */
    /**
     * Perfis que o chefe de setor NÃO concede — só o administrador do sistema.
     *
     * São os que ultrapassam a própria Secretaria: acesso total (TI), leitura
     * de todos os órgãos (auditorias), o Gabinete, e o próprio posto de chefia
     * (senão um responsável de UG nomearia outro). Sem essa lista, delegar a
     * atribuição de perfis viraria escalada de privilégio: quem cadastra
     * poderia conceder a si mesmo, por interposta conta, mais do que tem.
     */
    public const PERFIS_VEDADOS_AO_CHEFE = [
        'administrador_setorial',
        'auditor_externo',
        'auditor_geral',
        'prefeito_municipal',
        'responsavel_unidade_gestora',
        'chefe_setor',   // chefe não nomeia outro chefe: quem designa chefia é o administrador
        'analista',   // em descontinuação: não se concede mais
    ];

    public const PERFIS_EXCLUSIVOS = [
        'administrador_setorial'           => 'ti',
        'responsavel_unidade_gestora'      => 'ug',
        'analista_tecnico_scp'             => 'scp',
        'responsavel_publicacao'           => 'scp',
        'analista_orcamentario_financeiro' => 'seplan',
        'prefeito_municipal'               => 'pm',
        'responsavel_legal'                => 'osc',
    ];

    /**
     * Encargos designados por portaria — não são lotação.
     *
     * Gestor da Parceria e as duas Comissões (Lei nº 13.019/2014, art. 2º, VI,
     * X e XI) são atribuições que a Unidade Gestora dá a servidores seus, por
     * ato que ela mesma publica — e que este sistema emite: a portaria do
     * gestor e a da Comissão de Monitoramento são peças da Celebração
     * preenchidas pela UG, e a da Comissão de Seleção é peça do Chamamento,
     * também dela.
     *
     * Estavam modelados como perfis exclusivos de "setores" que ninguém ocupa
     * (nem aqui nem em produção, nunca ocupou). O resultado: a UG publicava a
     * portaria e não conseguia criar a conta — o perfil não aparecia na lista
     * dela, e atribuí-lo pelo cadastro exigiria tirar a pessoa da Unidade
     * Gestora, de onde ela não sai. Quem é designado acumula o encargo sobre o
     * próprio perfil, como a chefia de setor.
     */
    public const PERFIS_DE_DESIGNACAO = [
        'ug' => [
            'gestor_parceria',
            'comissao_selecao',
            'comissao_monitoramento_avaliacao',
        ],
    ];

    /**
     * Perfis com acesso somente de leitura (auditoria).
     */
    public const PERFIS_SOMENTE_LEITURA = ['auditor_externo', 'auditor_geral'];

    /**
     * Situação da aprovação do cadastro.
     */
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

    public function aprovadoPor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function criadoPor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function subusuarios(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(User::class, 'created_by');
    }

    /**
     * A OSC de que este usuário faz parte.
     *
     * Era um hasOne sobre oscs.user_id, o que limitava cada OSC a uma única
     * conta. Agora o vínculo mora em users.osc_id e a organização pode ter
     * equipe; oscs.user_id ficou reservado ao responsável legal.
     */
    public function osc(): BelongsTo
    {
        return $this->belongsTo(Osc::class);
    }

    // ------------------------------------------------------------------
    // Rastros do usuário no sistema.
    //
    // Todas estas colunas têm FK sem CASCADE: são o registro de quem fez o
    // quê, e o banco impede que sumam junto com a conta. Existem aqui para o
    // motivoParaNaoExcluir() poder contá-las antes de tentar o delete.
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
     * Usuário interno do sistema (equipe da Administração) — qualquer papel que
     * não seja exclusivamente o `responsavel_legal` das OSCs, que só acessa o
     * portal público. Usado para manter o menu administrativo no topo mesmo
     * quando o interno navega pelo portal.
     */
    /**
     * Perfis que este usuário pode conceder ao cadastrar alguém.
     *
     * Deriva das regras em vez de repetir uma lista: tira os papéis de OSC, os
     * vedados ao chefe e os que são exclusivos de OUTRO setor — o subusuário
     * herda a lotação de quem o cadastra, então conceder um perfil de outro
     * setor seria atribuir algo que o cadastrado não poderia exercer.
     *
     * @return array<string,string> slug => rótulo
     */
    /**
     * Cadastra a equipe do próprio setor?
     *
     * A porta existia só para o chefe da Unidade Gestora; SCP, SEPLAN, PJ e
     * Gabinete dependiam do administrador criar cada conta. Agora vale para
     * qualquer setor, por meio da permissão `usuarios_setor` — que o perfil
     * `chefe_setor` concede e a chefia da UG já traz.
     *
     * Exige lotação: o usuário criado herda o setor de quem cadastra, então sem
     * setor não há o que herdar (é o caso das auditorias, transversais).
     * Quem tem `cadastros` não usa esta porta — cria e aprova direto em
     * Cadastros → Usuários, e ver as duas na tela só confundiria.
     */
    public function podeCadastrarNoSetor(): bool
    {
        return $this->setor
            && $this->can('usuarios_setor')
            && !$this->can('cadastros')
            && !$this->somenteLeitura();
    }

    public function perfisQuePodeConceder(): array
    {
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

    public function temAcessoInterno(): bool
    {
        return $this->roles->contains(fn ($role) => !in_array($role->name, self::PAPEIS_OSC, true));
    }

    /**
     * Atua como OSC? Definição única de quem pode participar de chamamentos,
     * submeter propostas e mexer nos documentos da própria proposta.
     *
     * Exige as duas coisas: o papel de responsável legal E o vínculo com a OSC.
     * Só o vínculo não basta — servidor é usuário interno dos setores, não
     * representa entidade, e um registro em oscs.user_id apontando para a conta
     * de um servidor (por engano ou má-fé) não pode virar permissão. Por isso
     * as telas e os controllers perguntam por este método, nunca por ->osc.
     */
    public function ehRepresentanteOsc(): bool
    {
        return !$this->temAcessoInterno() && $this->osc_id !== null;
    }

    /**
     * É o responsável legal da OSC — quem responde juridicamente por ela.
     *
     * Distinção que passou a existir quando a OSC ganhou equipe: todo mundo da
     * organização prepara a proposta, mas submeter, recorrer e administrar os
     * acessos são atos que vinculam a entidade, e ficam com uma pessoa só.
     * A fonte da verdade é oscs.user_id, não o papel: papel se atribui por
     * engano, a titularidade do cadastro não.
     */
    public function ehResponsavelLegalOsc(): bool
    {
        return $this->ehRepresentanteOsc() && $this->osc?->user_id === $this->id;
    }

    /**
     * Integrante da OSC a quem esta função NÃO foi marcada.
     *
     * A pergunta é feita em telas que servem aos dois lados (documentos da
     * proposta, peças do trâmite), onde o servidor tem as próprias permissões e
     * não pode ser medido por esta régua — daí a checagem vir junto.
     */
    public function oscSemFuncao(string $funcao): bool
    {
        return $this->ehRepresentanteOsc() && ! $this->can($funcao);
    }

    /**
     * O "setor" deste usuário para efeito de trâmite.
     *
     * Os fluxos designam etapas a setores, e um desses setores é a própria OSC
     * (na Celebração ela elabora o Plano de Trabalho, anexa a habilitação,
     * assina o Termo e informa os dados bancários). Só que OSC não tem lotação:
     * users.setor é NULL para ela. Quem comparava `$user->setor === 'osc'`
     * obtinha sempre falso, e o trâmite entrava num beco — a parceria chegava à
     * OSC e não podia ser movimentada por ninguém, nem por ela.
     *
     * Daí este acessor: um lugar só para dizer que, no trâmite, quem representa
     * a OSC atua como setor 'osc'.
     */
    public function setorNoTramite(): ?string
    {
        return $this->ehRepresentanteOsc() ? 'osc' : $this->setor;
    }

    /**
     * Toma parte no trâmite da Celebração?
     *
     * A permissão `formalizacao` responde por quem lavra o instrumento, mas a
     * Celebração passa por setores que não a têm: a SCP conduz sete das quinze
     * etapas (protocolo na PJ, termo, publicação, ordem de pagamento, empenho),
     * a SEPLAN emite o Parecer Financeiro, a PJ o Parecer Jurídico. Gateando o
     * menu só por `formalizacao`, esses setores viam a Celebração cadeado —
     * enquanto a caixa de entrada lhes entregava, no mesmo instante, parceria
     * parada esperando a sua etapa.
     *
     * Quem participa é, então, quem aparece no fluxo: os setores de
     * ETAPAS_CELEBRACAO. A OSC fica de fora porque não navega pelo menu
     * interno — chega à sua etapa pelo portal e pela caixa.
     */
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
     * Vê dados (ex.: propostas) de TODOS os órgãos? — administrador, auditoria
     * (somente leitura) ou papéis transversais não lotados numa Secretaria
     * específica (comissões etc.). Quem é lotado numa UG vê só o próprio órgão.
     */
    public function podeVerTodosOrgaos(): bool
    {
        return is_null($this->orgao_id)
            || $this->somenteLeitura()
            || $this->hasRole('administrador_setorial');
    }
}
