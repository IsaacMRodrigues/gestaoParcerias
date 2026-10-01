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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Devolução por documento, em todos os trâmites (decisão da gestão,
 * 30/09/2026): quem devolve marca o que está errado; só isso reabre, com o
 * motivo à vista no documento e no alto do trâmite, e o trâmite volta para a
 * etapa do mais antigo dos marcados.
 */
class DevolucaoPorDocumentoTest extends TestCase
{
    use RefreshDatabase;

    private Orgao $orgao;
    private Chamamento $chamamento;
    private User $ug;
    private User $scp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Mail::fake();
        Storage::fake('local');

        $this->orgao = Orgao::forceCreate(['name' => 'Educação']);
        $programa = Programa::forceCreate(['orgao_id' => $this->orgao->id, 'name' => 'P', 'tipo' => 'termo_fomento']);
        $this->chamamento = Chamamento::forceCreate(['programa_id' => $programa->id, 'numero' => '001/2026', 'titulo' => 'Oficinas',
            'objeto' => 'x', 'tipo' => 'chamamento_publico', 'status' => 'em_analise']);

        $this->ug = User::factory()->create(['setor' => 'ug', 'orgao_id' => $this->orgao->id, 'status' => true, 'approval_status' => 'aprovado']);
        $this->ug->assignRole('responsavel_unidade_gestora');
        $this->scp = User::factory()->create(['setor' => 'scp', 'status' => true, 'approval_status' => 'aprovado']);
        $this->scp->assignRole('analista_tecnico_scp');
    }

    private function assinado(): array
    {
        // Sem código de validação: é único, e aqui se marcam várias peças de uma vez.
        return ['conteudo' => '<p>Texto</p>', 'assinado_em' => now(), 'assinado_por' => $this->ug->id, 'assinante_nome' => 'UG',
            'arquivo_path' => 'x.pdf', 'arquivo_nome' => 'x.pdf'];
    }

    public function test_celebracao_so_o_documento_marcado_reabre_e_o_motivo_fica_a_vista(): void
    {
        $osc = Osc::forceCreate(['name' => 'Associação Viver', 'cnpj' => '11.111.111/0001-11', 'email' => 'osc@example.com']);
        $proposta = Proposta::forceCreate(['chamamento_id' => $this->chamamento->id, 'osc_id' => $osc->id, 'titulo' => 'Oficinas de música',
            'objeto' => 'x', 'status' => 'aprovada', 'valor_solicitado' => 1,
            'celebracao_iniciada_em' => now(), 'celebracao_etapa' => 5, 'celebracao_setor' => 'ug']);
        Peca::sincronizar($proposta, 'celebracao');
        $proposta->pecas()->whereIn('chave', ['aprovacao_plano', 'parecer_financeiro'])->update($this->assinado());

        $aprovacao = $proposta->pecas()->where('chave', 'aprovacao_plano')->sole();
        $parecer = $proposta->pecas()->where('chave', 'parecer_financeiro')->sole();
        $this->assertTrue($proposta->documentosDevolviveis()->contains('id', $aprovacao->id));

        $this->actingAs($this->ug)->post("/celebracao/{$proposta->id}/devolver", [
            'parecer' => 'A aprovação cita o valor errado: são R$ 8.000, não R$ 80.000.',
            'documentos' => [$aprovacao->id],
        ])->assertSessionHasNoErrors();

        $proposta = $proposta->fresh();
        $this->assertSame(2, (int) $proposta->celebracao_etapa, 'volta para a etapa do documento');
        $aprovacao = $aprovacao->fresh();
        $this->assertFalse($aprovacao->assinado());
        $this->assertTrue($aprovacao->devolvida());
        $this->assertStringContainsString('R$ 8.000', $aprovacao->devolucao_motivo);
        $this->assertTrue($parecer->fresh()->assinado(), 'o que não foi marcado fica como estava');

        $this->actingAs($this->ug)->get("/celebracao/{$proposta->id}")->assertOk()
            ->assertSee('Devolvido por')->assertSee('Documentos a corrigir:')
            ->assertSee('Devolvido para correção')->assertSee('R$ 8.000, não R$ 80.000');

        // Assinado de novo, sai a marca.
        $this->actingAs($this->ug)->patch("/pecas/{$aprovacao->id}/assinar")->assertSessionHasNoErrors();
        $this->assertFalse($aprovacao->fresh()->devolvida());
    }

    public function test_selecao_arquivo_devolvido_pede_o_arquivo_corrigido(): void
    {
        $this->chamamento->forceFill(['selecao_etapa' => 3, 'selecao_setor' => 'ug'])->save();
        Peca::sincronizar($this->chamamento, 'chamamento_publico');
        $publicacao = $this->chamamento->pecas()->where('chave', 'pub_resultado_parcial')->sole();
        $publicacao->forceFill(['arquivo_path' => 'pub.pdf', 'arquivo_nome' => 'pub.pdf'])->save();

        $this->actingAs($this->ug)->post("/chamamentos/{$this->chamamento->id}/selecao/devolver", [
            'parecer' => 'A publicação anexada é a de outro chamamento.',
            'documentos' => [$publicacao->id],
        ])->assertSessionHasNoErrors();

        $c = $this->chamamento->fresh();
        $this->assertSame([1, 'scp'], [(int) $c->selecao_etapa, $c->selecao_setor]);
        $this->assertContains('Publicação do resultado provisório (devolvido — enviar o arquivo corrigido)', $c->pendenciasSelecao());

        $this->actingAs($this->scp)->post("/pecas/{$publicacao->id}/arquivo", [
            'arquivo' => UploadedFile::fake()->create('certa.pdf', 20, 'application/pdf'),
        ])->assertSessionHasNoErrors();
        $this->assertFalse($publicacao->fresh()->devolvida());
        $this->assertNotContains('Publicação do resultado provisório (devolvido — enviar o arquivo corrigido)', $c->fresh()->pendenciasSelecao());
    }

    public function test_sem_marcar_documento_a_devolucao_e_a_de_sempre(): void
    {
        $this->chamamento->forceFill(['selecao_etapa' => 3, 'selecao_setor' => 'ug'])->save();
        Peca::sincronizar($this->chamamento, 'chamamento_publico');
        $this->chamamento->pecas()->where('chave', 'resultado_parcial')->update($this->assinado());

        $this->actingAs($this->ug)->post("/chamamentos/{$this->chamamento->id}/selecao/devolver", ['parecer' => 'Rever o prazo.'])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, (int) $this->chamamento->fresh()->selecao_etapa);
        $this->assertTrue($this->chamamento->pecas()->where('chave', 'resultado_parcial')->sole()->assinado());
    }

    public function test_nao_se_devolve_documento_de_etapa_que_ainda_nao_passou(): void
    {
        $this->chamamento->forceFill(['selecao_etapa' => 1, 'selecao_setor' => 'scp'])->save();
        Peca::sincronizar($this->chamamento, 'chamamento_publico');
        $definitivo = $this->chamamento->pecas()->where('chave', 'resultado_definitivo')->sole();
        $definitivo->forceFill($this->assinado())->save();

        $this->actingAs($this->scp)->post("/chamamentos/{$this->chamamento->id}/selecao/devolver", [
            'parecer' => 'x', 'documentos' => [$definitivo->id],
        ])->assertSessionHasErrors('documentos.0');
        $this->assertTrue($definitivo->fresh()->assinado());
    }

    public function test_planejamento_tambem_devolve_por_documento(): void
    {
        $processo = Processo::forceCreate(['numero' => '0207.0001.2026.01', 'orgao_id' => $this->orgao->id, 'created_by' => $this->ug->id,
            'status' => 'em_tramite', 'setor_atual' => 'scp', 'etapa' => 2]);
        $memorando = ProcessoPeca::forceCreate(['processo_id' => $processo->id, 'tipo' => 'oficio', 'conteudo' => '<p>Memorando</p>',
            'assinado_por' => $this->ug->id, 'assinado_em' => now(), 'assinante_nome' => 'UG', 'codigo_validacao' => 'AAAA-1111-CC']);
        $termo = ProcessoPeca::forceCreate(['processo_id' => $processo->id, 'tipo' => 'termo_referencia', 'conteudo' => '<p>TR</p>',
            'assinado_por' => $this->ug->id, 'assinado_em' => now(), 'assinante_nome' => 'UG', 'codigo_validacao' => 'AAAA-2222-CC']);

        $this->actingAs($this->scp)->post("/processos/{$processo->id}/devolver", [
            'parecer' => 'O Termo de Referência não traz o público-alvo.',
            'documentos' => [$termo->id],
        ])->assertSessionHasNoErrors();

        $processo = $processo->fresh();
        $this->assertSame([0, 'ug'], [(int) $processo->etapa, $processo->setor_atual]);
        $this->assertTrue($termo->fresh()->devolvida());
        $this->assertFalse($termo->fresh()->assinado());
        $this->assertTrue($memorando->fresh()->assinado());
        $this->assertStringContainsString('Documentos a corrigir: Termo de Referência', $processo->tramitacoes()->latest('id')->first()->parecer);

        $this->actingAs($this->ug)->get("/processos/{$processo->id}")->assertOk()
            ->assertSee('Devolvido para correção')->assertSee('não traz o público-alvo');
    }
}
