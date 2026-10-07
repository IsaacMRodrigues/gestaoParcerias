<?php

namespace Tests\Feature;

use App\Models\Chamamento;
use App\Models\Instrumento;
use App\Models\Orgao;
use App\Models\Osc;
use App\Models\PrestacaoContas;
use App\Models\Programa;
use App\Models\Proposta;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * As listas mostram ao servidor só o que é da sua Secretaria, como as telas de cada item já faziam:
 * Instrumentos, Execução, Prestação de Contas, Chamamentos, a busca e os números do painel. A SCP vê tudo.
 */
class ListasPorSecretariaTest extends TestCase
{
    use RefreshDatabase;

    private User $ugEducacao;
    private User $ugObras;
    private User $scp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);

        $educacao = Orgao::forceCreate(['name' => 'Educação']);
        $obras = Orgao::forceCreate(['name' => 'Obras']);
        $chamamento = Chamamento::forceCreate(['programa_id' => Programa::doOrgao($educacao)->id, 'numero' => '001/2026',
            'titulo' => 'Oficinas de Educação', 'objeto' => 'x', 'tipo' => 'chamamento_publico', 'status' => 'publicado']);
        $osc = Osc::forceCreate(['name' => 'TESTE OSC', 'cnpj' => '11.111.111/0001-11', 'email' => 'osc@example.com']);
        $proposta = Proposta::forceCreate(['chamamento_id' => $chamamento->id, 'osc_id' => $osc->id, 'titulo' => 'Oficinas',
            'objeto' => 'x', 'status' => 'aprovada', 'valor_solicitado' => 120000]);
        $instrumento = Instrumento::forceCreate(['proposta_id' => $proposta->id, 'numero' => 'TC 001/2026', 'tipo' => 'termo_colaboracao',
            'objeto' => 'x', 'valor_repasse' => 120000, 'data_inicio' => now(), 'data_fim' => now()->addYear(), 'status' => 'vigente']);
        PrestacaoContas::forceCreate(['instrumento_id' => $instrumento->id, 'tipo' => 'parcial', 'numero' => 1,
            'periodo_inicio' => now()->subMonth(), 'periodo_fim' => now(), 'etapa' => 0, 'setor' => 'osc']);

        $servidor = function (string $setor, ?Orgao $o, string $papel) {
            $u = User::factory()->create(['setor' => $setor, 'orgao_id' => $o?->id, 'status' => true, 'approval_status' => 'aprovado']);
            $u->assignRole($papel);

            return $u;
        };
        $this->ugEducacao = $servidor('ug', $educacao, 'responsavel_unidade_gestora');
        $this->ugObras = $servidor('ug', $obras, 'responsavel_unidade_gestora');
        $this->scp = $servidor('scp', null, 'analista_tecnico_scp');
    }

    public function test_a_ug_de_outra_secretaria_nao_ve_nas_listas(): void
    {
        foreach (['/instrumentos', '/execucao', '/prestacao-contas'] as $url) {
            $this->actingAs($this->ugObras)->get($url)->assertOk()->assertDontSee('TC 001/2026');
            $this->actingAs($this->ugEducacao)->get($url)->assertOk()->assertSee('TC 001/2026');
        }
        $this->actingAs($this->ugObras)->get('/chamamentos?situacao=todos')->assertOk()->assertDontSee('Oficinas de Educação');
        $this->actingAs($this->ugEducacao)->get('/chamamentos?situacao=todos')->assertOk()->assertSee('Oficinas de Educação');

        $busca = fn (User $u) => collect($this->actingAs($u)->getJson('/busca?q=001')->json('grupos'))->pluck('itens')->flatten(1)->pluck('titulo')->all();
        $this->assertNotContains('TC 001/2026', $busca($this->ugObras));
        $this->assertContains('TC 001/2026', $busca($this->ugEducacao));
    }

    public function test_a_scp_ve_todas(): void
    {
        foreach (['/execucao', '/prestacao-contas'] as $url) {
            $this->actingAs($this->scp)->get($url)->assertOk()->assertSee('TC 001/2026');
        }
        $this->actingAs($this->scp)->get('/chamamentos?situacao=todos')->assertOk()->assertSee('Oficinas de Educação');
    }

    public function test_a_transparencia_e_publica_e_lista_os_anos(): void
    {
        $this->get('/transparencia')->assertOk()->assertSee('TC 001/2026');
    }

    public function test_o_painel_conta_so_o_que_e_da_secretaria(): void
    {
        $this->actingAs($this->ugObras)->get('/dashboard')->assertOk()->assertViewHas('instrumentosVigentes', 0);
    }
}
