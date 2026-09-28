<?php

namespace Tests\Feature;

use App\Mail\Aviso;
use App\Models\Chamamento;
use App\Models\Orgao;
use App\Models\Osc;
use App\Models\Peca;
use App\Models\Programa;
use App\Models\Proposta;
use App\Models\User;
use App\Support\CaixaDeEntrada;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Cancelar o chamamento sem excluir, e reabrir (decisão da gestão, 28/09/2026).
 */
class ChamamentoCanceladoTest extends TestCase
{
    use RefreshDatabase;

    private Chamamento $chamamento;
    private User $ugDona;
    private User $rl;
    private Proposta $rascunho;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Mail::fake();

        $educacao = Orgao::forceCreate(['name' => 'Educação']);
        $programa = Programa::forceCreate(['orgao_id' => $educacao->id, 'name' => 'Programa', 'tipo' => 'termo_fomento']);
        $this->chamamento = Chamamento::forceCreate(['programa_id' => $programa->id, 'numero' => '001/2026', 'titulo' => 'Oficinas',
            'objeto' => 'x', 'tipo' => 'chamamento_publico', 'status' => 'publicado', 'selecao_etapa' => 0, 'selecao_setor' => 'ug']);

        $this->ugDona = $this->servidor('responsavel_unidade_gestora', 'ug', $educacao);

        $osc = Osc::forceCreate(['name' => 'OSC', 'cnpj' => '11.111.111/0001-11', 'email' => 'osc@example.com']);
        $this->rl = User::factory()->create(['osc_id' => $osc->id, 'setor' => 'osc', 'status' => true, 'approval_status' => 'aprovado']);
        $this->rl->assignRole('responsavel_legal');
        $osc->forceFill(['user_id' => $this->rl->id])->save();

        $this->rascunho = Proposta::forceCreate(['chamamento_id' => $this->chamamento->id, 'osc_id' => $osc->id,
            'titulo' => 'Proposta', 'objeto' => 'x', 'status' => 'rascunho', 'valor_solicitado' => 1000]);
    }

    private function servidor(string $papel, string $setor, ?Orgao $orgao): User
    {
        $u = User::factory()->create(['setor' => $setor, 'orgao_id' => $orgao?->id, 'status' => true, 'approval_status' => 'aprovado']);
        $u->assignRole($papel);

        return $u->fresh();
    }

    private function cancelar(?User $quem = null, string $motivo = 'Recurso orçamentário suspenso.')
    {
        return $this->actingAs($quem ?? $this->ugDona)->post("/chamamentos/{$this->chamamento->id}/cancelar", ['motivo' => $motivo]);
    }

    public function test_a_ug_dona_cancela_com_motivo_sem_excluir_e_as_oscs_sao_avisadas(): void
    {
        $this->cancelar()->assertSessionHasNoErrors();

        $c = $this->chamamento->fresh();
        $this->assertTrue($c->cancelado());
        $this->assertSame('publicado', $c->status_antes_cancelar);
        $this->assertNotNull(Proposta::find($this->rascunho->id), 'nada é excluído');
        $this->assertSame('Recurso orçamentário suspenso.', $c->ultimoCancelamento()->motivo);
        Mail::assertQueued(Aviso::class, fn ($m) => $m->hasTo($this->rl->email) && str_contains($m->assunto, 'cancelado'));
    }

    public function test_sem_motivo_nao_cancela(): void
    {
        $this->cancelar(motivo: '')->assertSessionHasErrors('motivo');
        $this->assertFalse($this->chamamento->fresh()->cancelado());
    }

    public function test_so_a_ug_da_secretaria_dona_cancela(): void
    {
        $ugAlheia = $this->servidor('responsavel_unidade_gestora', 'ug', Orgao::forceCreate(['name' => 'Saúde']));
        $scp      = $this->servidor('analista_tecnico_scp', 'scp', null);

        $this->cancelar($ugAlheia)->assertForbidden();
        $this->cancelar($scp)->assertForbidden();
        $this->assertFalse($this->chamamento->fresh()->cancelado());
    }

    public function test_depois_da_homologacao_ou_com_proposta_aprovada_nao_cancela(): void
    {
        $this->chamamento->forceFill(['selecao_concluida_em' => now()])->save();
        $this->cancelar()->assertSessionHasErrors('cancelamento');

        $this->chamamento->forceFill(['selecao_concluida_em' => null])->save();
        $this->rascunho->forceFill(['status' => 'aprovada'])->save();
        $this->cancelar()->assertSessionHasErrors('cancelamento');

        $this->assertFalse($this->chamamento->fresh()->cancelado());
    }

    public function test_cancelado_trava_selecao_envio_de_proposta_pecas_e_caixa(): void
    {
        $this->cancelar();
        $c = $this->chamamento->fresh();

        $this->actingAs($this->ugDona)->post("/chamamentos/{$c->id}/selecao/avancar")->assertStatus(422);
        $this->actingAs($this->rl)->patch("/portal/propostas/{$this->rascunho->id}/submeter")->assertStatus(422);
        $this->assertFalse($c->faseRecursalAberta());

        $peca = Peca::forceCreate(['pecaable_type' => Chamamento::class, 'pecaable_id' => $c->id, 'categoria' => 'chamamento_publico',
            'chave' => 'edital', 'rotulo' => 'Edital', 'tipo' => 'modelo', 'conteudo' => '<p>x</p>']);
        $this->actingAs($this->ugDona)->put("/pecas/{$peca->id}", ['conteudo' => '<p>y</p>'])->assertStatus(422);

        $this->assertFalse(CaixaDeEntrada::para($this->ugDona)->itens->contains(fn ($i) => $i['tramite'] === 'Seleção'));
    }

    public function test_reabrir_devolve_ao_status_anterior_e_avisa(): void
    {
        $this->cancelar();
        Mail::fake();

        $this->actingAs($this->ugDona)->post("/chamamentos/{$this->chamamento->id}/reabrir", ['motivo' => 'Orçamento liberado.'])
            ->assertSessionHasNoErrors();

        $c = $this->chamamento->fresh();
        $this->assertSame('publicado', $c->status);
        $this->assertNull($c->status_antes_cancelar);
        $this->assertSame(['reaberto', 'cancelado'], $c->cancelamentos()->pluck('acao')->all());
        Mail::assertQueued(Aviso::class, fn ($m) => $m->hasTo($this->rl->email) && str_contains($m->assunto, 'reaberto'));
    }

    public function test_o_formulario_de_edicao_nao_cancela_nem_descancela(): void
    {
        $admin = $this->servidor('administrador_setorial', 'ti', null);
        $programa = $this->chamamento->programa;
        $dados = ['numero' => '001/2026', 'titulo' => 'Oficinas', 'objeto' => 'x', 'tipo' => 'chamamento_publico'];

        $this->actingAs($admin)->put("/programas/{$programa->id}/chamamentos/{$this->chamamento->id}", $dados + ['status' => 'cancelado'])
            ->assertSessionHasErrors('status');

        $this->cancelar();
        $this->actingAs($admin)->put("/programas/{$programa->id}/chamamentos/{$this->chamamento->id}", $dados + ['status' => 'publicado']);
        $this->assertTrue($this->chamamento->fresh()->cancelado(), 'cancelado só sai pelo botão de reabrir');
    }

    public function test_a_osc_ve_o_aviso_no_portal(): void
    {
        $this->cancelar();

        $this->actingAs($this->rl)->get("/portal/chamamentos/{$this->chamamento->id}")
            ->assertOk()->assertSee('Este chamamento foi cancelado pela Prefeitura.')->assertSee('Recurso orçamentário suspenso.');
        $this->actingAs($this->rl)->get("/portal/propostas/{$this->rascunho->id}")
            ->assertOk()->assertSee('Este chamamento foi cancelado pela Prefeitura.');
    }
}
