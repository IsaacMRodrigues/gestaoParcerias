<?php

namespace Tests\Feature;

use App\Mail\Aviso;
use App\Models\Chamamento;
use App\Models\Orgao;
use App\Models\Osc;
use App\Models\Programa;
use App\Models\Proposta;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Proposta de dispensa ou inexigibilidade (a manifestação deferida) não passa pela Seleção:
 * o Responsável da UG da Secretaria a aprova, e a Celebração começa, ou a reprova com motivo.
 */
class DecisaoDaPropostaSemSelecaoTest extends TestCase
{
    use RefreshDatabase;

    private Proposta $proposta;
    private User $responsavelUg;
    private User $outroDaUg;
    private User $ugDeOutraSecretaria;
    private User $rl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Mail::fake();

        $obras = Orgao::forceCreate(['name' => 'Obras']);
        $saude = Orgao::forceCreate(['name' => 'Saúde']);
        $chamamento = Chamamento::forceCreate(['programa_id' => Programa::doOrgao($obras)->id, 'numero' => '0001/2026',
            'titulo' => 'Estrada', 'objeto' => 'x', 'tipo' => 'dispensa', 'status' => 'publicado']);
        $osc = Osc::forceCreate(['name' => 'TESTE OSC', 'cnpj' => '11.111.111/0001-11', 'email' => 'osc@example.com']);
        $this->rl = User::factory()->create(['osc_id' => $osc->id, 'setor' => 'osc', 'status' => true, 'approval_status' => 'aprovado']);
        $this->rl->assignRole('responsavel_legal');
        $osc->forceFill(['user_id' => $this->rl->id])->save();
        $this->proposta = Proposta::forceCreate(['chamamento_id' => $chamamento->id, 'osc_id' => $osc->id,
            'titulo' => 'Nova construção de estrada', 'objeto' => 'x', 'status' => 'submetida', 'valor_solicitado' => 1]);

        $servidor = function (Orgao $o, string $papel) {
            $u = User::factory()->create(['setor' => 'ug', 'orgao_id' => $o->id, 'status' => true, 'approval_status' => 'aprovado']);
            $u->assignRole($papel);

            return $u;
        };
        $this->responsavelUg = $servidor($obras, 'responsavel_unidade_gestora');
        $this->outroDaUg = $servidor($obras, 'cadastrador');
        $this->ugDeOutraSecretaria = $servidor($saude, 'responsavel_unidade_gestora');
    }

    private function decidir(User $quem, array $dados)
    {
        return $this->actingAs($quem)->post("/propostas/{$this->proposta->id}/decidir", $dados);
    }

    public function test_a_tela_mostra_a_decisao_a_quem_decide_e_a_secretaria_no_lugar_do_programa(): void
    {
        $this->actingAs($this->responsavelUg)->get("/propostas/{$this->proposta->id}")->assertOk()
            ->assertSee('Decisão da proposta')->assertSee('Aprovar e iniciar a Celebração')
            ->assertSee('Secretaria')->assertDontSee('>Programa<', false);
    }

    public function test_so_o_responsavel_da_ug_da_secretaria_decide(): void
    {
        $this->decidir($this->outroDaUg, ['decisao' => 'aprovar'])->assertForbidden();
        $this->decidir($this->ugDeOutraSecretaria, ['decisao' => 'aprovar'])->assertForbidden();
        $this->assertSame('submetida', $this->proposta->fresh()->status);
    }

    public function test_aprovar_abre_a_celebracao(): void
    {
        $this->decidir($this->responsavelUg, ['decisao' => 'aprovar'])
            ->assertRedirect(route('celebracao.show', $this->proposta));

        $p = $this->proposta->fresh();
        $this->assertSame(['aprovada', 0, 'ug', $this->responsavelUg->id], [$p->status, (int) $p->celebracao_etapa, $p->celebracao_setor, (int) $p->decidida_por]);
        $this->assertNotNull($p->celebracao_iniciada_em);
        Mail::assertQueued(Aviso::class, fn ($m) => $m->hasTo($this->rl->email));

        $this->decidir($this->responsavelUg, ['decisao' => 'reprovar', 'motivo' => 'x'])->assertStatus(422);
    }

    public function test_reprovar_exige_motivo_e_a_osc_o_ve(): void
    {
        $this->decidir($this->responsavelUg, ['decisao' => 'reprovar'])->assertSessionHasErrors('motivo');
        $this->decidir($this->responsavelUg, ['decisao' => 'reprovar', 'motivo' => 'Sem orçamento neste exercício.'])
            ->assertSessionHasNoErrors();

        $p = $this->proposta->fresh();
        $this->assertSame(['reprovada', null], [$p->status, $p->celebracao_iniciada_em]);
        $this->actingAs($this->rl)->get("/portal/propostas/{$p->id}")->assertOk()
            ->assertSee('Motivo da reprovação')->assertSee('Sem orçamento neste exercício.');
    }

    public function test_chamamento_publico_segue_pela_selecao(): void
    {
        $this->proposta->chamamento->forceFill(['tipo' => 'chamamento_publico'])->save();

        $this->actingAs($this->responsavelUg)->get("/propostas/{$this->proposta->id}")->assertDontSee('Decisão da proposta');
        $this->decidir($this->responsavelUg, ['decisao' => 'aprovar'])->assertStatus(422);
    }
}
