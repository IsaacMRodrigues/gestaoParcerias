<?php

namespace Tests\Feature;

use App\Models\Orgao;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A SCP vê todos os órgãos e usuários, como o administrador, mas só consulta:
 * criar, editar e remover seguem com quem tem `cadastros`.
 */
class ScpVeOsUsuariosTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_scp_ve_todos_os_usuarios_sem_poder_alterar(): void
    {
        $this->seed(RolesSeeder::class);
        $educacao = Orgao::forceCreate(['name' => 'Educação']);
        $maria = User::factory()->create(['name' => 'Maria da Educação', 'setor' => 'ug', 'orgao_id' => $educacao->id]);
        $maria->assignRole('responsavel_unidade_gestora');
        $scp = User::factory()->create(['setor' => 'scp', 'deve_trocar_senha' => false]);
        $scp->assignRole('analista_tecnico_scp');

        $this->actingAs($scp)->get(route('dashboard'))->assertSee(route('orgaos.index'));
        $this->actingAs($scp)->get(route('orgaos.index'))->assertOk()
            ->assertSee('Maria da Educação')->assertSee($scp->name)
            ->assertDontSee('+ Novo usuário')->assertDontSee(route('usuarios.edit', $maria))->assertDontSee('Editar órgão');

        $this->actingAs($scp)->get(route('usuarios.edit', $maria))->assertForbidden();
        $this->actingAs($scp)->get(route('orgaos.edit', $educacao))->assertForbidden();
        $this->actingAs($scp)->delete(route('usuarios.destroy', $maria))->assertForbidden();
        $this->assertNotNull($maria->fresh());

        // O administrador continua com a lista e as ações.
        $admin = User::factory()->create(['setor' => 'ti', 'deve_trocar_senha' => false]);
        $admin->assignRole('administrador_setorial');
        $this->actingAs($admin)->get(route('orgaos.index'))->assertOk()
            ->assertSee('+ Novo usuário')->assertSee(route('usuarios.edit', $maria));
    }
}
