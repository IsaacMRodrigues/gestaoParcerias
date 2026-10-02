<?php

namespace Tests\Feature;

use App\Models\Chamamento;
use App\Models\Orgao;
use App\Models\Programa;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Chamamentos" no menu abre direto a lista de todos os chamamentos, sem passar por
 * Programas, mostrando os abertos; o programa é só o agrupamento por Secretaria.
 */
class ListaDeChamamentosTest extends TestCase
{
    use RefreshDatabase;

    private User $scp;
    private Orgao $educacao;
    private Orgao $saude;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);

        $this->educacao = Orgao::forceCreate(['name' => 'Educação', 'sigla' => 'SEMED']);
        $this->saude = Orgao::forceCreate(['name' => 'Saúde', 'sigla' => 'SEMUS']);
        $novo = fn (Orgao $o, string $titulo, string $status) => Chamamento::forceCreate([
            'programa_id' => Programa::doOrgao($o)->id, 'titulo' => $titulo, 'objeto' => 'x',
            'tipo' => 'chamamento_publico', 'status' => $status,
        ]);
        $novo($this->educacao, 'Oficinas de música', 'em_inscricao');
        $novo($this->saude, 'Agentes de saúde', 'publicado');
        $novo($this->educacao, 'Reforço escolar', 'encerrado');

        $this->scp = User::factory()->create(['setor' => 'scp', 'status' => true, 'approval_status' => 'aprovado']);
        $this->scp->assignRole('analista_tecnico_scp');
    }

    public function test_abre_com_os_abertos_de_todas_as_secretarias(): void
    {
        $this->actingAs($this->scp)->get('/chamamentos')->assertOk()
            ->assertSee('Chamamentos Públicos')
            ->assertSee('Oficinas de música')->assertSee('Agentes de saúde')->assertSee('SEMUS')
            ->assertDontSee('Reforço escolar');
    }

    public function test_filtra_por_situacao_secretaria_e_busca(): void
    {
        $this->actingAs($this->scp)->get('/chamamentos?situacao=todos')
            ->assertSee('Oficinas de música')->assertSee('Reforço escolar');
        $this->actingAs($this->scp)->get('/chamamentos?situacao=encerrado')
            ->assertSee('Reforço escolar')->assertDontSee('Oficinas de música');
        $this->actingAs($this->scp)->get("/chamamentos?situacao=todos&orgao_id={$this->saude->id}")
            ->assertSee('Agentes de saúde')->assertDontSee('Oficinas de música');
        $this->actingAs($this->scp)->get('/chamamentos?busca=música')
            ->assertSee('Oficinas de música')->assertDontSee('Agentes de saúde');
    }

    public function test_o_menu_e_os_enderecos_antigos_levam_a_lista(): void
    {
        $this->actingAs($this->scp)->get('/dashboard')->assertSee(route('chamamentos.index'), false);
        $this->actingAs($this->scp)->get('/programas')->assertRedirect(route('chamamentos.index'));
        $programa = Programa::first();
        $this->actingAs($this->scp)->get("/programas/{$programa->id}/chamamentos")->assertRedirect(route('chamamentos.index'));
    }

    public function test_a_scp_cria_escolhendo_a_secretaria(): void
    {
        $dados = ['titulo' => 'Esporte na escola', 'objeto' => 'x', 'tipo' => 'chamamento_publico', 'status' => 'rascunho'];

        $this->actingAs($this->scp)->post('/chamamentos', $dados)->assertSessionHasErrors('orgao_id');
        $this->actingAs($this->scp)->post('/chamamentos', $dados + ['orgao_id' => $this->educacao->id])
            ->assertSessionHasNoErrors()->assertRedirect(route('chamamentos.index'));

        $novo = Chamamento::where('titulo', 'Esporte na escola')->sole();
        $this->assertSame($this->educacao->id, $novo->programa->orgao_id);
        $this->assertSame(Programa::doOrgao($this->educacao)->id, $novo->programa_id, 'entra no programa da Secretaria');
    }
}
