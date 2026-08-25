<?php

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * As funções da OSC passam a ser marcadas por pessoa. Quem já está cadastrado
 * recebe as quatro.
 *
 * Não é generosidade: até hoje qualquer integrante da equipe fazia tudo o que a
 * OSC pode fazer, e a tela nova só passa a valer daqui para a frente. Sem esta
 * migração, ligar a checagem tiraria de gente que trabalha um acesso que ela
 * tinha ontem — inclusive no meio de uma Celebração em andamento. Quem deve
 * perder função, perde pela tela, por decisão do responsável legal.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (RolesSeeder::FUNCOES_OSC as $funcao) {
            Permission::firstOrCreate(['name' => $funcao, 'guard_name' => 'web']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        User::role('membro_osc')->get()
            ->each(fn (User $u) => $u->givePermissionTo(RolesSeeder::FUNCOES_OSC));
    }

    public function down(): void
    {
        User::role('membro_osc')->get()
            ->each(fn (User $u) => $u->revokePermissionTo(RolesSeeder::FUNCOES_OSC));
    }
};
