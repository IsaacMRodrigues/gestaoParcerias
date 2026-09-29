<?php

namespace Tests\Feature;

use App\Models\Chamamento;
use App\Models\Orgao;
use App\Models\Peca;
use App\Models\Programa;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * O Termo de Adjudicação e Homologação, documento final da Seleção: a SCP o
 * preenche antes de enviá-lo ao Gabinete, e o Prefeito só assina — não edita
 * (decisão da gestão, 29/09/2026).
 */
class TermoDeHomologacaoTest extends TestCase
{
    use RefreshDatabase;

    private Chamamento $chamamento;
    private Peca $termo;
    private User $scp;
    private User $prefeito;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Mail::fake();

        $orgao = Orgao::forceCreate(['name' => 'Educação']);
        $programa = Programa::forceCreate(['orgao_id' => $orgao->id, 'name' => 'P', 'tipo' => 'termo_fomento']);
        // Etapa 5 (índice 4): a SCP publica o resultado definitivo e emite o Termo.
        $this->chamamento = Chamamento::forceCreate(['programa_id' => $programa->id, 'numero' => '001/2026', 'titulo' => 'Oficinas',
            'objeto' => 'x', 'tipo' => 'chamamento_publico', 'status' => 'em_analise', 'selecao_etapa' => 4, 'selecao_setor' => 'scp']);
        Peca::sincronizar($this->chamamento, 'chamamento_publico');
        $this->chamamento->pecas()->where('chave', 'pub_resultado_definitivo')->update(['arquivo_path' => 'x.pdf', 'arquivo_nome' => 'x.pdf']);
        $this->termo = $this->chamamento->pecas()->where('chave', 'termo_homologacao')->sole();

        $this->scp = User::factory()->create(['setor' => 'scp', 'status' => true, 'approval_status' => 'aprovado']);
        $this->scp->assignRole('analista_tecnico_scp');
        $this->prefeito = User::factory()->create(['setor' => 'pm', 'status' => true, 'approval_status' => 'aprovado']);
        $this->prefeito->assignRole('prefeito_municipal');
    }

    private function enviarAoGabinete()
    {
        return $this->actingAs($this->scp)->post("/chamamentos/{$this->chamamento->id}/selecao/avancar");
    }

    public function test_a_scp_nao_envia_o_termo_com_o_texto_do_modelo(): void
    {
        $this->assertTrue($this->termo->aindaEOModelo());
        $this->assertContains('Termo de Adjudicação e Homologação (modelo padrão) (preencher antes de enviar ao Gabinete)',
            $this->chamamento->fresh()->pendenciasSelecao());

        $this->enviarAoGabinete()->assertStatus(422);
        $this->assertSame(4, (int) $this->chamamento->fresh()->selecao_etapa);
    }

    public function test_preenchido_pela_scp_vai_ao_gabinete_e_o_prefeito_so_assina(): void
    {
        $this->actingAs($this->scp)->put("/pecas/{$this->termo->id}", ['conteudo' => '<p>Fica homologada a celebração com a Associação Viver.</p>'])
            ->assertSessionHasNoErrors();
        $this->enviarAoGabinete()->assertSessionHasNoErrors();
        $this->assertSame([5, 'pm'], [(int) $this->chamamento->fresh()->selecao_etapa, $this->chamamento->fresh()->selecao_setor]);

        $termo = $this->termo->fresh();
        $this->assertFalse($termo->podePreencher($this->prefeito), 'o Prefeito não edita');
        $this->assertFalse($termo->podePreencher($this->scp), 'depois de enviado, nem a SCP');
        $this->actingAs($this->prefeito)->put("/pecas/{$termo->id}", ['conteudo' => '<p>outro texto</p>'])->assertForbidden();

        $this->actingAs($this->prefeito)->get("/chamamentos/{$this->chamamento->id}/selecao")->assertOk()
            ->assertDontSee('name="conteudo"', false);

        $this->actingAs($this->prefeito)->patch("/pecas/{$termo->id}/assinar")->assertSessionHasNoErrors();
        $termo = $termo->fresh();
        $this->assertTrue($termo->assinado());
        $this->assertStringContainsString('Associação Viver', $termo->conteudo);
    }
}
