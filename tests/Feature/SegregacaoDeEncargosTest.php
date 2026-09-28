<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Gestor da Parceria, Comissão de Seleção, Comissão de Monitoramento e
 * Comissão de Avaliação: a mesma pessoa tem um desses encargos, no máximo
 * (decisão da gestão, 28/09/2026). Ver User::ENCARGOS_QUE_NAO_ACUMULAM.
 */
class SegregacaoDeEncargosTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Mail::fake();

        $this->admin = User::factory()->create(['setor' => 'ti', 'status' => true, 'approval_status' => 'aprovado']);
        $this->admin->assignRole('administrador_setorial');
    }

    private function cadastro(array $roles, string $email = 'servidor@example.com'): array
    {
        return ['name' => 'Servidor', 'email' => $email, 'setor' => 'ug', 'status' => 1, 'roles' => $roles,
            'password' => 'Senha123', 'password_confirmation' => 'Senha123'];
    }

    public function test_monitoramento_e_avaliacao_viraram_dois_perfis_com_uma_permissao_cada(): void
    {
        $this->assertNull(Role::where('name', 'comissao_monitoramento_avaliacao')->first());
        $this->assertSame(['monitoramento'], Role::findByName('comissao_monitoramento')->permissions->pluck('name')->all());
        $this->assertSame(['prestacao_contas'], Role::findByName('comissao_avaliacao')->permissions->pluck('name')->all());
    }

    public function test_cadastro_pelo_administrador_nao_aceita_dois_encargos(): void
    {
        $this->actingAs($this->admin)->post('/usuarios', $this->cadastro(['gestor_parceria', 'comissao_selecao']))
            ->assertSessionHasErrors(['roles' => 'A mesma pessoa não pode acumular Comissão de Seleção e Gestor da Parceria. Gestor da Parceria, Comissão de Seleção, Comissão de Monitoramento e Comissão de Avaliação são encargos que se fiscalizam: escolha um só.']);

        $this->assertNull(User::where('email', 'servidor@example.com')->first());
    }

    public function test_monitoramento_e_avaliacao_tambem_nao_se_acumulam(): void
    {
        $this->actingAs($this->admin)->post('/usuarios', $this->cadastro(['comissao_monitoramento', 'comissao_avaliacao']))
            ->assertSessionHasErrors('roles');
    }

    public function test_um_encargo_so_passa(): void
    {
        $this->actingAs($this->admin)->post('/usuarios', $this->cadastro(['gestor_parceria']))->assertSessionHasNoErrors();

        $this->assertTrue(User::where('email', 'servidor@example.com')->sole()->hasRole('gestor_parceria'));
    }

    public function test_editar_para_acumular_e_barrado_e_os_perfis_ficam_como_estavam(): void
    {
        $gestor = User::factory()->create(['setor' => 'ug', 'status' => true, 'approval_status' => 'aprovado']);
        $gestor->assignRole('gestor_parceria');

        $this->actingAs($this->admin)->put("/usuarios/{$gestor->id}", [
            'name' => $gestor->name, 'email' => $gestor->email, 'setor' => 'ug', 'status' => 1,
            'roles' => ['gestor_parceria', 'comissao_avaliacao'],
        ])->assertSessionHasErrors('roles');

        $this->assertSame(['gestor_parceria'], $gestor->fresh()->getRoleNames()->all());
    }

    public function test_chefia_de_setor_nao_cadastra_equipe_acumulando_encargos(): void
    {
        $chefe = User::factory()->create(['setor' => 'ug', 'status' => true, 'approval_status' => 'aprovado']);
        $chefe->assignRole(['chefe_setor', 'responsavel_unidade_gestora']);

        $this->actingAs($chefe->fresh())->post('/meus-usuarios', [
            'name' => 'Novo', 'email' => 'novo@example.com', 'matricula' => '123', 'solicitacao_obs' => 'Equipe',
            'password' => 'Senha123', 'password_confirmation' => 'Senha123',
            'perfis' => ['comissao_selecao', 'comissao_monitoramento'],
        ])->assertSessionHasErrors(['perfis']);

        $this->assertStringContainsString('não pode acumular', session('errors')->first('perfis'));
        $this->assertNull(User::where('email', 'novo@example.com')->first());
    }

    public function test_conta_pendente_que_acumula_nao_e_aprovada(): void
    {
        $pendente = User::factory()->create(['setor' => 'ug', 'status' => true, 'approval_status' => 'pendente']);
        $pendente->assignRole(['gestor_parceria', 'comissao_selecao']);

        $this->actingAs($this->admin)->patch("/usuarios/{$pendente->id}/aprovar")->assertSessionHasErrors('roles');

        $this->assertTrue($pendente->fresh()->isPendente());
    }
}
