<?php

namespace Tests\Feature;

use App\Models\Documento;
use App\Models\ManifestacaoInteresse;
use App\Models\Orgao;
use App\Models\Osc;
use App\Models\User;
use App\Support\CaixaDeEntrada;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Homologação, item 1: a manifestação de interesse enviada pela OSC vai direto
 * à Unidade Gestora da Secretaria escolhida — antes parava na triagem da SCP.
 */
class ManifestacaoParaUgTest extends TestCase
{
    use RefreshDatabase;

    private Orgao $administracao;
    private User $rl;
    private User $ugDona;
    private User $ugAlheia;
    private User $scp;
    private ManifestacaoInteresse $manifestacao;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Mail::fake();

        $this->administracao = Orgao::forceCreate(['name' => 'Administração']);
        $saude = Orgao::forceCreate(['name' => 'Saúde']);

        $servidor = function (string $papel, string $setor, ?Orgao $orgao) {
            $u = User::factory()->create(['setor' => $setor, 'orgao_id' => $orgao?->id, 'status' => true, 'approval_status' => 'aprovado']);
            $u->assignRole($papel);

            return $u->fresh();
        };
        $this->ugDona   = $servidor('responsavel_unidade_gestora', 'ug', $this->administracao);
        $this->ugAlheia = $servidor('responsavel_unidade_gestora', 'ug', $saude);
        $this->scp      = $servidor('analista_tecnico_scp', 'scp', null);

        $osc = Osc::forceCreate(['name' => 'OSC', 'cnpj' => '11.111.111/0001-11', 'email' => 'osc@example.com']);
        $this->rl = User::factory()->create(['osc_id' => $osc->id, 'setor' => 'osc', 'status' => true, 'approval_status' => 'aprovado']);
        $this->rl->assignRole('responsavel_legal');
        $osc->forceFill(['user_id' => $this->rl->id])->save();

        // Manifestação completa: plano, item, parcela e um documento — o mínimo para poder enviar.
        $this->manifestacao = ManifestacaoInteresse::forceCreate([
            'osc_id' => $osc->id, 'orgao_id' => $this->administracao->id, 'titulo' => 'Oficinas para idosos',
            'objeto' => 'x', 'justificativa' => 'x', 'valor_solicitado' => 5000, 'status' => 'rascunho',
        ]);
        $this->manifestacao->criarMeta(['descricao' => 'Meta 1']);
        $this->manifestacao->planoItens()->create(['numero' => 1, 'descricao' => 'Item', 'tipo_despesa' => 'material']);
        $this->manifestacao->desembolsos()->create(['ano' => 2026, 'mes' => 10]);
        Documento::forceCreate(['manifestacao_id' => $this->manifestacao->id, 'nome_original' => 'estatuto.pdf',
            'path' => 'x.pdf', 'mime_type' => 'application/pdf', 'tamanho' => 1, 'uploaded_by' => $this->rl->id]);
    }

    private function enviar(): void
    {
        $this->actingAs($this->rl)->patch("/portal/manifestacoes/{$this->manifestacao->id}/submeter")
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'Unidade Gestora'));
    }

    public function test_enviada_vai_direto_para_a_ug_da_secretaria_escolhida(): void
    {
        $this->enviar();

        $m = $this->manifestacao->fresh();
        $this->assertSame('em_analise', $m->status);
        $this->assertSame('ug', $m->setor_atual);
        $this->assertSame('Em análise na Unidade Gestora', $m->statusLabel());
    }

    public function test_aparece_na_caixa_da_ug_dona_e_nao_na_da_scp_nem_na_de_outra_secretaria(): void
    {
        $this->enviar();
        $naCaixa = fn (User $u) => CaixaDeEntrada::para($u)->itens->contains(fn ($i) => $i['titulo'] === 'Oficinas para idosos');

        $this->assertTrue($naCaixa($this->ugDona));
        $this->assertFalse($naCaixa($this->scp), 'a SCP não faz mais a triagem');
        $this->assertFalse($naCaixa($this->ugAlheia), 'a UG de outra Secretaria não recebe');
    }

    public function test_o_detalhe_diz_que_esta_com_a_unidade_gestora(): void
    {
        $this->enviar();

        $this->actingAs($this->scp)->get("/manifestacoes/{$this->manifestacao->id}")
            ->assertOk()
            ->assertSee('a Unidade Gestora — Administração');
    }

    public function test_a_ug_segue_o_fluxo_e_devolve_a_scp_para_decidir(): void
    {
        $this->enviar();

        $this->actingAs($this->ugDona)->post("/manifestacoes/{$this->manifestacao->id}/parecer", [
            'parecer_favoravel' => 1, 'parecer_ug' => 'Há interesse público.',
        ])->assertSessionHasNoErrors();

        $m = $this->manifestacao->fresh();
        $this->assertSame('analisada', $m->status);
        $this->assertSame('scp', $m->setor_atual);
    }
}
