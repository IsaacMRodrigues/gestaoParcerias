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
use Tests\Feature\Concerns\PreencheArquivosDaOsc;
use Tests\TestCase;

/**
 * Nova Proposta da OSC (decisão da gestão, 28/09/2026): o conteúdo da
 * manifestação, outro caminho — a SCP decide o fundamento (dispensa ou
 * inexigibilidade, desde 29/09/2026) e encaminha à Unidade Gestora que a
 * atende, e a UG defere ou indefere.
 */
class NovaPropostaTest extends TestCase
{
    use RefreshDatabase;
    use PreencheArquivosDaOsc;

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
        $this->preencherArquivosDaOsc($osc);
    }

    /** A OSC cria pelo portal, completa o plano e a habilitação, e envia. */
    private function enviada(): ManifestacaoInteresse
    {
        $this->actingAs($this->rl)->post('/portal/novas-propostas', [
            'titulo' => 'Oficinas de música',
            'objeto' => 'x', 'justificativa' => 'x', 'plano_aplicacao' => 'Instrutor de música, 10 meses.',
        ])->assertSessionHasNoErrors();

        $p = ManifestacaoInteresse::where('titulo', 'Oficinas de música')->sole();
        // Valor, planilha de itens e desembolso por meta vêm na tela seguinte, no plano de trabalho.
        $p->update(['valor_solicitado' => 8000]);
        $p->planoItens()->create(['numero' => 1, 'descricao' => 'Instrutor', 'tipo_despesa' => 'servicos_pf', 'quantidade' => 10, 'valor_unitario' => 800]);
        $meta = $p->criarMeta(['descricao' => 'Meta 1']);
        $p->desembolsos()->create(['meta_id' => $meta->id, 'parcela' => 1, 'valor' => 8000]);
        Documento::forceCreate(['manifestacao_id' => $p->id, 'nome_original' => 'estatuto.pdf', 'path' => 'x.pdf',
            'mime_type' => 'application/pdf', 'tamanho' => 1, 'uploaded_by' => $this->rl->id]);

        $this->actingAs($this->rl)->patch("/portal/manifestacoes/{$p->id}/submeter")->assertSessionHas('success');

        return $p->fresh();
    }

    private function encaminhada(): ManifestacaoInteresse
    {
        $p = $this->enviada();
        $this->actingAs($this->scp)->post("/manifestacoes/{$p->id}/encaminhar", [
            'orgao_id' => $this->educacao->id, 'fundamento_pedido' => 'inexigibilidade',
        ])->assertSessionHasNoErrors();

        return $p->fresh();
    }

    private function naCaixa(User $u, string $titulo): bool
    {
        return CaixaDeEntrada::para($u)->itens->contains(fn ($i) => $i['titulo'] === $titulo && $i['tramite'] === 'Nova Proposta');
    }

    public function test_a_osc_nao_informa_o_fundamento_nem_a_secretaria(): void
    {
        $this->actingAs($this->rl)->get('/portal/novas-propostas')->assertOk()->assertSee('Nova Proposta');
        $this->actingAs($this->rl)->get('/portal/novas-propostas/nova')->assertOk()
            ->assertDontSee('name="fundamento_pedido"', false)->assertDontSee('name="orgao_id"', false)
            ->assertDontSee('name="valor_solicitado"', false)->assertDontSee('13 – Plano')
            ->assertSee('name="plano_aplicacao"', false)->assertDontSee('Adicionar item')
            ->assertDontSee('name="valor_proprio"', false)->assertDontSee('Cronograma de desembolso');

        // Mesmo que mande, não grava: quem decide é a SCP.
        $this->actingAs($this->rl)->post('/portal/novas-propostas', [
            'titulo' => 'Tentativa', 'fundamento_pedido' => 'dispensa', 'objeto' => 'x', 'justificativa' => 'x', 'plano_aplicacao' => 'x',
        ])->assertSessionHasNoErrors();
        $this->assertNull(ManifestacaoInteresse::where('titulo', 'Tentativa')->value('fundamento_pedido'));
    }

    public function test_o_plano_de_aplicacao_e_digitado_no_primeiro_formulario(): void
    {
        $this->actingAs($this->rl)->post('/portal/novas-propostas', [
            'titulo' => 'Com plano', 'objeto' => 'x', 'justificativa' => 'x',
            'plano_aplicacao' => "Instrutor de música por 10 meses.\nLanche para as turmas.",
            'valor_solicitado' => '99.999,00', // não vem do formulário; se vier, é ignorado
        ])->assertSessionHasNoErrors();

        $p = ManifestacaoInteresse::where('titulo', 'Com plano')->sole();
        $this->assertSame("Instrutor de música por 10 meses.\nLanche para as turmas.", $p->plano_aplicacao);
        $this->assertSame('0.00', $p->valor_solicitado, 'o valor se lança no plano de trabalho');
        $this->assertSame(0, $p->planoItens()->count());

        // O texto segue editável no plano (item 13) e aparece nele.
        $this->actingAs($this->rl)->put("/portal/manifestacoes/{$p->id}/plano/aplicacao", ['plano_aplicacao' => 'Revisto.'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Revisto.', $p->fresh()->plano_aplicacao);
    }

    public function test_sem_plano_de_aplicacao_ou_com_texto_acima_de_mil_caracteres_nao_cria(): void
    {
        $base = ['titulo' => 'Incompleta', 'objeto' => 'x', 'justificativa' => 'x'];

        $this->actingAs($this->rl)->post('/portal/novas-propostas', $base)->assertSessionHasErrors('plano_aplicacao');
        $this->actingAs($this->rl)->post('/portal/novas-propostas', $base + ['plano_aplicacao' => str_repeat('a', 1001)])
            ->assertSessionHasErrors('plano_aplicacao');
        $this->actingAs($this->rl)->post('/portal/novas-propostas', ['objeto' => str_repeat('a', 1001), 'plano_aplicacao' => 'x'] + $base)
            ->assertSessionHasErrors('objeto');

        $this->assertSame(0, ManifestacaoInteresse::where('titulo', 'Incompleta')->count());
    }

    public function test_a_scp_decide_o_fundamento_ao_encaminhar(): void
    {
        $p = $this->enviada();
        $this->assertNull($p->fundamento_pedido);

        $this->actingAs($this->scp)->get("/manifestacoes/{$p->id}")->assertOk()->assertSee('name="fundamento_pedido"', false);
        $this->actingAs($this->scp)->post("/manifestacoes/{$p->id}/encaminhar", ['orgao_id' => $this->educacao->id])
            ->assertSessionHasErrors('fundamento_pedido');
        $this->assertSame('submetida', $p->fresh()->status);

        $this->actingAs($this->scp)->post("/manifestacoes/{$p->id}/encaminhar", [
            'orgao_id' => $this->educacao->id, 'fundamento_pedido' => 'dispensa',
        ])->assertSessionHasNoErrors();
        $this->assertSame('dispensa', $p->fresh()->fundamento_pedido);
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
        $this->assertSame('inexigibilidade', $chamamento->tipo, 'o enquadramento é o que a SCP decidiu');
        $this->assertSame($this->educacao->id, $chamamento->programa->orgao_id);
        $proposta = Proposta::findOrFail($p->proposta_id);
        $this->assertSame(1, $proposta->metas()->count(), 'o plano de trabalho vai junto');
        $this->actingAs($this->ugEducacao)->get('/propostas')->assertSee('Oficinas de música');

        // Direto para a Celebração, sem a análise de proposta (01/10/2026).
        $this->assertSame('aprovada', $proposta->status);
        $this->assertTrue($proposta->celebracaoIniciada());
        $this->assertSame([0, 'ug'], [(int) $proposta->celebracao_etapa, $proposta->celebracao_setor]);
        $this->actingAs($this->ugEducacao)->get('/celebracao')->assertOk()->assertSee('Oficinas de música');
        // Quem deferiu não recebe o próprio aviso, mas tem o item na caixa; a OSC é avisada.
        $this->assertTrue(CaixaDeEntrada::para($this->ugEducacao)->itens->contains(fn ($i) => $i['tramite'] === 'Celebração'));
        Mail::assertQueued(\App\Mail\Aviso::class, fn ($m) => $m->hasTo($this->rl->email));
    }

    public function test_a_manifestacao_deferida_continua_passando_pela_analise(): void
    {
        $m = ManifestacaoInteresse::forceCreate(['tipo' => 'manifestacao', 'osc_id' => $this->rl->osc_id, 'orgao_id' => $this->educacao->id,
            'titulo' => 'Manifestação', 'objeto' => 'x', 'justificativa' => 'x', 'valor_solicitado' => 100,
            'status' => 'analisada', 'setor_atual' => 'scp', 'parecer_favoravel' => true, 'parecer_ug' => 'x']);

        $this->actingAs($this->scp)->post("/manifestacoes/{$m->id}/deferir", [
            'decisao' => 'dispensa', 'numero' => '006/2026', 'fundamento' => 'Art. 30.',
        ])->assertSessionHasNoErrors();

        $proposta = Proposta::findOrFail($m->fresh()->proposta_id);
        $this->assertSame('submetida', $proposta->status);
        $this->assertFalse($proposta->celebracaoIniciada());
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
