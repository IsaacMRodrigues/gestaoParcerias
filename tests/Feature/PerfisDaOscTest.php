<?php

namespace Tests\Feature;

use App\Models\Osc;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Homologação, item 2: os perfis marcados no cadastro de usuário da OSC
 * persistem — ao reabrir, todos continuam marcados.
 *
 * Pelo portal eles sempre gravaram; o que os apagava era a tela de edição da
 * Prefeitura (Cadastros → Usuários), corrigida em 23/09 — ver também
 * ContasDeUsuarioTest. Aqui o caminho inteiro: cadastrar, reabrir, alterar,
 * e a aprovação pela Prefeitura no meio.
 */
class PerfisDaOscTest extends TestCase
{
    use RefreshDatabase;

    private User $rl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Mail::fake();

        $osc = Osc::forceCreate(['name' => 'OSC', 'cnpj' => '11.111.111/0001-11', 'email' => 'osc@example.com']);
        $this->rl = User::factory()->create(['osc_id' => $osc->id, 'setor' => 'osc', 'status' => true, 'approval_status' => 'aprovado']);
        $this->rl->assignRole('responsavel_legal');
        $osc->forceFill(['user_id' => $this->rl->id])->save();
    }

    private function cadastrar(array $perfis): User
    {
        $this->actingAs($this->rl)->post('/portal/usuarios', [
            'name' => 'Integrante', 'email' => 'integrante@example.com',
            'password' => 'Senha123', 'password_confirmation' => 'Senha123',
            'perfis' => $perfis,
        ])->assertSessionHasNoErrors();

        return User::where('email', 'integrante@example.com')->sole();
    }

    /** Os perfis que o "Alterar" da lista da equipe mostra marcados para esta pessoa. */
    private function marcadosAoReabrir(User $u): array
    {
        $html = $this->actingAs($this->rl)->get('/portal/usuarios')->assertOk()->getContent();

        $inicio = strpos($html, 'action="' . route('portal.usuarios.funcoes', $u) . '"');
        $this->assertNotFalse($inicio, 'o formulário de Alterar da pessoa deve estar na lista');
        $form = substr($html, $inicio, strpos($html, '</form>', $inicio) - $inicio);

        preg_match_all('/name="perfis\[\]" value="([a-z_]+)"\s*checked/', $form, $m);

        return $m[1];
    }

    public function test_marcar_dois_perfis_salvar_e_reabrir_mantem_todos(): void
    {
        $novo = $this->cadastrar(['cadastrador_proposta', 'cadastrador_prestacao_contas']);

        $esperados = ['membro_osc', 'cadastrador_proposta', 'cadastrador_prestacao_contas'];
        $this->assertEqualsCanonicalizing($esperados, $novo->getRoleNames()->all(), 'gravado no banco');
        $this->assertEqualsCanonicalizing($esperados, $this->marcadosAoReabrir($novo), 'marcados ao reabrir');
    }

    public function test_os_tres_perfis_opcionais_juntos_tambem_persistem(): void
    {
        $novo = $this->cadastrar(['cadastrador_proposta', 'cadastrador_prestacao_contas', 'cadastrador_usuario_entidade']);

        $this->assertCount(4, $this->marcadosAoReabrir($novo));
    }

    public function test_alterar_pela_lista_troca_os_perfis_e_mantem_o_membro(): void
    {
        $novo = $this->cadastrar(['cadastrador_proposta']);

        $this->actingAs($this->rl)->patch("/portal/usuarios/{$novo->id}/funcoes", [
            'perfis' => ['cadastrador_usuario_entidade', 'cadastrador_prestacao_contas'], 'funcoes' => [],
        ])->assertSessionHasNoErrors();

        $this->assertEqualsCanonicalizing(
            ['membro_osc', 'cadastrador_usuario_entidade', 'cadastrador_prestacao_contas'],
            $this->marcadosAoReabrir($novo->fresh()),
        );
    }

    public function test_a_aprovacao_pela_prefeitura_nao_mexe_nos_perfis(): void
    {
        $novo = $this->cadastrar(['cadastrador_proposta', 'cadastrador_prestacao_contas']);

        $scp = User::factory()->create(['setor' => 'scp', 'status' => true, 'approval_status' => 'aprovado']);
        $scp->assignRole('analista_tecnico_scp');
        $this->actingAs($scp)->patch("/usuarios/{$novo->id}/aprovar")->assertSessionHasNoErrors();

        $novo = $novo->fresh();
        $this->assertTrue($novo->isAprovado());
        $this->assertEqualsCanonicalizing(
            ['membro_osc', 'cadastrador_proposta', 'cadastrador_prestacao_contas'],
            $novo->getRoleNames()->all(),
        );
    }
}
