<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesSeeder extends Seeder
{
    /**
     * Permissões por área do sistema.
     */
    public const PERMISSOES = [
        'cadastros'         => 'Cadastros (usuários, órgãos, OSCs)',
        'planejamento'      => 'Planejamento (processos, termo de referência, trâmite)',
        'chamamentos'       => 'Programas e Chamamentos',
        'propostas'         => 'Propostas e Plano de Trabalho',
        'pareceres_tecnico' => 'Parecer Técnico',
        'pareceres_juridico'=> 'Parecer Jurídico',
        'pareceres_decisao' => 'Decisão / Seleção',
        'formalizacao'      => 'Formalização (instrumentos e aditivos)',
        'monitoramento'     => 'Monitoramento e Fiscalização',
        'prestacao_contas'  => 'Prestação de Contas',
    ];

    /**
     * Matriz perfil => permissões (perfis do Módulo 1).
     * '*' = todas. Auditores recebem todas porém apenas leitura (middleware readonly).
     */
    public const MATRIZ = [
        'administrador_setorial'           => ['*'],
        'auditor_externo'                  => ['*'], // somente leitura
        'auditor_geral'                    => ['*'], // somente leitura
        'responsavel_unidade_gestora'      => ['planejamento', 'chamamentos', 'propostas', 'pareceres_decisao', 'formalizacao'],
        'analista_tecnico_scp'             => ['planejamento', 'chamamentos'],
        'responsavel_publicacao'           => ['chamamentos'],
        'analista_orcamentario_financeiro' => ['planejamento'],
        'analista_juridico'                => ['pareceres_juridico'],
        'analista_viabilidade_tecnica'     => ['pareceres_tecnico'],
        'analista_aditivo_apostilamento'   => ['formalizacao'],
        'analista_prestacao_contas_previa' => ['prestacao_contas'],
        'comissao_selecao'                 => ['propostas', 'pareceres_tecnico', 'pareceres_decisao'],
        'comissao_monitoramento_avaliacao' => ['monitoramento'],
        'gestor_parceria'                  => ['planejamento', 'monitoramento'],
        'cadastrador'                      => ['chamamentos', 'propostas', 'formalizacao'],
        'contador'                         => ['prestacao_contas'],
        'encaminhador'                     => ['formalizacao'],
        'operador_ordem_pagamento'         => [], // módulo de pagamento (futuro)
        'aprovador_assinatura_eletronica'  => [], // módulo de assinatura (futuro)
        'analista'                         => [], // acesso básico, em descontinuação
        'responsavel_legal'                => [], // só portal
    ];

    public function run(): void
    {
        foreach (array_keys(self::PERMISSOES) as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $todas = array_keys(self::PERMISSOES);

        foreach (self::MATRIZ as $role => $perms) {
            $r = Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
            $r->syncPermissions($perms === ['*'] ? $todas : $perms);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
