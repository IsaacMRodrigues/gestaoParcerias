<?php

namespace Tests\Feature;

use App\Mail\Aviso;
use App\Models\Chamamento;
use App\Models\Orgao;
use App\Models\Osc;
use App\Models\Programa;
use App\Models\Proposta;
use App\Models\Recurso;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * O recurso contra o resultado provisório é julgado pela Comissão de Seleção,
 * na tela da proposta da OSC que o protocolou (decisão da gestão, 29/09/2026).
 */
class RecursoPelaComissaoTest extends TestCase
{
    use RefreshDatabase;

    private Chamamento $chamamento;
    private Proposta $proposta;
    private Recurso $recurso;
    private User $ug;
    private User $comissao;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Mail::fake();

        $orgao = Orgao::forceCreate(['name' => 'Educação']);
        $programa = Programa::forceCreate(['orgao_id' => $orgao->id, 'name' => 'P', 'tipo' => 'termo_fomento']);
        // Prazo de recurso já encerrado, a Seleção ainda na etapa do prazo.
        $this->chamamento = Chamamento::forceCreate(['programa_id' => $programa->id, 'numero' => '001/2026', 'titulo' => 'Oficinas',
            'objeto' => 'x', 'tipo' => 'chamamento_publico', 'status' => 'em_analise',
            'selecao_etapa' => Chamamento::ETAPA_PRAZO_RECURSO, 'selecao_setor' => 'ug', 'prazo_recurso_ate' => now()->subDay()]);

        $servidor = function (string $papel, ?Orgao $o) {
            $u = User::factory()->create(['setor' => 'ug', 'orgao_id' => $o?->id, 'status' => true, 'approval_status' => 'aprovado']);
            $u->assignRole($papel);

            return $u;
        };
        $this->ug = $servidor('responsavel_unidade_gestora', $orgao);
        $this->comissao = $servidor('comissao_selecao', $orgao);

        $osc = Osc::forceCreate(['name' => 'Associação Viver', 'cnpj' => '11.111.111/0001-11', 'email' => 'osc@example.com']);
        $this->proposta = Proposta::forceCreate(['chamamento_id' => $this->chamamento->id, 'osc_id' => $osc->id, 'titulo' => 'Oficinas de música',
            'objeto' => 'x', 'status' => 'em_analise', 'valor_solicitado' => 1]);
        $this->recurso = Recurso::forceCreate(['chamamento_id' => $this->chamamento->id, 'osc_id' => $osc->id,
            'proposta_id' => $this->proposta->id, 'fundamentacao' => 'A nota do critério 2 ignorou a experiência.',
            'protocolado_em' => now()->subDays(3)]);
    }

    private function julgar(User $quem)
    {
        return $this->actingAs($quem)->post("/recursos/{$this->recurso->id}/responder", [
            'resultado' => 'improvido', 'resposta' => 'A experiência foi considerada no critério 3, não no 2.',
        ]);
    }

    private function irParaOJulgamento(): void
    {
        $this->actingAs($this->ug)->post("/chamamentos/{$this->chamamento->id}/selecao/avancar")->assertSessionHasNoErrors();
    }

    public function test_ao_abrir_o_julgamento_a_comissao_e_avisada_e_ve_o_recurso_na_proposta(): void
    {
        $this->irParaOJulgamento();

        Mail::assertQueued(Aviso::class, fn ($m) => $m->hasTo($this->comissao->email) && str_contains($m->assunto, 'Recursos a julgar'));
        $this->actingAs($this->comissao)->get("/propostas/{$this->proposta->id}")->assertOk()
            ->assertSee('Recurso contra o resultado provisório')
            ->assertSee('A nota do critério 2 ignorou a experiência.')
            ->assertSee('Julgar recurso');
    }

    public function test_a_comissao_julga_e_a_ug_nao(): void
    {
        $this->irParaOJulgamento();

        $this->julgar($this->ug)->assertForbidden();
        $this->actingAs($this->ug)->get("/propostas/{$this->proposta->id}")->assertOk()->assertDontSee('Julgar recurso');

        $this->julgar($this->comissao)->assertSessionHas('success');
        $r = $this->recurso->fresh();
        $this->assertSame(['improvido', $this->comissao->id], [$r->resultado, $r->respondido_por]);
        $this->julgar($this->comissao)->assertStatus(422);

        $this->actingAs($this->ug)->get("/propostas/{$this->proposta->id}")->assertSee('Julgamento da Comissão de Seleção');
    }

    public function test_comissao_de_outra_secretaria_nao_julga(): void
    {
        $this->irParaOJulgamento();
        $outra = User::factory()->create(['setor' => 'ug', 'orgao_id' => Orgao::forceCreate(['name' => 'Saúde'])->id,
            'status' => true, 'approval_status' => 'aprovado']);
        $outra->assignRole('comissao_selecao');

        $this->julgar($outra)->assertForbidden();
    }

    public function test_sem_julgamento_a_ug_nao_emite_o_resultado_definitivo(): void
    {
        $this->irParaOJulgamento();

        $this->assertContains('1 recurso sem julgamento da Comissão de Seleção', $this->chamamento->fresh()->pendenciasSelecao());
        $this->julgar($this->comissao);
        $this->assertNotContains('1 recurso sem julgamento da Comissão de Seleção', $this->chamamento->fresh()->pendenciasSelecao());
    }
}
