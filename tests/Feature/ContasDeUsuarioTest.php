<?php

namespace Tests\Feature;

use App\Models\Orgao;
use App\Models\Osc;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Contas: a tela da Prefeitura não corrompe conta de OSC, e ninguém apaga o
 * autor do que foi feito no processo.
 */
class ContasDeUsuarioTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Osc $osc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);

        $this->admin = User::factory()->create(['setor' => 'ti', 'status' => true, 'approval_status' => 'aprovado']);
        $this->admin->assignRole('administrador_setorial');

        $this->osc = Osc::forceCreate(['name' => 'OSC Teste', 'cnpj' => '11.111.111/0001-11', 'email' => 'osc@example.com']);
    }

    private function integrante(): User
    {
        $u = User::factory()->create([
            'osc_id' => $this->osc->id, 'setor' => 'osc', 'status' => true, 'approval_status' => 'aprovado',
        ]);
        $u->assignRole(['membro_osc', 'cadastrador_proposta']);
        $u->givePermissionTo(['osc_propostas', 'osc_documentos']);

        return $u->fresh();
    }

    private function formulario(User $u, array $extra = []): array
    {
        return array_merge(['name' => $u->name, 'email' => $u->email, 'status' => 1], $extra);
    }

    // ── Item 4: editar conta de OSC pela tela da Prefeitura ──────────────

    public function test_salvar_conta_de_osc_sem_perfil_da_prefeitura_funciona(): void
    {
        $membro = $this->integrante();

        $this->actingAs($this->admin)
            ->put("/usuarios/{$membro->id}", $this->formulario($membro, ['name' => 'Nome Corrigido']))
            ->assertSessionHasNoErrors();

        $membro = $membro->fresh();
        $this->assertSame('Nome Corrigido', $membro->name);
        $this->assertEqualsCanonicalizing(['membro_osc', 'cadastrador_proposta'], $membro->getRoleNames()->all());
        $this->assertEqualsCanonicalizing(['osc_propostas', 'osc_documentos'], $membro->getPermissionNames()->all());
    }

    public function test_perfil_lotacao_e_secretaria_forjados_sao_ignorados_na_conta_de_osc(): void
    {
        $membro = $this->integrante();
        $orgao  = Orgao::forceCreate(['name' => 'Administração']);

        // Um perfil comum da Prefeitura, que a validação antiga aceitava — e
        // trocava pelo "Membro da OSC".
        $this->actingAs($this->admin)->put("/usuarios/{$membro->id}", $this->formulario($membro, [
            'roles' => ['cadastrador'], 'setor' => 'ug', 'orgao_id' => $orgao->id,
        ]))->assertSessionHasNoErrors();

        $membro = $membro->fresh();
        $this->assertFalse($membro->hasRole('cadastrador'));
        $this->assertTrue($membro->hasRole('membro_osc'));
        $this->assertSame('osc', $membro->setor);
        $this->assertNull($membro->orgao_id);
    }

    public function test_a_tela_de_edicao_da_conta_de_osc_nao_oferece_perfis(): void
    {
        $membro = $this->integrante();

        $this->actingAs($this->admin)->get("/usuarios/{$membro->id}/edit")
            ->assertOk()
            ->assertDontSee('name="roles[]"', false)
            ->assertSee('Usuários da organização');
    }

    public function test_servidor_continua_exigindo_e_gravando_perfil(): void
    {
        $servidor = User::factory()->create(['setor' => 'ug', 'status' => true, 'approval_status' => 'aprovado']);
        $servidor->assignRole('cadastrador');

        $this->actingAs($this->admin)->put("/usuarios/{$servidor->id}", $this->formulario($servidor))
            ->assertSessionHasErrors('roles');

        $this->actingAs($this->admin)->put("/usuarios/{$servidor->id}", $this->formulario($servidor, ['roles' => ['contador']]));
        $this->assertSame(['contador'], $servidor->fresh()->getRoleNames()->all());
    }

    // ── Item 5: ninguém apaga o autor ────────────────────────────────────

    public function test_nao_existe_mais_excluir_a_propria_conta(): void
    {
        $servidor = User::factory()->create(['setor' => 'ug', 'status' => true, 'approval_status' => 'aprovado']);
        $servidor->assignRole('cadastrador');

        $this->actingAs($servidor)->delete('/profile', ['password' => 'password'])->assertStatus(405);

        $this->assertNotNull($servidor->fresh());
    }

    public function test_administrador_nao_exclui_quem_deixou_autoria_que_o_banco_apagaria_em_silencio(): void
    {
        // oscs.user_id é nullOnDelete: antes, excluir o responsável legal
        // passava e a OSC ficava sem titular, sem aviso.
        $responsavel = User::factory()->create(['osc_id' => $this->osc->id, 'setor' => 'osc', 'status' => true, 'approval_status' => 'aprovado']);
        $responsavel->assignRole('responsavel_legal');
        $this->osc->forceFill(['user_id' => $responsavel->id])->save();

        $this->actingAs($this->admin)->delete("/usuarios/{$responsavel->id}")
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'responsável legal'));

        $this->assertNotNull($responsavel->fresh());
        $this->assertSame($responsavel->id, $this->osc->fresh()->user_id);
    }

    public function test_administrador_ainda_exclui_conta_que_nunca_fez_nada(): void
    {
        $vazia = User::factory()->create(['setor' => 'ug', 'status' => true, 'approval_status' => 'aprovado']);

        $this->actingAs($this->admin)->delete("/usuarios/{$vazia->id}")->assertSessionHasNoErrors();

        $this->assertNull($vazia->fresh());
    }
}
