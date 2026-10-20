<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * A Comissão de Monitoramento e a Comissão de Avaliação são uma comissão só: volta o perfil único, com as
 * permissões das duas, e quem tinha qualquer uma passa a ele.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['monitoramento', 'prestacao_contas'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $unica = Role::findOrCreate('comissao_monitoramento_avaliacao', 'web');
        $unica->syncPermissions(['monitoramento', 'prestacao_contas']);

        foreach (['comissao_monitoramento', 'comissao_avaliacao'] as $nome) {
            $velha = Role::where('name', $nome)->where('guard_name', 'web')->first();
            if (!$velha) {
                continue;
            }
            // Quem tinha as duas fica com uma linha só.
            foreach (DB::table('model_has_roles')->where('role_id', $velha->id)->get() as $vinculo) {
                DB::table('model_has_roles')->updateOrInsert(
                    ['role_id' => $unica->id, 'model_type' => $vinculo->model_type, 'model_id' => $vinculo->model_id],
                );
            }
            $velha->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
    }
};
