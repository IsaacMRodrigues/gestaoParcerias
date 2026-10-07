<?php

namespace Tests\Feature;

use App\Models\Chamamento;
use App\Models\Instrumento;
use App\Models\Orgao;
use App\Models\Osc;
use App\Models\Peca;
use App\Models\Programa;
use App\Models\Proposta;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * O instrumento nasce na conclusão da Celebração, com os dados do Termo e da proposta, e segue para a
 * execução; não há mais formalizar, assinar e publicar à mão. No instrumento, só a OP parcial: a Global
 * e os dados bancários são da Celebração.
 */
class InstrumentoNaConclusaoTest extends TestCase
{
    use RefreshDatabase;

    private Proposta $proposta;
    private User $scp;
    private User $ug;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Mail::fake();

        $orgao = Orgao::forceCreate(['name' => 'Educação']);
        $programa = Programa::forceCreate(['orgao_id' => $orgao->id, 'name' => 'P', 'tipo' => 'termo_fomento']);
        $chamamento = Chamamento::forceCreate(['programa_id' => $programa->id, 'numero' => '001/2026', 'titulo' => 'C',
            'objeto' => 'x', 'tipo' => 'chamamento_publico', 'status' => 'encerrado']);
        $osc = Osc::forceCreate(['name' => 'OSC', 'cnpj' => '11.111.111/0001-11', 'email' => 'osc@example.com']);

        $ultima = count(Proposta::ETAPAS_CELEBRACAO) - 1;
        $this->proposta = Proposta::forceCreate(['chamamento_id' => $chamamento->id, 'osc_id' => $osc->id, 'titulo' => 'Oficinas',
            'objeto' => 'Oficinas de música', 'status' => 'aprovada', 'valor_solicitado' => 50000, 'valor_proprio' => 0,
            'data_inicio_prevista' => '2026-11-01', 'data_fim_prevista' => '2027-10-31',
            'celebracao_iniciada_em' => now(), 'celebracao_etapa' => $ultima,
            'celebracao_setor' => Proposta::ETAPAS_CELEBRACAO[$ultima]['setor']]);
        Peca::sincronizar($this->proposta, 'celebracao');
        $this->proposta->pecas()->update(['arquivo_path' => 'x.pdf', 'arquivo_nome' => 'dados.pdf', 'conteudo' => '<p>ok</p>', 'assinado_em' => now()]);

        $this->scp = User::factory()->create(['setor' => 'scp', 'status' => true, 'approval_status' => 'aprovado']);
        $this->scp->assignRole('analista_tecnico_scp');
        $this->ug = User::factory()->create(['setor' => 'ug', 'orgao_id' => $orgao->id, 'status' => true, 'approval_status' => 'aprovado']);
        $this->ug->assignRole('responsavel_unidade_gestora');
    }

    public function test_concluir_a_celebracao_cria_o_instrumento_vigente(): void
    {
        $this->actingAs($this->scp)->post("/celebracao/{$this->proposta->id}/concluir")
            ->assertSessionHasNoErrors()->assertSessionHas('success', fn ($m) => str_contains($m, 'instrumento nº 001/' . now()->year));

        $i = $this->proposta->fresh()->instrumento;
        $this->assertNotNull($i);
        $this->assertSame(['vigente', 'termo_fomento', 'Oficinas de música', '50000.00', '2026-11-01', '2027-10-31', true],
            [$i->status, $i->tipo, $i->objeto, $i->valor_repasse, $i->data_inicio->toDateString(), $i->data_fim->toDateString(), $i->publicado_doe]);

        // Entra na Transparência, que mostra os assinados, vigentes e encerrados.
        $this->assertContains($i->status, ['assinado', 'vigente', 'encerrado']);
    }

    public function test_numeracao_segue_no_ano(): void
    {
        Instrumento::forceCreate(['proposta_id' => Proposta::forceCreate(['chamamento_id' => $this->proposta->chamamento_id,
            'osc_id' => $this->proposta->osc_id, 'titulo' => 'Outra', 'objeto' => 'x', 'status' => 'aprovada', 'valor_solicitado' => 1])->id,
            'numero' => '007/' . now()->year, 'tipo' => 'termo_fomento', 'objeto' => 'x', 'valor_repasse' => 1,
            'data_inicio' => now(), 'data_fim' => now()->addYear(), 'status' => 'vigente']);

        $this->actingAs($this->scp)->post("/celebracao/{$this->proposta->id}/concluir")->assertSessionHasNoErrors();

        $this->assertSame('008/' . now()->year, $this->proposta->fresh()->instrumento->numero);
    }

    public function test_no_instrumento_so_a_op_parcial_com_os_dados_bancarios_da_celebracao(): void
    {
        $this->actingAs($this->scp)->post("/celebracao/{$this->proposta->id}/concluir");
        $i = $this->proposta->fresh()->instrumento;

        $this->actingAs($this->ug)->get("/instrumentos/{$i->id}")->assertOk()
            ->assertSee('+ Nova OP da parcela')->assertDontSee('Marcar como Assinado')->assertDontSee('Imprimir Minuta');

        $this->actingAs($this->ug)->post("/instrumentos/{$i->id}/ordens-pagamento", ['tipo' => 'global'])->assertRedirect();
        $op = $i->ordensPagamento()->sole();
        $this->assertSame('parcial', $op->tipo, 'a OP Global é peça da Celebração');

        $this->actingAs($this->ug)->get("/ordens-pagamento/{$op->id}/editar")->assertOk()
            ->assertSee('dados.pdf')->assertSee('enviados pela OSC na Celebração');
    }
}
