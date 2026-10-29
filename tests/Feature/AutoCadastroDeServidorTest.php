<?php

namespace Tests\Feature;

use App\Mail\Aviso;
use App\Models\Chamado;
use App\Models\Orgao;
use App\Models\User;
use App\Support\ContasPreCadastradas;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Cada servidor, secretário inclusive, faz o próprio cadastro: escolhe o setor, a Secretaria e os
 * perfis do setor; a SCP confere a identidade e aprova. As contas que o sistema criava prontas são apagadas.
 */
class AutoCadastroDeServidorTest extends TestCase
{
    use RefreshDatabase;

    private Orgao $educacao;
    private User $scp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Mail::fake();
        $this->educacao = Orgao::where('name', 'Educação')->first() ?? Orgao::forceCreate(['name' => 'Educação']);
        $this->scp = User::where('email', 'scp@gmail.com')->first();
        // A conta da migração nasce com a senha provisória pública, e por isso com troca obrigatória.
        $this->scp?->forceFill(['deve_trocar_senha' => false, 'termos_versao' => \App\Support\TermosDeUso::VERSAO])->save();
    }

    private function dados(array $extra = []): array
    {
        return $extra + [
            'name' => 'Maria Secretária', 'email' => 'maria.educacao@saogoncalo.mg.gov.br', 'matricula' => 'M-123',
            'setor' => 'ug', 'orgao_id' => $this->educacao->id, 'perfis' => ['responsavel_unidade_gestora'],
            'password' => 'senha123', 'password_confirmation' => 'senha123', 'aceite_termos' => '1', 'cpf' => '529.982.247-25',
        ];
    }

    public function test_o_ambiente_novo_so_traz_o_administrador_e_a_scp(): void
    {
        $this->assertNotNull($this->scp);
        $this->assertTrue($this->scp->can('aprovar_contas'));
        $this->assertSame(0, User::where('email', 'like', '%@saogoncalo.mg.gov.br')->count());
        $this->assertSame(0, User::whereIn('email', ['prefeito@gmail.com', 'seplan@gmail.com', 'fazenda@provisorio.local'])->count());
    }

    public function test_o_secretario_se_cadastra_como_responsavel_da_ug_e_aguarda_a_scp(): void
    {
        $this->get('/login')->assertOk()->assertSee('Solicite seu acesso')->assertSee(route('register'))->assertSee('Voltar para o início')
            ->assertDontSee('Cadastre sua OSC');
        // A entrada da OSC é a mesma tela, com o cadastro de OSC no lugar do pedido de acesso.
        $this->get(route('login', ['acesso' => 'osc']))->assertOk()->assertSee('Acessar o Portal da OSC')
            ->assertSee('Cadastre sua OSC')->assertDontSee('Solicite seu acesso');
        $this->get('/')->assertSee(route('login', ['acesso' => 'osc']), false);
        $this->get('/register')->assertOk()->assertSee('<title>Solicitar acesso', false)->assertDontSee('Acessar o sistema')
            ->assertSee('Responsável da Unidade Gestora')->assertSee('exclusivo do secretário (Unidade Gestora)')
            ->assertSee('Analista Orçamentário Financeiro')->assertSee('Analista Técnico do SCP')->assertSee('exclusivo: Setor de Convênios e Parcerias (SCP)')
            ->assertDontSee('Aprovador de Assinatura');
        $this->post('/register', $this->dados())->assertRedirect(route('login'))->assertSessionHasNoErrors();

        $maria = User::where('email', 'maria.educacao@saogoncalo.mg.gov.br')->sole();
        $this->assertSame('pendente', $maria->approval_status);
        $this->assertTrue($maria->hasRole('responsavel_unidade_gestora'));
        $this->assertFalse($maria->podeAutenticar(), 'pendente não entra');
        Mail::assertQueued(Aviso::class, fn ($m) => $m->hasTo('scp@gmail.com'));

        // A SCP vê e aprova a conta de servidor (antes só via as de OSC).
        $this->actingAs($this->scp)->get(route('usuarios.pendentes'))->assertOk()
            ->assertSee('Maria Secretária')->assertSee('Pedidos pela própria pessoa');
        $this->actingAs($this->scp)->patch(route('usuarios.aprovar', $maria))->assertSessionHasNoErrors();
        $this->assertSame('aprovado', $maria->fresh()->approval_status);
    }

    public function test_so_pede_os_perfis_do_proprio_setor(): void
    {
        $this->post('/register', $this->dados(['perfis' => ['administrador_setorial']]))->assertSessionHasErrors('perfis.0');
        $this->post('/register', $this->dados(['perfis' => ['analista_tecnico_scp']]))->assertSessionHasErrors('perfis.0');
        $this->post('/register', $this->dados(['perfis' => []]))->assertSessionHasErrors('perfis');
        $this->post('/register', $this->dados(['perfis' => ['gestor_parceria', 'comissao_selecao']]))->assertSessionHasErrors('perfis');
        $this->post('/register', $this->dados(['orgao_id' => null]))->assertSessionHasErrors('orgao_id');
        $this->assertSame(0, User::where('email', 'maria.educacao@saogoncalo.mg.gov.br')->count());
    }

    public function test_a_scp_e_avisada_quando_a_secretaria_ja_tem_responsavel(): void
    {
        $atual = User::factory()->create(['setor' => 'ug', 'orgao_id' => $this->educacao->id, 'status' => true, 'approval_status' => 'aprovado',
            'name' => 'João Atual']);
        $atual->assignRole('responsavel_unidade_gestora');
        $this->post('/register', $this->dados())->assertSessionHasNoErrors();

        $this->actingAs($this->scp)->get(route('usuarios.pendentes'))
            ->assertSee('já tem responsável ativo')->assertSee('João Atual');
    }

    public function test_as_contas_pre_cadastradas_sao_apagadas_mesmo_com_registro(): void
    {
        $semRegistro = User::factory()->create(['email' => 'obras@saogoncalo.mg.gov.br', 'setor' => 'ug', 'status' => true]);
        $semRegistro->assignRole('responsavel_unidade_gestora');
        $comRegistro = User::factory()->create(['email' => 'educacao@saogoncalo.mg.gov.br', 'login' => 'educacao', 'setor' => 'ug', 'status' => true]);
        $comRegistro->assignRole('responsavel_unidade_gestora');
        $chamado = Chamado::forceCreate(['numero' => 1, 'user_id' => $comRegistro->id, 'autor_nome' => 'Gestor', 'categoria' => 'duvida', 'assunto' => 'x', 'status' => 'aberto']);
        $processo = \App\Models\Processo::forceCreate(['numero' => 'P-1', 'sequencial' => 1, 'esfera' => 'municipal', 'modalidade' => 'fomento',
            'orgao_id' => $this->educacao->id, 'created_by' => $comRegistro->id, 'status' => 'rascunho', 'setor_atual' => 'ug', 'etapa' => 0]);

        $apagadas = ContasPreCadastradas::remover();

        $this->assertEqualsCanonicalizing(['obras@saogoncalo.mg.gov.br', 'educacao@saogoncalo.mg.gov.br'], $apagadas);
        $this->assertNull(User::find($semRegistro->id));
        $this->assertNull(User::find($comRegistro->id));
        // O registro fica, sem autor.
        $this->assertNull($chamado->fresh()->user_id);
        $this->assertNull($processo->fresh()->created_by);

        // O endereço institucional fica livre para o secretário se cadastrar.
        $this->post('/register', $this->dados(['email' => 'educacao@saogoncalo.mg.gov.br']))->assertSessionHasNoErrors();
    }
}
