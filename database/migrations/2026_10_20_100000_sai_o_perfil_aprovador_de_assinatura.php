<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * O perfil Aprovador de Assinatura Eletrônica não tinha permissão nem fluxo: sai, e quem o tinha o perde.
 */
return new class extends Migration
{
    public function up(): void
    {
        $id = DB::table('roles')->where('name', 'aprovador_assinatura_eletronica')->value('id');
        if ($id) {
            DB::table('model_has_roles')->where('role_id', $id)->delete();
            DB::table('role_has_permissions')->where('role_id', $id)->delete();
            DB::table('roles')->where('id', $id)->delete();
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
    }
};
