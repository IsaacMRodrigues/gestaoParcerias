<?php

namespace Tests\Feature;

use App\Models\Chamamento;
use App\Models\Documento;
use App\Models\ManifestacaoInteresse;
use App\Models\Orgao;
use App\Models\Osc;
use App\Models\Proposta;
use App\Models\User;
use App\Support\CaixaDeEntrada;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Nova Proposta da OSC (decisão da gestão, 28/09/2026): o conteúdo da
 * manifestação, outro caminho — a OSC informa dispensa ou inexigibilidade, a
 * SCP encaminha à Unidade Gestora que a atende, e a UG defere ou indefere.
 */
class NovaPropostaTest extends TestCase
{
    use RefreshDatabase;

    private Orgao $educacao;
    private User $rl;
    private User $scp;
    private User $ugEducacao;
    private User $ugSaude;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Mail::fake();

        $this->educacao = Orgao::forceCreate(['name' => 'Educação']);
        $saude = Orgao::forceCreate(['name' => 'Saúde']);

        $servidor = function (string $papel, string $setor, ?Orgao $orgao) {
            $u = User::factory()->create(['setor' => $setor, 'orgao_id' => $orgao?->id, 'status' => true, 'approval_status' => 'aprovado']);
            $u->assignRole($papel);

            return $u->fresh();
        };
        $this->scp        = $servidor('analista_tecnico_scp', 'scp', null);
        $this->ugEducacao = $servidor('responsavel_unidade_gestora', 'ug', $this->educacao);
        $this->ugSaude    = $servidor('responsavel_unidade_gestora', 'ug', $saude);

        $osc = Osc::forceCreate(['name' => 'OSC', 'cnpj' => '11.111.111/0001-11', 'email' => 'osc@example.com']);
        $this->rl = User::factory()->create(['osc_id' => $osc->id, 'setor' => 'osc', 'status' => true, 'approval_status' => 'aprovado']);
        $this->rl->assignRole('responsavel_legal');
        $osc->forceFill(['user_id' => $this->rl->id])->save();
    }

    /** A OSC cria pelo portal, completa o plano e a habilitação, e envia. */
    private function enviada(): ManifestacaoInteresse
    {
        $this->actingAs($this->rl)->post('/portal/novas-propostas', [
            'titulo' => 'Oficinas de música', 'fundamento_pedido' => 'inexigibilidade',
            'objeto' => 'x', 'justificativa' => 'x', 'valor_solicitado' => 8000,
        ])->assertSessionHasNoErrors();

        $p = ManifestacaoInteresse::where('titulo', 'Oficinas de música')->sole();
        $p->criarMeta(['descricao' => 'Meta 1']);
        $p->planoItens()->create(['numero' => 1, 'descricao' => 'Item', 'tipo_despesa' => 'material']);
        $p->desembolsos()->create(['ano' => 2026, 'mes' => 10]);
        Documento::forceCreate(['manifestacao_id' => $p->id, 'nome_original' => 'estatuto.pdf', 'path' => 'x.pdf',
            'mime_type' => 'application/pdf', 'tamanho' => 1, 'uploaded_by' => $this->rl->id]);

        $this->actingAs($this->rl)->patch("/portal/manifestacoes/{$p->id}/submeter")->assertSessionHas('success');

        return $p->fresh();
    }

    private function encaminhada(): ManifestacaoInteresse
    {
        $p = $this->enviada();
        $this->actingAs($this->scp)->post("/manifestacoes/{$p->id}/encaminhar", ['orgao_id' => $this->educacao->id])
            ->assertSessionHasNoErrors();

        return $p->fresh();
    }

    private function naCaixa(User $u, string $titulo): bool
    {
        return CaixaDeEntrada::para($u)->itens->contains(fn ($i) => $i['titulo'] === $titulo && $i['tramite'] === 'Nova Proposta');
    }

    public function test_o_portal_tem_o_item_e_a_osc_informa_o_fundamento_e_nao_a_secretaria(): void
    {
        $this->actingAs($this->rl)->get('/portal/novas-propostas')->assertOk()->assertSee('Nova Proposta');
        $this->actingAs($this->rl)->get('/portal/novas-propostas/nova')
            ->assertOk()->assertSee('name="fundamento_pedido"', false)->assertDontSee('name="orgao_id"', false);

        $this->actingAs($this->rl)->post('/portal/novas-propostas', [
            'titulo' => 'Sem fundamento', 'objeto' => 'x', 'justificativa' => 'x', 'valor_solicitado' => 1,
        ])->assertSessionHasErrors('fundamento_pedido');
    }

    public function test_enviada_vai_primeiro_para_a_scp(): void
    {
        $p = $this->enviada();

        $this->assertSame('proposta', $p->tipo);
        $this->assertSame('submetida', $p->status);
        $this->assertSame('scp', $p->setor_atual);
        $this->assertNull($p->orgao_id, 'a Secretaria é a SCP quem escolhe');
        $this->assertTrue($this->naCaixa($this->scp, 'Oficinas de música'));
        $this->assertFalse($this->naCaixa($this->ugEducacao, 'Oficinas de música'));
    }

    public function test_a_scp_encaminha_a_ug_escolhida_e_so_ela_recebe(): void
    {
        $p = $this->encaminhada();

        $this->assertSame($this->educacao->id, $p->orgao_id);
        $this->assertSame(['em_analise', 'ug'], [$p->status, $p->setor_atual]);
        $this->assertTrue($this->naCaixa($this->ugEducacao, 'Oficinas de música'));
        $this->assertFalse($this->naCaixa($this->ugSaude, 'Oficinas de música'));
        $this->actingAs($this->ugEducacao)->get('/propostas')->assertOk()
            ->assertSee('Novas Propostas em análise')->assertSee('Oficinas de música');
    }

    public function test_a_ug_defere_e_nasce_o_chamamento_e_a_proposta_com_o_plano(): void
    {
        $p = $this->encaminhada();

        $this->actingAs($this->ugEducacao)->post("/manifestacoes/{$p->id}/parecer", ['parecer_favoravel' => 1, 'parecer_ug' => 'x'])
            ->assertStatus(422);
        $this->actingAs($this->scp)->post("/manifestacoes/{$p->id}/deferir", ['numero' => '1', 'fundamento' => 'x'])
            ->assertForbidden();

        $this->actingAs($this->ugEducacao)->post("/manifestacoes/{$p->id}/deferir", [
            'numero' => '005/2026', 'fundamento' => 'Art. 31: única entidade capaz.',
        ])->assertSessionHasNoErrors();

        $p = $p->fresh();
        $this->assertSame('deferida', $p->status);
        $chamamento = Chamamento::findOrFail($p->chamamento_id);
        $this->assertSame('inexigibilidade', $chamamento->tipo, 'o enquadramento é o que a OSC pediu');
        $this->assertSame($this->educacao->id, $chamamento->programa->orgao_id);
        $proposta = Proposta::findOrFail($p->proposta_id);
        $this->assertSame(1, $proposta->metas()->count(), 'o plano de trabalho vai junto');
        $this->actingAs($this->ugEducacao)->get('/propostas')->assertSee('Oficinas de música');
    }

    public function test_a_ug_indefere_com_motivo(): void
    {
        $p = $this->encaminhada();

        $this->actingAs($this->ugSaude)->post("/manifestacoes/{$p->id}/indeferir", ['decisao_motivo' => 'x'])->assertForbidden();
        $this->actingAs($this->ugEducacao)->post("/manifestacoes/{$p->id}/indeferir", ['decisao_motivo' => 'Sem orçamento.'])
            ->assertSessionHasNoErrors();

        $this->assertSame('indeferida', $p->fresh()->status);
    }

    public function test_a_manifestacao_de_interesse_continua_igual(): void
    {
        $this->actingAs($this->rl)->get('/portal/manifestacoes/nova')->assertOk()->assertSee('name="orgao_id"', false);
        $this->actingAs($this->rl)->post('/portal/manifestacoes', [
            'orgao_id' => $this->educacao->id, 'titulo' => 'Manifestação', 'objeto' => 'x', 'justificativa' => 'x', 'valor_solicitado' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertSame('manifestacao', ManifestacaoInteresse::where('titulo', 'Manifestação')->value('tipo'));
    }
}
