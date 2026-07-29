<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'cpf', 'matricula', 'phone', 'status', 'setor', 'orgao_id', 'password', 'approval_status', 'approved_at', 'approved_by', 'created_by', 'solicitacao_obs', 'rejeitado_motivo'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

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
        'responsavel_unidade_gestora'      => 'Responsável da Unidade Gestora',
        'responsavel_legal'                => 'Responsável Legal',
        'responsavel_publicacao'           => 'Responsável pela Publicação',
    ];

    /**
     * Setores de lotação do usuário (mais amplo que os setores do trâmite).
     */
    public const LOTACOES = [
        'ug'                 => 'Unidade Gestora',
        'scp'                => 'Setor de Convênios e Parcerias (SCP)',
        'seplan'             => 'Secretaria de Planejamento (SEPLAN)',
        'pj'                 => 'Procuradoria Jurídica (PJ)',
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

    public function osc(): HasOne
    {
        return $this->hasOne(Osc::class);
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
        return $this->roles->contains(fn ($role) => $role->name !== 'responsavel_legal');
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
