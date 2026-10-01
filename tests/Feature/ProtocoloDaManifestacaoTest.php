<?php

namespace Tests\Feature;

use App\Models\Documento;
use App\Models\ManifestacaoInteresse;
use App\Models\Orgao;
use App\Models\Osc;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Concerns\PreencheArquivosDaOsc;
use Tests\TestCase;

/**
 * Número de protocolo no envio da manifestação de interesse e da Nova
 * Proposta (decisão da gestão, 29/09/2026): 2026/0001, um livro por ano para
 * as duas.
 */
class ProtocoloDaManifestacaoTest extends TestCase
{
    use RefreshDatabase;
    use PreencheArquivosDaOsc;

    private User $rl;
    private Orgao $orgao;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Mail::fake();

        $this->orgao = Orgao::forceCreate(['name' => 'Educação']);
        $osc = Osc::forceCreate(['name' => 'OSC', 'cnpj' => '11.111.111/0001-11', 'email' => 'osc@example.com']);
        $this->rl = User::factory()->create(['osc_id' => $osc->id, 'setor' => 'osc', 'status' => true, 'approval_status' => 'aprovado']);
        $this->rl->assignRole('responsavel_legal');
        $osc->forceFill(['user_id' => $this->rl->id])->save();
        $this->preencherArquivosDaOsc($osc);
    }

    /** Rascunho completo, pronto para enviar. */
    private function pronta(string $tipo, string $titulo): ManifestacaoInteresse
    {
        $m = ManifestacaoInteresse::forceCreate(['tipo' => $tipo, 'osc_id' => $this->rl->osc_id, 'titulo' => $titulo, 'objeto' => 'x',
            'justificativa' => 'x', 'valor_solicitado' => 100, 'status' => 'rascunho',
            'orgao_id' => $tipo === 'manifestacao' ? $this->orgao->id : null]);
        $meta = $m->criarMeta(['descricao' => 'Meta']);
        $m->planoItens()->create(['numero' => 1, 'descricao' => 'Item', 'tipo_despesa' => 'material_consumo', 'quantidade' => 1, 'valor_unitario' => 100]);
        $m->desembolsos()->create(['meta_id' => $meta->id, 'parcela' => 1, 'valor' => 100]);
        Documento::forceCreate(['manifestacao_id' => $m->id, 'nome_original' => 'estatuto.pdf', 'path' => 'x.pdf',
            'mime_type' => 'application/pdf', 'tamanho' => 1, 'uploaded_by' => $this->rl->id]);

        return $m;
    }

    private function enviar(ManifestacaoInteresse $m)
    {
        return $this->actingAs($this->rl)->patch("/portal/manifestacoes/{$m->id}/submeter");
    }

    public function test_o_envio_gera_o_protocolo_em_sequencia_para_os_dois_tipos(): void
    {
        $manifestacao = $this->pronta('manifestacao', 'Manifestação');
        $proposta = $this->pronta('proposta', 'Nova Proposta');
        $this->assertNull($manifestacao->protocolo, 'rascunho não tem protocolo');

        $ano = now()->year;
        $this->enviar($manifestacao)->assertSessionHas('success', fn ($msg) => str_contains($msg, "protocolo nº {$ano}/0001"));
        $this->enviar($proposta)->assertSessionHas('success', fn ($msg) => str_contains($msg, "protocolo nº {$ano}/0002"));

        $this->assertSame("{$ano}/0001", $manifestacao->fresh()->protocolo);
        $this->assertSame("{$ano}/0002", $proposta->fresh()->protocolo);

        $this->actingAs($this->rl)->get("/portal/manifestacoes/{$proposta->id}")->assertSee("Protocolo nº {$ano}/0002");
        $this->actingAs($this->rl)->get('/portal/novas-propostas')->assertSee("Protocolo nº {$ano}/0002");
    }

    public function test_o_numero_segue_do_maior_do_ano_e_recomeca_no_ano_seguinte(): void
    {
        $ano = now()->year;
        ManifestacaoInteresse::forceCreate(['tipo' => 'manifestacao', 'osc_id' => $this->rl->osc_id, 'titulo' => 'Antiga',
            'objeto' => 'x', 'justificativa' => 'x', 'status' => 'indeferida', 'protocolo' => "{$ano}/0041"]);

        $this->assertSame("{$ano}/0042", ManifestacaoInteresse::proximoProtocolo());

        $this->travelTo(now()->addYear()->startOfYear());
        $this->assertSame(($ano + 1) . '/0001', ManifestacaoInteresse::proximoProtocolo());
    }
}
