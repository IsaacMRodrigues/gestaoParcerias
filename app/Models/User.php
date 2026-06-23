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

#[Fillable(['name', 'email', 'cpf', 'phone', 'status', 'setor', 'orgao_id', 'password'])]
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

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'status'            => 'boolean',
        ];
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
}
