<?php

namespace Tests\Feature;

use App\Models\Chamamento;
use App\Models\Orgao;
use App\Models\Peca;
use App\Models\Programa;
use App\Models\User;
use App\Support\CaixaDeEntrada;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Como no Planejamento, a Seleção que chega de outro setor espera o registro de recebimento:
 * antes dele, o setor não encaminha, não devolve nem mexe nos documentos da etapa.
 */
class SelecaoRegistraRecebimentoTest extends TestCase
{
    use RefreshDatabase;

    private Chamamento $chamamento;
    private User $scp;
    private User $ug;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Mail::fake();

        $orgao = Orgao::forceCreate(['name' => 'Educação']);
        $programa = Programa::forceCreate(['orgao_id' => $orgao->id, 'name' => 'P', 'tipo' => 'termo_fomento']);
        // Resultado Provisório publicado: a SCP encaminha à UG, que abre a etapa do recurso.
        $this->chamamento = Chamamento::forceCreate(['programa_id' => $programa->id, 'numero' => '001/2026', 'titulo' => 'Oficinas',
            'objeto' => 'x', 'tipo' => 'chamamento_publico', 'status' => 'em_analise', 'selecao_etapa' => 1, 'selecao_setor' => 'scp']);

        $this->scp = User::factory()->create(['setor' => 'scp', 'status' => true, 'approval_status' => 'aprovado']);
        $this->scp->assignRole('analista_tecnico_scp');
        $this->ug = User::factory()->create(['setor' => 'ug', 'orgao_id' => $orgao->id, 'status' => true, 'approval_status' => 'aprovado']);
        $this->ug->assignRole('responsavel_unidade_gestora');
    }

    private function encaminharAUg(): void
    {
        $this->actingAs($this->scp)->post("/chamamentos/{$this->chamamento->id}/selecao/avancar", [
            'prazo_recurso_ate' => now()->addDay()->format('Y-m-d'),
        ])->assertSessionHasNoErrors();
    }

    public function test_a_ug_registra_o_recebimento_antes_de_atuar(): void
    {
        $this->encaminharAUg();
        $this->travel(2)->days();

        $tela = fn () => $this->actingAs($this->ug)->get("/chamamentos/{$this->chamamento->id}/selecao")->assertOk();
        $tela()->assertSee('Registrar Recebimento')->assertDontSee('Encerrar o prazo de recurso');
        $this->assertTrue(CaixaDeEntrada::para($this->ug)->itens->sole()['aguardaRecebimento']);

        $this->actingAs($this->ug)->post("/chamamentos/{$this->chamamento->id}/selecao/avancar")->assertStatus(422);
        $this->actingAs($this->ug)->post("/chamamentos/{$this->chamamento->id}/selecao/devolver", ['parecer' => 'x'])->assertStatus(422);
        // Só quem está com a Seleção registra.
        $this->actingAs($this->scp)->patch("/chamamentos/{$this->chamamento->id}/selecao/receber")->assertForbidden();

        $this->actingAs($this->ug)->patch("/chamamentos/{$this->chamamento->id}/selecao/receber")->assertSessionHas('success');

        $mov = $this->chamamento->selecaoTramitacoes()->first();
        $this->assertSame($this->ug->id, $mov->recebido_por);
        $this->assertNotNull($mov->recebido_em);
        $tela()->assertDontSee('Registrar Recebimento')->assertSee('Encerrar o prazo de recurso')
            ->assertSee('recebido em ' . $mov->recebido_em->format('d/m/Y H:i'));
        $this->assertFalse(CaixaDeEntrada::para($this->ug)->itens->sole()['aguardaRecebimento']);

        // Da etapa do recurso à do Resultado Definitivo a Seleção fica na UG: já chega recebida.
        $this->actingAs($this->ug)->post("/chamamentos/{$this->chamamento->id}/selecao/avancar")->assertSessionHasNoErrors();
        $this->assertFalse($this->chamamento->fresh()->aguardandoRecebimentoSelecao());
    }

    public function test_os_documentos_da_etapa_esperam_o_recebimento(): void
    {
        // Devolvida pela SCP à etapa do Resultado Definitivo, que é da UG.
        $this->chamamento->forceFill(['selecao_etapa' => 4])->save();
        Peca::sincronizar($this->chamamento, 'chamamento_publico');
        $this->actingAs($this->scp)->post("/chamamentos/{$this->chamamento->id}/selecao/devolver", ['parecer' => 'Rever a nota da OSC.'])
            ->assertSessionHasNoErrors();
        $this->assertSame([3, 'ug'], [(int) $this->chamamento->fresh()->selecao_etapa, $this->chamamento->fresh()->selecao_setor]);

        $resultado = fn () => $this->chamamento->fresh()->pecas()->where('chave', 'resultado_definitivo')->sole();
        $this->assertFalse($resultado()->podePreencher($this->ug));
        $this->assertStringContainsString('falta registrar o recebimento', $resultado()->motivoNaoPodePreencher($this->ug));
        $this->actingAs($this->ug)->put("/pecas/{$resultado()->id}", ['conteudo' => '<p>Resultado</p>'])->assertForbidden();

        $this->actingAs($this->ug)->patch("/chamamentos/{$this->chamamento->id}/selecao/receber")->assertSessionHasNoErrors();
        $this->assertTrue($resultado()->podePreencher($this->ug));
    }
}
