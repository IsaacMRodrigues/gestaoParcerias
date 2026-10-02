<?php

namespace Tests\Feature;

use App\Models\Chamamento;
use App\Models\Orgao;
use App\Models\Osc;
use App\Models\Programa;
use App\Models\Proposta;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * O parecer avulso da proposta (técnico, jurídico, decisão final) e a diligência que ele abria saíram:
 * os pareceres são peças dos trâmites, e quem aprova ou reprova a proposta é o julgamento da Seleção.
 */
class ParecerAvulsoForaTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_proposta_nao_tem_mais_o_parecer_avulso(): void
    {
        $this->seed(RolesSeeder::class);
        $orgao = Orgao::forceCreate(['name' => 'Educação']);
        $chamamento = Chamamento::forceCreate(['programa_id' => Programa::doOrgao($orgao)->id, 'titulo' => 'C',
            'objeto' => 'x', 'tipo' => 'chamamento_publico', 'status' => 'publicado']);
        $osc = Osc::forceCreate(['name' => 'OSC', 'cnpj' => '11.111.111/0001-11', 'email' => 'osc@example.com']);
        $proposta = Proposta::forceCreate(['chamamento_id' => $chamamento->id, 'osc_id' => $osc->id, 'titulo' => 'Estrada',
            'objeto' => 'x', 'status' => 'submetida', 'valor_solicitado' => 1]);
        $ug = User::factory()->create(['setor' => 'ug', 'orgao_id' => $orgao->id, 'status' => true, 'approval_status' => 'aprovado']);
        $ug->assignRole('responsavel_unidade_gestora');

        $this->actingAs($ug)->get("/propostas/{$proposta->id}")->assertOk()
            ->assertDontSee('Análise da Proposta')->assertDontSee('+ Parecer Técnico')->assertDontSee('+ Decisão Final');
        $this->actingAs($ug)->get("/propostas/{$proposta->id}/pareceres/create/tecnico")->assertNotFound();
        $this->actingAs($ug)->post("/propostas/{$proposta->id}/pareceres", ['tipo' => 'decisao', 'resultado' => 'aprovado'])
            ->assertNotFound();
        $this->assertSame('submetida', $proposta->fresh()->status, 'só a Seleção aprova ou reprova');
    }
}
