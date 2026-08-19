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
     * Setores de lotação do usuário (mais amplo que os setores do trâmite).
     */
    public const LOTACOES = [
        'ug'                 => 'Unidade Gestora',
        'scp'                => 'Setor de Convênios e Parcerias (SCP)',
        'seplan'             => 'Secretaria de Planejamento (SEPLAN)',
        'pj'                 => 'Procuradoria Jurídica (PJ)',
        'pm'                 => 'Gabinete do Prefeito (PM)',
        'ti'                 => 'Tecnologia da Informação (TI)',
        'comissao_selecao'   => 'Comissão de Seleção',
        'comissao_avaliacao' => 'Comissão de Avaliação e Monitoramento',
        'gestor'             => 'Gestoria de Parcerias',
        'osc'                => 'OSC (externo)',
    ];

    /**
     * Perfis exclusivos de um setor: só podem ser atribuídos a quem é lotado nele.
     */
    public const PERFIS_EXCLUSIVOS = [
        'administrador_setorial'           => 'ti',
        'responsavel_unidade_gestora'      => 'ug',
        'analista_tecnico_scp'             => 'scp',
        'responsavel_publicacao'           => 'scp',
        'analista_orcamentario_financeiro' => 'seplan',
        'prefeito_municipal'               => 'pm',
        'comissao_selecao'                 => 'comissao_selecao',
        'comissao_monitoramento_avaliacao' => 'comissao_avaliacao',
        'gestor_parceria'                  => 'gestor',
        'responsavel_legal'                => 'osc',
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
