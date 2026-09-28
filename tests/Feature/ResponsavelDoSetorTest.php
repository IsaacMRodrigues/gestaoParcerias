<?php

namespace Tests\Feature;

use App\Models\Orgao;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Quem cadastra a equipe é o responsável do setor (decisão da gestão,
 * 28/09/2026). Na UG e na SEPLAN o responsável tem perfil próprio e é ele o
 * chefe; nos demais setores, é quem tem o perfil Chefe de Setor.
 * Ver User::RESPONSAVEL_DO_SETOR.
 */
class ResponsavelDoSetorTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Mail::fake();

        $this->admin = $this->conta(['administrador_setorial'], 'ti');
    }

    private function conta(array $perfis, string $setor, ?Orgao $orgao = null): User
    {
        $u = User::factory()->create(['setor' => $setor, 'orgao_id' => $orgao?->id, 'status' => true, 'approval_status' => 'aprovado']);
        $u->assignRole($perfis);

        return $u->fresh();
    }

    public function test_quem_cadastra_a_equipe_e_o_responsavel_do_setor(): void
    {
        $orgao = Orgao::forceCreate(['name' => 'Educação']);

        $this->assertTrue($this->conta(['responsavel_unidade_gestora'], 'ug', $orgao)->podeCadastrarNoSetor());
        $this->assertTrue($this->conta(['responsavel_seplan'], 'seplan')->podeCadastrarNoSetor());
        $this->assertTrue($this->conta(['analista_tecnico_scp', 'chefe_setor'], 'scp')->podeCadastrarNoSetor());

        $this->assertFalse($this->conta(['cadastrador'], 'ug', $orgao)->podeCadastrarNoSetor(), 'demais usuários não cadastram');
        $this->assertFalse($this->conta(['analista_orcamentario_financeiro'], 'seplan')->podeCadastrarNoSetor());
        $this->assertFalse($this->conta(['analista_tecnico_scp'], 'scp')->podeCadastrarNoSetor());
    }

    public function test_na_ug_o_chefe_de_setor_so_pode_ser_o_responsavel(): void
    {
        $dados = fn (array $roles, string $setor, string $email) => ['name' => 'Servidor', 'email' => $email, 'setor' => $setor,
            'status' => 1, 'roles' => $roles, 'password' => 'Senha123', 'password_confirmation' => 'Senha123'];

        $this->actingAs($this->admin)->post('/usuarios', $dados(['cadastrador', 'chefe_setor'], 'ug', 'a@example.com'))
            ->assertSessionHasErrors('roles');
        $this->assertNull(User::where('email', 'a@example.com')->first());

        $this->actingAs($this->admin)->post('/usuarios', $dados(['analista_orcamentario_financeiro', 'chefe_setor'], 'seplan', 'b@example.com'))
            ->assertSessionHasErrors('roles');

        // Nos setores sem responsável próprio, o chefe de setor continua sendo quem o administrador designar.
        $this->actingAs($this->admin)->post('/usuarios', $dados(['analista_tecnico_scp', 'chefe_setor'], 'scp', 'c@example.com'))
            ->assertSessionHasNoErrors();
    }

    public function test_o_responsavel_nao_nomeia_outro_responsavel(): void
    {
        $orgao = Orgao::forceCreate(['name' => 'Educação']);

        $this->assertArrayNotHasKey('responsavel_unidade_gestora', $this->conta(['responsavel_unidade_gestora'], 'ug', $orgao)->perfisQuePodeConceder());
        $this->assertArrayNotHasKey('responsavel_seplan', $this->conta(['responsavel_seplan'], 'seplan')->perfisQuePodeConceder());
        $this->assertArrayNotHasKey('chefe_setor', $this->conta(['analista_tecnico_scp', 'chefe_setor'], 'scp')->perfisQuePodeConceder());
    }
}
