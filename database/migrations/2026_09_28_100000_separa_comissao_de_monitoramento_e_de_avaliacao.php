<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * "Comissão de Monitoramento e Avaliação" vira dois perfis (decisão da gestão,
 * 28/09/2026):
 *
 * - Comissão de Monitoramento — `monitoramento`: acompanha a execução;
 * - Comissão de Avaliação — `prestacao_contas`: avalia a prestação de contas.
 *
 * Separados para que a mesma pessoa não acompanhe e avalie a mesma parceria —
 * ver User::ENCARGOS_QUE_NAO_ACUMULAM. Quem tivesse o perfil antigo fica com o
 * de Monitoramento (a Avaliação se designa de novo). Na data da migração
 * ninguém o tinha, aqui nem em produção.
 */
return new class extends Migration
{
    public function up(): void
    {
        $monitoramento = $this->perfil('comissao_monitoramento', ['monitoramento']);
        $this->perfil('comissao_avaliacao', ['prestacao_contas']);

        $antigo = Role::where('name', 'comissao_monitoramento_avaliacao')->where('guard_name', 'web')->first();
        if ($antigo) {
            DB::table('model_has_roles')->where('role_id', $antigo->id)->update(['role_id' => $monitoramento->id]);
            $antigo->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $antigo = $this->perfil('comissao_monitoramento_avaliacao', ['monitoramento', 'prestacao_contas']);

        foreach (['comissao_monitoramento', 'comissao_avaliacao'] as $nome) {
            $novo = Role::where('name', $nome)->where('guard_name', 'web')->first();
            if (!$novo) {
                continue;
            }
            // Quem tinha um dos dois volta ao perfil único, sem duplicar a linha.
            foreach (DB::table('model_has_roles')->where('role_id', $novo->id)->get() as $vinculo) {
                DB::table('model_has_roles')->updateOrInsert(
                    ['role_id' => $antigo->id, 'model_type' => $vinculo->model_type, 'model_id' => $vinculo->model_id],
                );
            }
            $novo->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function perfil(string $nome, array $permissoes): Role
    {
        foreach ($permissoes as $p) {
            Permission::findOrCreate($p, 'web');
        }

        $role = Role::findOrCreate($nome, 'web');
        $role->syncPermissions($permissoes);

        return $role;
    }
};
