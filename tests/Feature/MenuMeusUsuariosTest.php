<?php

namespace Tests\Feature;

use App\Models\Orgao;
use App\Models\Osc;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Meus usuários", no menu da conta, aparece para todo mundo que cadastra usuários e leva à tela de cada um.
 */
class MenuMeusUsuariosTest extends TestCase
{
    use RefreshDatabase;

    private function servidor(string $setor, array $perfis, ?int $orgao = null): User
    {
        $u = User::factory()->create(['setor' => $setor, 'orgao_id' => $orgao, 'status' => true,
            'approval_status' => 'aprovado', 'deve_trocar_senha' => false]);
        $u->assignRole($perfis);

        return $u->fresh();
    }

    public function test_aparece_para_quem_cadastra_e_leva_a_tela_certa(): void
    {
        $this->seed(RolesSeeder::class);
        $educacao = Orgao::forceCreate(['name' => 'Educação']);
        $link = fn (string $rota) => 'href="' . route($rota) . '">Meus usuários</a>';

        $casos = [
            [$this->servidor('ti', ['administrador_setorial']), 'orgaos.index'],
            [$this->servidor('scp', ['analista_tecnico_scp']), 'orgaos.index'],
            [$this->servidor('ug', ['responsavel_unidade_gestora'], $educacao->id), 'subusuarios.index'],
            [$this->servidor('seplan', ['responsavel_seplan']), 'subusuarios.index'],
        ];
        foreach ($casos as [$quem, $rota]) {
            $this->actingAs($quem)->get(route('dashboard'))->assertOk()->assertSee($link($rota), false);
        }

        // Quem não cadastra não vê.
        $juridico = $this->servidor('ug', ['analista_juridico'], $educacao->id);
        $this->actingAs($juridico)->get(route('dashboard'))->assertOk()->assertDontSee('Meus usuários');

        // Na OSC, o responsável legal vê; o membro, não.
        $osc = Osc::forceCreate(['name' => 'OSC do Teste', 'cnpj' => '11.111.111/0001-11']);
        $rl = User::factory()->create(['osc_id' => $osc->id, 'status' => true, 'approval_status' => 'aprovado', 'deve_trocar_senha' => false]);
        $rl->assignRole('responsavel_legal');
        $osc->forceFill(['user_id' => $rl->id])->save();
        $this->actingAs($rl->fresh())->get(route('portal.index'))->assertOk()
            ->assertSee(route('portal.usuarios.index'), false)->assertSee('Meus usuários');
        $membro = User::factory()->create(['osc_id' => $osc->id, 'status' => true, 'approval_status' => 'aprovado', 'deve_trocar_senha' => false]);
        $membro->assignRole('membro_osc');
        $this->actingAs($membro->fresh())->get(route('portal.index'))->assertOk()->assertDontSee('Meus usuários');
    }
}
