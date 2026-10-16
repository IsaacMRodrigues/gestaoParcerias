<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * A SCP passa a aprovar toda conta nova, de servidor e de integrante de OSC: a permissão que
 * só valia para OSC (`aprovar_contas_osc`) vira `aprovar_contas`, mantendo quem já a tinha.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')->where('name', 'aprovar_contas_osc')->update(['name' => 'aprovar_contas']);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::table('permissions')->where('name', 'aprovar_contas')->update(['name' => 'aprovar_contas_osc']);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
