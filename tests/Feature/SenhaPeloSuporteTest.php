<?php

namespace Tests\Feature;

use App\Models\Chamado;
use App\Models\Osc;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Senha esquecida vira chamado de suporte; quem atende define uma provisória;
 * senha definida por outra pessoa se troca no primeiro acesso.
 */
class SenhaPeloSuporteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    private function conta(string $papel, string $setor, array $extra = []): User
    {
        $u = User::factory()->create(array_merge([
            'setor' => $setor, 'status' => true, 'approval_status' => 'aprovado',
        ], $extra));
        $u->assignRole($papel);

        return $u->fresh();
    }

    private function pedido(array $extra = []): array
    {
        return array_merge([
            'identificacao' => 'fulana@example.com', 'nome' => 'Fulana', 'contato' => '(31) 99999-0000',
        ], $extra);
    }

    // ── O pedido, sem login ──────────────────────────────────────────────

    public function test_a_tela_de_entrada_leva_ao_pedido_e_o_fluxo_de_email_saiu(): void
    {
        $this->get('/login')->assertSee('Esqueci minha senha')->assertSee(route('password.request'), false);
        $this->get('/esqueci-a-senha')->assertOk();
        $this->get('/forgot-password')->assertNotFound();
    }

    public function test_pedido_abre_chamado_de_acesso_ligado_a_conta(): void
    {
        $fulana = $this->conta('cadastrador', 'ug', ['email' => 'fulana@example.com']);

        $this->post('/esqueci-a-senha', $this->pedido())->assertRedirect('/login')->assertSessionHas('status');

        $chamado = Chamado::sole();
        $this->assertSame('acesso', $chamado->categoria);
        $this->assertSame($fulana->id, $chamado->conta_id);
        $this->assertNull($chamado->user_id);
        $this->assertSame('(31) 99999-0000', $chamado->contato);
    }

    public function test_a_resposta_nao_revela_se_a_conta_existe(): void
    {
        $this->conta('cadastrador', 'ug', ['email' => 'fulana@example.com']);

        $existe = $this->post('/esqueci-a-senha', $this->pedido())->getSession()->get('status');
        $naoExiste = $this->post('/esqueci-a-senha', $this->pedido(['identificacao' => 'ninguem@example.com']))->getSession()->get('status');

        $this->assertSame($existe, $naoExiste);
        $this->assertNull(Chamado::where('assunto', 'like', '%ninguem%')->value('conta_id'));
    }

    public function test_campo_isca_preenchido_nao_abre_chamado(): void
    {
        $this->post('/esqueci-a-senha', $this->pedido(['site' => 'http://spam.example']))->assertRedirect('/login');

        $this->assertSame(0, Chamado::count());
    }

    public function test_pedido_repetido_continua_o_mesmo_chamado(): void
    {
        $this->conta('cadastrador', 'ug', ['email' => 'fulana@example.com']);

        $this->post('/esqueci-a-senha', $this->pedido());
        $this->post('/esqueci-a-senha', $this->pedido(['mensagem' => 'Ainda não recebi.']));

        $this->assertSame(1, Chamado::count());
        $this->assertSame(2, Chamado::sole()->mensagens()->count());
    }

    public function test_pedidos_em_excesso_sao_barrados(): void
    {
        foreach (range(1, 3) as $i) {
            $this->post('/esqueci-a-senha', $this->pedido(['identificacao' => "p{$i}@example.com"]))->assertRedirect();
        }

        $this->post('/esqueci-a-senha', $this->pedido(['identificacao' => 'p4@example.com']))->assertStatus(429);
    }

    // ── Quem atende define a provisória ──────────────────────────────────

    private function pedidoPara(User $conta): Chamado
    {
        $this->post('/esqueci-a-senha', $this->pedido(['identificacao' => $conta->email]));

        return Chamado::where('conta_id', $conta->id)->sole();
    }

    public function test_quem_atende_gera_senha_provisoria_mostrada_uma_vez_e_nao_gravada(): void
    {
        $fulana  = $this->conta('cadastrador', 'ug');
        $chamado = $this->pedidoPara($fulana);
        $scp     = $this->conta('analista_tecnico_scp', 'scp');

        $resposta = $this->actingAs($scp)->post("/suporte/{$chamado->id}/senha-provisoria");
        $senha = $resposta->getSession()->get('senha_provisoria');

        $this->assertNotEmpty($senha);
        $fulana = $fulana->fresh();
        $this->assertTrue(Hash::check($senha, $fulana->password));
        $this->assertTrue($fulana->deve_trocar_senha);

        $nota = $chamado->mensagens()->where('interna', true)->sole();
        $this->assertStringNotContainsString($senha, $nota->mensagem);
    }

    public function test_quem_nao_atende_nao_gera_senha(): void
    {
        $fulana  = $this->conta('cadastrador', 'ug');
        $chamado = $this->pedidoPara($fulana);

        $this->actingAs($this->conta('cadastrador', 'ug'))
            ->post("/suporte/{$chamado->id}/senha-provisoria")->assertForbidden();
    }

    public function test_scp_nao_redefine_senha_de_administrador_mas_o_ti_sim(): void
    {
        $admin   = $this->conta('administrador_setorial', 'ti');
        $chamado = $this->pedidoPara($admin);

        $this->actingAs($this->conta('analista_tecnico_scp', 'scp'))
            ->post("/suporte/{$chamado->id}/senha-provisoria")->assertForbidden();

        $this->actingAs($this->conta('administrador_setorial', 'ti'))
            ->post("/suporte/{$chamado->id}/senha-provisoria")->assertSessionHas('senha_provisoria');
    }

    // ── Troca obrigatória ────────────────────────────────────────────────

    public function test_senha_definida_por_outro_obriga_a_troca_antes_de_tudo(): void
    {
        $fulana = $this->conta('cadastrador', 'ug', ['password' => 'Provisoria1', 'deve_trocar_senha' => true]);

        $this->actingAs($fulana)->get('/dashboard')->assertRedirect(route('senha.trocar'));

        $this->actingAs($fulana)->put('/trocar-senha', ['password' => 'Provisoria1', 'password_confirmation' => 'Provisoria1'])
            ->assertSessionHasErrors('password');

        $this->actingAs($fulana)->put('/trocar-senha', ['password' => 'MinhaSenha9', 'password_confirmation' => 'MinhaSenha9'])
            ->assertRedirect(route('dashboard'));

        $fulana = $fulana->fresh();
        $this->assertFalse($fulana->deve_trocar_senha);
        $this->assertTrue(Hash::check('MinhaSenha9', $fulana->password));
        $this->actingAs($fulana)->get('/dashboard')->assertOk();
    }

    public function test_contas_criadas_por_terceiros_nascem_com_a_troca_obrigatoria(): void
    {
        $osc = Osc::forceCreate(['name' => 'OSC', 'cnpj' => '11.111.111/0001-11', 'email' => 'osc@example.com']);
        $responsavel = $this->conta('responsavel_legal', 'osc', ['osc_id' => $osc->id]);
        $osc->forceFill(['user_id' => $responsavel->id])->save();

        $this->actingAs($responsavel)->post('/portal/usuarios', [
            'name' => 'Integrante', 'email' => 'integrante@example.com',
            'password' => 'Senha123', 'password_confirmation' => 'Senha123',
        ]);

        $this->assertTrue(User::where('email', 'integrante@example.com')->sole()->deve_trocar_senha);
    }

    public function test_administrador_que_troca_a_senha_de_alguem_obriga_a_troca_mas_nao_a_propria(): void
    {
        $ti     = $this->conta('administrador_setorial', 'ti');
        $fulana = $this->conta('cadastrador', 'ug');
        $form   = fn (User $u) => ['name' => $u->name, 'email' => $u->email, 'status' => 1, 'roles' => $u->getRoleNames()->all(),
                                   'setor' => $u->setor, 'password' => 'NovaSenha1', 'password_confirmation' => 'NovaSenha1'];

        $this->actingAs($ti)->put("/usuarios/{$fulana->id}", $form($fulana))->assertSessionHasNoErrors();
        $this->assertTrue($fulana->fresh()->deve_trocar_senha);

        $this->actingAs($ti)->put("/usuarios/{$ti->id}", $form($ti))->assertSessionHasNoErrors();
        $this->assertFalse($ti->fresh()->deve_trocar_senha);
    }
}
