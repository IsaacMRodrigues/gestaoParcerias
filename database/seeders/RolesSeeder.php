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
     * Matriz: perfil => permissões.
     * 'administrador' e 'controle_interno' recebem todas (controle_interno só leitura via middleware).
     */
    public const MATRIZ = [
        'administrador'                    => ['*'],
        'controle_interno'                 => ['*'], // somente leitura (middleware readonly)
        'secretario_unidade_gestora'       => ['planejamento', 'chamamentos', 'propostas', 'pareceres_decisao', 'formalizacao'],
        'gestor_parceria'                  => ['planejamento', 'monitoramento'],
        'comissao_selecao'                 => ['propostas', 'pareceres_tecnico', 'pareceres_decisao'],
        'comissao_avaliacao_monitoramento' => ['planejamento', 'monitoramento'],
        'procuradoria_juridica'            => ['planejamento', 'pareceres_juridico'],
        'cadastrador_proposta'             => ['propostas'],
        'cadastrador_prestacao_contas'     => ['prestacao_contas'],
        'representante_legal'              => [], // só portal
    ];

    public function run(): void
    {
        // Permissões
        foreach (array_keys(self::PERMISSOES) as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $todas = array_keys(self::PERMISSOES);

        // Perfis + atribuição
        foreach (self::MATRIZ as $role => $perms) {
            $r = Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
            $conceder = ($perms === ['*']) ? $todas : $perms;
            $r->syncPermissions($conceder);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
