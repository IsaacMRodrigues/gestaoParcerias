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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Etapa própria do prazo de recurso na Seleção (decisão da gestão,
 * 29/09/2026): depois da publicação do Resultado Provisório, as OSCs podem
 * recorrer até a data do edital, que a SCP informa; recorrer é opcional, e a
 * UG só encerra a etapa depois do prazo — e só então responde os recursos.
 */
class PrazoDeRecursoTest extends TestCase
{
    use RefreshDatabase;

    private Chamamento $chamamento;
    private User $scp;
    private User $ug;
    private User $rl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Mail::fake();
        Storage::fake('local');

        $orgao = Orgao::forceCreate(['name' => 'Educação']);
        $programa = Programa::forceCreate(['orgao_id' => $orgao->id, 'name' => 'P', 'tipo' => 'termo_fomento']);
        // Resultado Provisório já emitido: a Seleção está com a SCP para publicar.
        $this->chamamento = Chamamento::forceCreate(['programa_id' => $programa->id, 'numero' => '001/2026', 'titulo' => 'Oficinas',
            'objeto' => 'x', 'tipo' => 'chamamento_publico', 'status' => 'em_analise', 'selecao_etapa' => 1, 'selecao_setor' => 'scp']);

        $this->scp = User::factory()->create(['setor' => 'scp', 'status' => true, 'approval_status' => 'aprovado']);
        $this->scp->assignRole('analista_tecnico_scp');
        $this->ug = User::factory()->create(['setor' => 'ug', 'orgao_id' => $orgao->id, 'status' => true, 'approval_status' => 'aprovado']);
        $this->ug->assignRole('responsavel_unidade_gestora');

        $osc = Osc::forceCreate(['name' => 'OSC', 'cnpj' => '11.111.111/0001-11', 'email' => 'osc@example.com']);
        $this->rl = User::factory()->create(['osc_id' => $osc->id, 'setor' => 'osc', 'status' => true, 'approval_status' => 'aprovado']);
        $this->rl->assignRole('responsavel_legal');
        $osc->forceFill(['user_id' => $this->rl->id])->save();
        Proposta::forceCreate(['chamamento_id' => $this->chamamento->id, 'osc_id' => $osc->id, 'titulo' => 'P', 'objeto' => 'x',
            'status' => 'submetida', 'valor_solicitado' => 1]);
    }

    private function abrirPrazo(int $dias = 5)
    {
        return $this->actingAs($this->scp)->post("/chamamentos/{$this->chamamento->id}/selecao/avancar", [
            'prazo_recurso_ate' => now()->addDays($dias)->format('Y-m-d'),
        ]);
    }

    private function recorrer()
    {
        return $this->actingAs($this->rl)->post("/portal/chamamentos/{$this->chamamento->id}/recurso", [
            'fundamentacao' => 'A nota do critério 2 desconsiderou a experiência comprovada.',
            'arquivo'       => UploadedFile::fake()->create('recurso.pdf', 50, 'application/pdf'),
        ]);
    }

    public function test_a_scp_informa_o_prazo_do_edital_ao_publicar_e_as_oscs_sao_avisadas(): void
    {
        $this->actingAs($this->scp)->post("/chamamentos/{$this->chamamento->id}/selecao/avancar", [])
            ->assertSessionHasErrors('prazo_recurso_ate');
        $this->assertSame(1, (int) $this->chamamento->fresh()->selecao_etapa);

        $this->abrirPrazo()->assertSessionHasNoErrors();

        $c = $this->chamamento->fresh();
        $this->assertSame([Chamamento::ETAPA_PRAZO_RECURSO, 'ug'], [(int) $c->selecao_etapa, $c->selecao_setor]);
        $this->assertSame(now()->addDays(5)->format('Y-m-d'), $c->prazo_recurso_ate->format('Y-m-d'));
        $this->assertTrue($c->faseRecursalAberta());
        Mail::assertQueued(Aviso::class, fn ($m) => $m->hasTo($this->rl->email) && str_contains($m->assunto, 'Prazo de recurso'));
    }

    public function test_no_prazo_a_osc_recorre_mas_a_ug_nao_responde_nem_encerra(): void
    {
        $this->abrirPrazo();
        $this->recorrer()->assertSessionHas('success');
        $recurso = Recurso::sole();

        $this->actingAs($this->ug)->post("/recursos/{$recurso->id}/responder", ['resultado' => 'improvido', 'resposta' => str_repeat('x', 30)])
            ->assertStatus(422);
        $this->actingAs($this->ug)->post("/chamamentos/{$this->chamamento->id}/selecao/avancar")->assertStatus(422);

        $this->assertSame(Chamamento::ETAPA_PRAZO_RECURSO, (int) $this->chamamento->fresh()->selecao_etapa);
    }

    public function test_findo_o_prazo_a_osc_nao_recorre_e_a_ug_encerra_e_responde(): void
    {
        $this->abrirPrazo(2);
        $this->recorrer();
        $recurso = Recurso::sole();

        $this->travel(3)->days();

        $this->assertFalse($this->chamamento->fresh()->faseRecursalAberta());
        Recurso::query()->delete();
        $this->recorrer()->assertStatus(422);
        $recurso = Recurso::forceCreate($recurso->only(['chamamento_id', 'osc_id', 'proposta_id', 'fundamentacao', 'protocolado_por', 'protocolado_em']));

        $this->actingAs($this->ug)->post("/chamamentos/{$this->chamamento->id}/selecao/avancar")->assertSessionHasNoErrors();
        $this->assertSame(Chamamento::ETAPA_RESPOSTA_RECURSOS, (int) $this->chamamento->fresh()->selecao_etapa);

        $this->actingAs($this->ug)->post("/recursos/{$recurso->id}/responder", ['resultado' => array_key_first(Recurso::RESULTADOS), 'resposta' => str_repeat('x', 30)])
            ->assertSessionHas('success');
        $this->assertNotNull($recurso->fresh()->respondido_em);
    }

    public function test_sem_recurso_a_etapa_so_passa(): void
    {
        $this->abrirPrazo(1);
        $this->travel(2)->days();

        $this->actingAs($this->ug)->post("/chamamentos/{$this->chamamento->id}/selecao/avancar")->assertSessionHasNoErrors();
        $this->assertSame(Chamamento::ETAPA_RESPOSTA_RECURSOS, (int) $this->chamamento->fresh()->selecao_etapa);
        $this->assertSame([], $this->chamamento->fresh()->pendenciasSelecao(), 'sem recurso, nada a responder');
    }

    public function test_a_tela_da_selecao_mostra_a_etapa_e_o_prazo(): void
    {
        $this->abrirPrazo();

        $this->actingAs($this->ug)->get("/chamamentos/{$this->chamamento->id}/selecao")->assertOk()
            ->assertSee('Prazo de recurso (opcional para a OSC)')
            ->assertSee('Prazo do edital: até ' . now()->addDays(5)->format('d/m/Y'))
            ->assertSee('Encerrar o prazo de recurso');
        $this->actingAs($this->rl)->get("/portal/chamamentos/{$this->chamamento->id}")->assertOk()
            ->assertSee('Protocolar recurso')->assertSee('Recorrer é opcional.');
    }
}
