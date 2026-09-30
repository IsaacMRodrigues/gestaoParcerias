<?php

namespace Tests\Feature;

use App\Models\Chamamento;
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
 * Na Celebração, o plano de trabalho fica aberto a edição pela OSC e pela UG,
 * em qualquer etapa, até o documento "Plano de Trabalho" ser assinado
 * (decisão da gestão, 30/09/2026).
 */
class PlanoNaCelebracaoTest extends TestCase
{
    use RefreshDatabase;

    private Orgao $orgao;
    private Proposta $proposta;
    private User $rl;
    private User $ug;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Mail::fake();

        $this->orgao = Orgao::forceCreate(['name' => 'Educação']);
        $programa = Programa::forceCreate(['orgao_id' => $this->orgao->id, 'name' => 'P', 'tipo' => 'termo_fomento']);
        $chamamento = Chamamento::forceCreate(['programa_id' => $programa->id, 'numero' => '001/2026', 'titulo' => 'Oficinas',
            'objeto' => 'x', 'tipo' => 'chamamento_publico', 'status' => 'publicado']);

        $osc = Osc::forceCreate(['name' => 'Associação Viver', 'cnpj' => '11.111.111/0001-11', 'email' => 'osc@example.com']);
        $this->rl = User::factory()->create(['osc_id' => $osc->id, 'setor' => 'osc', 'status' => true, 'approval_status' => 'aprovado']);
        $this->rl->assignRole('responsavel_legal');
        $osc->forceFill(['user_id' => $this->rl->id])->save();

        // Celebração em curso, com a SCP — não é a etapa da OSC.
        $this->proposta = Proposta::forceCreate(['chamamento_id' => $chamamento->id, 'osc_id' => $osc->id, 'titulo' => 'Oficinas de música',
            'objeto' => 'x', 'status' => 'aprovada', 'valor_solicitado' => 1000,
            'celebracao_iniciada_em' => now(), 'celebracao_etapa' => 3, 'celebracao_setor' => 'scp']);
        Peca::sincronizar($this->proposta, 'celebracao');

        $this->ug = $this->servidor('responsavel_unidade_gestora', $this->orgao);
    }

    private function servidor(string $papel, Orgao $orgao): User
    {
        $u = User::factory()->create(['setor' => 'ug', 'orgao_id' => $orgao->id, 'status' => true, 'approval_status' => 'aprovado']);
        $u->assignRole($papel);

        return $u;
    }

    private function editarComoOsc(string $titulo)
    {
        return $this->actingAs($this->rl)->put("/portal/propostas/{$this->proposta->id}/plano", [
            'titulo' => $titulo, 'objeto' => 'x', 'valor_solicitado' => 1000,
        ]);
    }

    private function editarComoUg(User $quem, string $titulo)
    {
        return $this->actingAs($quem)->put("/propostas/{$this->proposta->id}/plano", [
            'titulo' => $titulo, 'objeto' => 'x', 'valor_solicitado' => 1000,
        ]);
    }

    private function assinarOPlano(): void
    {
        $this->proposta->pecas()->where('chave', 'plano_trabalho')->update(['assinado_em' => now(), 'conteudo' => '<p>Plano</p>']);
    }

    public function test_a_osc_edita_em_qualquer_etapa_da_celebracao(): void
    {
        $this->editarComoOsc('Ajustado pela OSC')->assertSessionHasNoErrors();
        $this->assertSame('Ajustado pela OSC', $this->proposta->fresh()->titulo);

        $this->actingAs($this->rl)->get("/portal/propostas/{$this->proposta->id}")->assertOk()->assertSee('Salvar plano');
        $this->actingAs($this->rl)->get("/celebracao/{$this->proposta->id}")->assertOk()->assertSee('Editar o plano');
    }

    public function test_a_ug_da_secretaria_edita_na_tela_da_proposta(): void
    {
        $this->actingAs($this->ug)->get("/propostas/{$this->proposta->id}")->assertOk()
            ->assertSee('Salvar plano')->assertSee('Adicionar meta');
        $this->editarComoUg($this->ug, 'Ajustado pela UG')->assertSessionHasNoErrors();
        $this->assertSame('Ajustado pela UG', $this->proposta->fresh()->titulo);

        $this->actingAs($this->ug)->post("/propostas/{$this->proposta->id}/plano/metas", ['descricao' => 'Meta da UG'])
            ->assertSessionHasNoErrors();
        $this->assertSame(['Meta da UG'], $this->proposta->metas()->pluck('descricao')->all());
    }

    public function test_so_quem_formaliza_e_da_secretaria(): void
    {
        $comissao = $this->servidor('comissao_selecao', $this->orgao);
        $outraUg = $this->servidor('responsavel_unidade_gestora', Orgao::forceCreate(['name' => 'Saúde']));

        $this->editarComoUg($comissao, 'x')->assertForbidden();
        $this->editarComoUg($outraUg, 'x')->assertForbidden();
        $this->assertSame('Oficinas de música', $this->proposta->fresh()->titulo);
    }

    public function test_assinado_o_documento_do_plano_ninguem_mais_edita(): void
    {
        $this->assinarOPlano();

        $this->editarComoOsc('x')->assertForbidden();
        $this->editarComoUg($this->ug, 'x')->assertForbidden();
        $this->actingAs($this->ug)->get("/propostas/{$this->proposta->id}")->assertOk()->assertDontSee('Salvar plano');
        $this->actingAs($this->rl)->get("/celebracao/{$this->proposta->id}")->assertOk()->assertDontSee('Editar o plano');
    }

    public function test_fora_da_celebracao_a_ug_nao_edita(): void
    {
        $this->proposta->forceFill(['status' => 'em_analise', 'celebracao_iniciada_em' => null])->save();
        $this->editarComoUg($this->ug, 'x')->assertForbidden();

        $this->proposta->forceFill(['status' => 'aprovada', 'celebracao_iniciada_em' => now(), 'celebracao_concluida_em' => now()])->save();
        $this->editarComoUg($this->ug, 'x')->assertForbidden();
        $this->editarComoOsc('x')->assertForbidden();
    }
}
