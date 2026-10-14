<?php

namespace Tests\Feature;

use App\Models\Chamamento;
use App\Models\ManifestacaoInteresse;
use App\Models\Orgao;
use App\Models\Osc;
use App\Models\Programa;
use App\Models\Proposta;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Campo de pesquisa nos Chamamentos abertos do portal e em Minhas inscrições da OSC. */
class PesquisaNasListasDoPortalTest extends TestCase
{
    use RefreshDatabase;

    private Orgao $educacao;
    private Orgao $saude;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $this->educacao = Orgao::forceCreate(['name' => 'Educação']);
        $this->saude = Orgao::forceCreate(['name' => 'Saúde']);
    }

    private function chamamento(Orgao $orgao, string $numero, string $titulo, string $status = 'em_inscricao'): Chamamento
    {
        return Chamamento::forceCreate(['programa_id' => Programa::doOrgao($orgao)->id, 'numero' => $numero, 'titulo' => $titulo,
            'objeto' => 'Objeto de ' . $titulo, 'tipo' => 'chamamento_publico', 'status' => $status,
            'data_inicio_inscricao' => now()->subDay(), 'data_fim_inscricao' => now()->addMonth()]);
    }

    public function test_os_chamamentos_abertos_se_pesquisam_por_titulo_numero_e_secretaria(): void
    {
        $this->chamamento($this->educacao, '001/2026', 'Oficinas de música');
        $this->chamamento($this->saude, '002/2026', 'Agentes comunitários');

        $this->get('/portal')->assertOk()->assertSee('Oficinas de música')->assertSee('Agentes comunitários');
        $this->get('/portal?busca=música')->assertSee('Oficinas de música')->assertDontSee('Agentes comunitários')->assertSee('1 encontrado(s)');
        $this->get('/portal?busca=002/2026')->assertSee('Agentes comunitários')->assertDontSee('Oficinas de música');
        $this->get('/portal?busca=Saúde')->assertSee('Agentes comunitários')->assertDontSee('Oficinas de música');
        $this->get('/portal?busca=xyz')->assertSee('Nenhum chamamento aberto encontrado');
    }

    public function test_minhas_inscricoes_se_pesquisam(): void
    {
        $osc = Osc::forceCreate(['name' => 'Associação Viver', 'cnpj' => '11.111.111/0001-11', 'email' => 'osc@example.com']);
        $rl = User::factory()->create(['osc_id' => $osc->id, 'setor' => 'osc', 'status' => true, 'approval_status' => 'aprovado']);
        $rl->assignRole('responsavel_legal');
        $osc->forceFill(['user_id' => $rl->id])->save();

        foreach ([[$this->educacao, '001/2026', 'Oficinas de música'], [$this->saude, '002/2026', 'Agentes comunitários']] as [$orgao, $numero, $titulo]) {
            Proposta::forceCreate(['chamamento_id' => $this->chamamento($orgao, $numero, $titulo)->id, 'osc_id' => $osc->id,
                'titulo' => 'Proposta ' . $titulo, 'objeto' => 'x', 'status' => 'submetida', 'valor_solicitado' => 1]);
        }
        ManifestacaoInteresse::forceCreate(['osc_id' => $osc->id, 'orgao_id' => $this->educacao->id, 'titulo' => 'Horta comunitária',
            'objeto' => 'x', 'justificativa' => 'x', 'valor_solicitado' => 1, 'status' => 'em_analise', 'protocolo' => 'MI-2026-0007']);

        $this->actingAs($rl)->get('/portal/minhas-propostas')->assertOk()
            ->assertSee('Proposta Oficinas de música')->assertSee('Proposta Agentes comunitários')->assertSee('Horta comunitária');
        $this->actingAs($rl)->get('/portal/minhas-propostas?busca=001/2026')
            ->assertSee('Proposta Oficinas de música')->assertDontSee('Proposta Agentes comunitários')->assertDontSee('Horta comunitária')
            ->assertSee('1 resultado(s)');
        $this->actingAs($rl)->get('/portal/minhas-propostas?busca=MI-2026-0007')
            ->assertSee('Horta comunitária')->assertDontSee('Proposta Oficinas de música');
    }
}
