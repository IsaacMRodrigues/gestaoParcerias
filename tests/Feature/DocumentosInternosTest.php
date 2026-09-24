<?php

namespace Tests\Feature;

use App\Models\Chamamento;
use App\Models\Orgao;
use App\Models\Osc;
use App\Models\Peca;
use App\Models\Processo;
use App\Models\ProcessoPeca;
use App\Models\Programa;
use App\Models\Proposta;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Homologação, item 3: os documentos do Planejamento são internos.
 *
 * A OSC não os vê na aba "Documentos do processo" — nem pela fase do
 * Planejamento, nem pela peça da Seleção que os puxa — e o endereço direto
 * responde 403. Os perfis internos continuam vendo tudo.
 */
class DocumentosInternosTest extends TestCase
{
    use RefreshDatabase;

    private User $dona;
    private User $ugDona;
    private Proposta $proposta;
    private Processo $processo;
    private ProcessoPeca $termoDeReferencia;
    private Peca $editalNaSelecao;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Mail::fake();

        $orgao = Orgao::forceCreate(['name' => 'Educação']);
        $this->ugDona = User::factory()->create(['setor' => 'ug', 'orgao_id' => $orgao->id, 'status' => true, 'approval_status' => 'aprovado']);
        $this->ugDona->assignRole('responsavel_unidade_gestora');

        $this->processo = Processo::forceCreate(['numero' => '0207.0001.2026.01', 'orgao_id' => $orgao->id, 'created_by' => $this->ugDona->id,
            'status' => 'concluido', 'setor_atual' => 'scp', 'etapa' => 9]);

        $assinado = ['assinado_por' => $this->ugDona->id, 'assinado_em' => now(), 'assinante_nome' => 'UG', 'codigo_validacao' => 'AAAA-BBBB-CC'];
        $this->termoDeReferencia = ProcessoPeca::forceCreate(['processo_id' => $this->processo->id, 'tipo' => 'termo_referencia',
            'conteudo' => '<p>Termo de Referência interno</p>', 'visivel_osc' => true] + $assinado);
        $parecer = ProcessoPeca::forceCreate(['processo_id' => $this->processo->id, 'tipo' => 'parecer_juridico',
            'conteudo' => '<p>Parecer</p>', 'visivel_osc' => true, 'codigo_validacao' => 'DDDD-EEEE-FF'] + $assinado);

        $programa = Programa::forceCreate(['orgao_id' => $orgao->id, 'name' => 'Programa', 'tipo' => 'termo_fomento']);
        $chamamento = Chamamento::forceCreate(['programa_id' => $programa->id, 'processo_id' => $this->processo->id, 'numero' => '001/2026',
            'titulo' => 'Chamamento', 'objeto' => 'x', 'tipo' => 'chamamento_publico', 'status' => 'publicado']);

        // A peça da Seleção que puxa o Edital do Planejamento — a mesma peça, por outra porta.
        $this->editalNaSelecao = Peca::forceCreate(['pecaable_type' => Chamamento::class, 'pecaable_id' => $chamamento->id,
            'categoria' => 'chamamento_publico', 'chave' => 'parecer_juridico', 'rotulo' => 'Parecer jurídico', 'tipo' => 'modelo',
            'origem_processo_peca_id' => $parecer->id, 'visivel_osc' => true, 'conteudo' => '<p>Parecer</p>'] + $assinado);

        $osc = Osc::forceCreate(['name' => 'OSC Dona', 'cnpj' => '11.111.111/0001-11', 'email' => 'osc@example.com']);
        $this->dona = User::factory()->create(['osc_id' => $osc->id, 'setor' => 'osc', 'status' => true, 'approval_status' => 'aprovado']);
        $this->dona->assignRole('responsavel_legal');
        $osc->forceFill(['user_id' => $this->dona->id])->save();

        $this->proposta = Proposta::forceCreate(['chamamento_id' => $chamamento->id, 'osc_id' => $osc->id, 'titulo' => 'Parceria',
            'objeto' => 'x', 'status' => 'submetida', 'valor_solicitado' => 1000]);
    }

    public function test_a_osc_nao_ve_a_aba_quando_so_ha_documentos_do_planejamento(): void
    {
        $this->actingAs($this->dona)->get("/portal/propostas/{$this->proposta->id}")
            ->assertOk()
            ->assertDontSee('Documentos do processo')
            ->assertDontSee('Termo de Referência');

        $this->assertSame([], $this->proposta->fresh()->dossieParaOsc());
    }

    public function test_endereco_direto_de_documento_do_planejamento_responde_403(): void
    {
        $base = "/portal/propostas/{$this->proposta->id}/dossie";

        $this->actingAs($this->dona)->get("$base/processo/{$this->termoDeReferencia->id}")->assertForbidden();
        $this->actingAs($this->dona)->get("$base/processo/{$this->termoDeReferencia->id}/arquivo")->assertForbidden();
        $this->actingAs($this->dona)->get("$base/peca/{$this->editalNaSelecao->id}")->assertForbidden();
    }

    public function test_documento_de_outra_fase_continua_aparecendo_para_a_osc(): void
    {
        $termo = Peca::forceCreate(['pecaable_type' => Proposta::class, 'pecaable_id' => $this->proposta->id,
            'categoria' => 'celebracao', 'chave' => 'termo', 'rotulo' => 'Termo de Fomento assinado', 'tipo' => 'modelo',
            'visivel_osc' => true, 'conteudo' => '<p>Termo</p>', 'assinado_em' => now(), 'assinado_por' => $this->ugDona->id]);

        $this->actingAs($this->dona)->get("/portal/propostas/{$this->proposta->id}")
            ->assertSee('Documentos do processo')
            ->assertSee('Termo de Fomento assinado')
            ->assertDontSee('Termo de Referência');

        $this->actingAs($this->dona)->get("/portal/propostas/{$this->proposta->id}/dossie/peca/{$termo->id}")->assertOk();
    }

    public function test_perfis_internos_continuam_vendo_o_planejamento(): void
    {
        $this->actingAs($this->ugDona)->get("/processos/{$this->processo->id}")
            ->assertOk()
            ->assertSee('Termo de Referência');
    }

    public function test_a_curadoria_nao_oferece_abrir_o_planejamento(): void
    {
        $this->actingAs($this->ugDona)->get("/parcerias/{$this->proposta->id}/documentos-osc")
            ->assertOk()
            ->assertSee('são internos da')
            ->assertDontSee('value="processo:' . $this->termoDeReferencia->id . '"', false)
            ->assertDontSee('value="peca:' . $this->editalNaSelecao->id . '"', false);
    }
}
