<?php

namespace Tests\Feature;

use App\Models\Orgao;
use App\Models\TermoAceite;
use App\Models\User;
use App\Support\TermosDeUso;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use LogicException;
use Tests\TestCase;

/**
 * Termos de Uso: toda conta, da Prefeitura ou da OSC, aceita a versão vigente — no cadastro ou no primeiro acesso —, e
 * cada aceite fica registrado com a versão, o hash do texto lido, o IP e o navegador.
 */
class TermosDeUsoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Mail::fake();
    }

    private function servidor(array $extra = []): User
    {
        $u = User::factory()->semAceiteDosTermos()->create($extra + ['setor' => 'scp', 'status' => true, 'approval_status' => 'aprovado']);
        $u->assignRole('analista_tecnico_scp');

        return $u;
    }

    public function test_o_texto_da_versao_nao_muda_sem_versao_nova(): void
    {
        // Falhou? O texto de termos/_texto mudou: suba TermosDeUso::VERSAO e VIGENTE_DESDE e atualize o HASH.
        $this->assertSame(TermosDeUso::HASH, hash('sha256', TermosDeUso::texto()));
    }

    public function test_os_termos_sao_publicos(): void
    {
        $this->get('/termos-de-uso')->assertOk()->assertSee('Termos de Uso')->assertSee('Versão ' . TermosDeUso::VERSAO)
            ->assertSee('Lei Federal nº 13.709/2018');
        $this->get('/login')->assertSee(route('termos.index'), false);
    }

    public function test_conta_sem_aceite_so_entra_depois_de_aceitar_e_volta_para_onde_ia(): void
    {
        $scp = $this->servidor();

        $this->actingAs($scp)->get('/caixa')->assertRedirect(route('termos.aceitar'));
        $this->actingAs($scp)->get(route('termos.aceitar'))->assertOk()->assertSee('Li e aceito os Termos de Uso')->assertSee('Não aceito — sair');

        $this->actingAs($scp)->post(route('termos.aceitar.salvar'))->assertSessionHasErrors('aceite');
        $this->assertSame(0, TermoAceite::count());

        $this->actingAs($scp)->post(route('termos.aceitar.salvar'), ['aceite' => '1'])->assertRedirect('/caixa');

        $aceite = TermoAceite::sole();
        $this->assertSame([$scp->id, TermosDeUso::VERSAO, TermosDeUso::HASH, 'primeiro_acesso', '127.0.0.1'],
            [(int) $aceite->user_id, $aceite->versao, $aceite->hash_texto, $aceite->origem, $aceite->ip]);
        $this->assertSame(TermosDeUso::VERSAO, $scp->fresh()->termos_versao);
        $this->actingAs($scp->fresh())->get('/caixa')->assertOk();
        $this->actingAs($scp->fresh())->get('/termos-de-uso')->assertSee('Você aceitou a versão ' . TermosDeUso::VERSAO);

        // O registro não se altera.
        $this->assertThrows(fn () => $aceite->forceFill(['versao' => '0.1'])->save(), LogicException::class);
    }

    public function test_versao_nova_pede_novo_aceite(): void
    {
        $scp = $this->servidor(['termos_versao' => '0.9', 'termos_aceitos_em' => now()->subYear()]);

        $this->actingAs($scp)->get('/dashboard')->assertRedirect(route('termos.aceitar'));
        $this->actingAs($scp)->get(route('termos.aceitar'))->assertSee('Os Termos de Uso e Adesão foram atualizados');
        $this->actingAs($scp)->post(route('termos.aceitar.salvar'), ['aceite' => '1'])->assertRedirect();

        $this->assertSame('nova_versao', TermoAceite::sole()->origem);
    }

    public function test_sem_aceite_ainda_da_para_sair_e_a_troca_de_senha_vem_antes(): void
    {
        $scp = $this->servidor(['deve_trocar_senha' => true]);

        $this->actingAs($scp)->get('/dashboard')->assertRedirect(route('senha.trocar'));
        $this->actingAs($scp)->get(route('senha.trocar'))->assertOk();
        $this->actingAs($scp)->post('/logout')->assertRedirect();
        $this->assertGuest();
    }

    public function test_o_auditor_somente_leitura_tambem_aceita(): void
    {
        $auditor = User::factory()->semAceiteDosTermos()->create(['setor' => 'controle_interno', 'status' => true, 'approval_status' => 'aprovado']);
        $auditor->assignRole('auditor_geral');

        $this->actingAs($auditor)->post(route('termos.aceitar.salvar'), ['aceite' => '1'])->assertRedirect();
        $this->assertTrue(TermosDeUso::aceitou($auditor->fresh()));
    }

    public function test_o_servidor_aceita_ao_solicitar_acesso(): void
    {
        $educacao = Orgao::forceCreate(['name' => 'Educação']);
        $dados = ['name' => 'Maria Secretária', 'email' => 'maria@saogoncalo.mg.gov.br', 'setor' => 'ug', 'orgao_id' => $educacao->id,
            'perfis' => ['responsavel_unidade_gestora'], 'password' => 'senha123', 'password_confirmation' => 'senha123', 'cpf' => '52998224725'];

        $this->get('/register')->assertSee('name="aceite_termos"', false);
        $this->post('/register', $dados)->assertSessionHasErrors('aceite_termos');
        $this->assertSame(0, User::where('email', 'maria@saogoncalo.mg.gov.br')->count());

        $this->post('/register', $dados + ['aceite_termos' => '1'])->assertSessionHasNoErrors();
        $maria = User::where('email', 'maria@saogoncalo.mg.gov.br')->sole();
        $this->assertSame(['cadastro', TermosDeUso::VERSAO], [TermoAceite::sole()->origem, $maria->termos_versao]);

        // Aprovada, entra direto: o aceite já foi dado no cadastro.
        $maria->forceFill(['approval_status' => 'aprovado'])->save();
        $this->actingAs($maria->fresh())->get('/dashboard')->assertOk();
    }

    public function test_a_osc_aceita_ao_se_cadastrar(): void
    {
        $dados = ['resp_nome' => 'Ana Responsável', 'email' => 'ana@example.com', 'cpf' => '529.982.247-25',
            'password' => 'senha123', 'password_confirmation' => 'senha123', 'osc_name' => 'Associação Viver',
            'cnpj' => '11.111.111/0001-11', 'tipo' => array_key_first(\App\Models\Osc::TIPOS), 'cidade' => 'São Gonçalo do Rio Abaixo', 'estado' => 'MG'];

        $this->get('/cadastro/osc')->assertSee('name="aceite_termos"', false);
        $this->post('/cadastro/osc', $dados)->assertSessionHasErrors('aceite_termos');
        $this->assertGuest();

        $this->post('/cadastro/osc', $dados + ['aceite_termos' => '1'])->assertSessionHasNoErrors()->assertRedirect(route('portal.index'));
        $this->assertSame('cadastro', TermoAceite::sole()->origem);
        $this->get(route('portal.index'))->assertOk();
    }
}
