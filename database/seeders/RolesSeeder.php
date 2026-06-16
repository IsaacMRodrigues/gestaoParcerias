<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'representante_legal',
            'secretario_unidade_gestora',
            'gestor_parceria',
            'comissao_avaliacao_monitoramento',
            'comissao_selecao',
            'procuradoria_juridica',
            'controle_interno',
            'cadastrador_proposta',
            'cadastrador_prestacao_contas',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }
}
