<?php

namespace Tests\Feature;

use App\Mail\Aviso;
use App\Models\Chamamento;
use App\Models\Documento;
use App\Models\ManifestacaoInteresse;
use App\Models\Orgao;
use App\Models\Osc;
use App\Models\OscArquivo;
use App\Models\Peca;
use App\Models\Programa;
use App\Models\Proposta;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\PreencheArquivosDaOsc;
use Tests\TestCase;

/**
 * "Arquivos da OSC" (pedido da gestão, 30/09/2026, com o portal do DF como
 * modelo): a OSC anexa uma vez as certidões, o estatuto, a ata e as
 * declarações; cada envio é uma versão; a Prefeitura analisa em cada parceria.
 */
class ArquivosDaOscTest extends TestCase
{
    use RefreshDatabase;
    use PreencheArquivosDaOsc;

    private Orgao $orgao;
    private Osc $osc;
    private User $rl;
    private User $ug;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Mail::fake();
        Storage::fake('local');

        $this->orgao = Orgao::forceCreate(['name' => 'Educação']);
        $this->osc = Osc::forceCreate(['name' => 'Associação Viver', 'cnpj' => '11.111.111/0001-11', 'email' => 'osc@example.com',
            'resp_nome' => 'Maria Presidente']);
        $this->rl = User::factory()->create(['osc_id' => $this->osc->id, 'setor' => 'osc', 'status' => true, 'approval_status' => 'aprovado']);
        $this->rl->assignRole('responsavel_legal');
        $this->osc->forceFill(['user_id' => $this->rl->id])->save();
        $this->ug = User::factory()->create(['setor' => 'ug', 'orgao_id' => $this->orgao->id, 'status' => true, 'approval_status' => 'aprovado']);
        $this->ug->assignRole('responsavel_unidade_gestora');
    }

    private function enviar(string $tipo, array $extra = [])
    {
        return $this->actingAs($this->rl)->post("/portal/arquivos/{$tipo}", array_merge([
            'arquivo' => UploadedFile::fake()->create("{$tipo}.pdf", 30, 'application/pdf'),
        ], $extra));
    }

    private function proposta(): Proposta
    {
        $programa = Programa::forceCreate(['orgao_id' => $this->orgao->id, 'name' => 'P', 'tipo' => 'termo_fomento']);
        $chamamento = Chamamento::forceCreate(['programa_id' => $programa->id, 'numero' => '001/2026', 'titulo' => 'C',
            'objeto' => 'x', 'tipo' => 'chamamento_publico', 'status' => 'publicado']);

        return Proposta::forceCreate(['chamamento_id' => $chamamento->id, 'osc_id' => $this->osc->id, 'titulo' => 'Oficinas',
            'objeto' => 'x', 'status' => 'em_analise', 'valor_solicitado' => 1]);
    }

    public function test_a_osc_anexa_uma_vez_e_cada_envio_e_uma_versao(): void
    {
        $this->actingAs($this->rl)->get('/portal/arquivos')->assertOk()
            ->assertSee('CND — Certidões negativas')->assertSee('Declarações')->assertSee('Não anexado')->assertSee('Enviar arquivo');

        $this->enviar('cndt')->assertSessionHasErrors('validade');
        $this->enviar('cndt', ['validade' => now()->subDay()->toDateString()])->assertSessionHasErrors('validade');
        $this->enviar('cndt', ['validade' => now()->addMonth()->toDateString()])->assertSessionHasNoErrors();
        $this->enviar('cndt', ['validade' => now()->addMonths(2)->toDateString()])->assertSessionHasNoErrors();
        $this->enviar('estatuto')->assertSessionHasNoErrors(); // documento institucional: sem validade

        $atual = $this->osc->arquivosAtuais()['cndt'];
        $this->assertSame(2, $atual->versao);
        $this->assertSame(2, $this->osc->arquivos()->where('tipo', 'cndt')->count(), 'a versão anterior fica no histórico');

        $this->actingAs($this->rl)->get('/portal/arquivos')->assertSee('Versão 2 · enviada em')->assertSee('Histórico (2 versões)')->assertSee('Enviar nova versão');
        $this->enviar('nao_existe')->assertNotFound();
    }

    public function test_quem_nao_tem_a_funcao_de_documentos_ve_mas_nao_envia(): void
    {
        $membro = User::factory()->create(['osc_id' => $this->osc->id, 'setor' => 'osc', 'status' => true, 'approval_status' => 'aprovado']);
        $membro->assignRole('membro_osc');

        $this->actingAs($membro)->get('/portal/arquivos')->assertOk()->assertSee('Documentos da organização');
        $this->actingAs($membro)->post('/portal/arquivos/estatuto', [
            'arquivo' => UploadedFile::fake()->create('e.pdf', 10, 'application/pdf'),
        ])->assertForbidden();
    }

    public function test_a_declaracao_vem_preenchida_com_o_cadastro(): void
    {
        $this->actingAs($this->rl)->get('/portal/arquivos/decl_art7/declaracao')->assertOk()
            ->assertSee('Associação Viver')->assertSee('Maria Presidente');
        $this->actingAs($this->rl)->get('/portal/arquivos/estatuto/declaracao')->assertNotFound();
    }

    public function test_area_incompleta_ou_certidao_vencida_impede_o_envio_da_manifestacao(): void
    {
        $m = ManifestacaoInteresse::forceCreate(['tipo' => 'proposta', 'osc_id' => $this->osc->id, 'titulo' => 'P', 'objeto' => 'x',
            'justificativa' => 'x', 'valor_solicitado' => 100, 'status' => 'rascunho']);
        $meta = $m->criarMeta(['descricao' => 'Meta']);
        $m->planoItens()->create(['numero' => 1, 'descricao' => 'Item', 'tipo_despesa' => 'material_consumo', 'quantidade' => 1, 'valor_unitario' => 100]);
        $m->desembolsos()->create(['meta_id' => $meta->id, 'parcela' => 1, 'valor' => 100]);
        Documento::forceCreate(['manifestacao_id' => $m->id, 'tipo' => 'experiencia_previa', 'nome_original' => 'exp.pdf', 'path' => 'x.pdf',
            'mime_type' => 'application/pdf', 'tamanho' => 1, 'uploaded_by' => $this->rl->id]);

        $this->assertContains('Arquivos da OSC: ' . OscArquivo::rotulo('estatuto') . ' (não anexado)', $m->pendenciasParaSubmeter());

        $this->preencherArquivosDaOsc($this->osc);
        $this->assertSame([], $m->fresh()->pendenciasParaSubmeter());

        OscArquivo::where('osc_id', $this->osc->id)->where('tipo', 'crf_fgts')->update(['validade' => now()->subDay()]);
        $this->assertStringContainsString('vencida', implode(' ', $m->fresh()->pendenciasParaSubmeter()));
    }

    public function test_a_celebracao_cobra_a_area_na_etapa_da_osc_e_nao_tem_mais_certidoes_nem_declaracoes(): void
    {
        $proposta = $this->proposta();
        $proposta->forceFill(['status' => 'aprovada', 'celebracao_iniciada_em' => now(), 'celebracao_etapa' => 1, 'celebracao_setor' => 'osc'])->save();
        Peca::sincronizar($proposta, 'celebracao');

        $chaves = $proposta->pecas()->pluck('chave');
        $this->assertNotContains('certidoes_habilitacao', $chaves);
        $this->assertNotContains('decl_art39', $chaves);
        $this->assertNotEmpty(array_filter($proposta->fresh()->pendenciasCelebracao(), fn ($p) => str_starts_with($p, 'Arquivos da OSC:')));

        $this->preencherArquivosDaOsc($this->osc);
        $this->assertEmpty(array_filter($proposta->fresh()->pendenciasCelebracao(), fn ($p) => str_starts_with($p, 'Arquivos da OSC:')));
    }

    public function test_a_prefeitura_analisa_em_cada_parceria_e_versao_nova_pede_nova_analise(): void
    {
        $this->preencherArquivosDaOsc($this->osc);
        $proposta = $this->proposta();
        $outra = Proposta::forceCreate(['chamamento_id' => $proposta->chamamento_id, 'osc_id' => $this->osc->id, 'titulo' => 'Outra',
            'objeto' => 'x', 'status' => 'em_analise', 'valor_solicitado' => 1]);
        $estatuto = $this->osc->arquivosAtuais()['estatuto'];

        $this->actingAs($this->ug)->get("/propostas/{$proposta->id}")->assertOk()
            ->assertSee('Arquivos da OSC')->assertSee('Aguardando análise nesta parceria');

        $url = "/propostas/{$proposta->id}/arquivos-osc/{$estatuto->id}/analisar";
        $this->actingAs($this->ug)->post($url, ['situacao' => 'recusado'])->assertSessionHasErrors('motivo');
        $this->actingAs($this->ug)->post($url, ['situacao' => 'recusado', 'motivo' => 'Falta a última alteração.'])->assertSessionHasNoErrors();

        $this->actingAs($this->ug)->get("/propostas/{$proposta->id}")->assertSee('Recusado nesta parceria')->assertSee('Falta a última alteração.');
        $this->assertSame(0, $estatuto->analises()->where('proposta_id', $outra->id)->count(), 'a análise é desta parceria');

        // A OSC manda versão nova: a análise da versão anterior não vale para ela.
        $this->enviar('estatuto')->assertSessionHasNoErrors();
        $novo = $this->osc->fresh()->arquivosAtuais()['estatuto'];
        $this->assertSame(0, $novo->analises()->count());
    }

    public function test_quem_baixa_e_a_propria_osc_e_a_prefeitura(): void
    {
        Storage::disk('local')->put('osc-arquivos/x/estatuto.pdf', '%PDF');
        $arquivo = OscArquivo::forceCreate(['osc_id' => $this->osc->id, 'tipo' => 'estatuto', 'versao' => 1,
            'arquivo_path' => 'osc-arquivos/x/estatuto.pdf', 'arquivo_nome' => 'estatuto.pdf']);
        $outraOsc = Osc::forceCreate(['name' => 'Outra', 'cnpj' => '22.222.222/0001-22', 'email' => 'o@example.com']);
        $deOutra = User::factory()->create(['osc_id' => $outraOsc->id, 'setor' => 'osc', 'status' => true, 'approval_status' => 'aprovado']);
        $deOutra->assignRole('responsavel_legal');
        $outraOsc->forceFill(['user_id' => $deOutra->id])->save();

        $this->actingAs($this->rl)->get("/arquivos-osc/{$arquivo->id}")->assertOk();
        $this->actingAs($this->ug)->get("/arquivos-osc/{$arquivo->id}")->assertOk();
        $this->actingAs($deOutra)->get("/arquivos-osc/{$arquivo->id}")->assertForbidden();
        $this->actingAs($this->ug)->get("/oscs/{$this->osc->id}/arquivos")->assertOk()->assertSee('estatuto.pdf');
    }

    public function test_certidao_perto_de_vencer_avisa_a_osc_uma_vez(): void
    {
        OscArquivo::forceCreate(['osc_id' => $this->osc->id, 'tipo' => 'cndt', 'versao' => 1, 'arquivo_path' => 'a', 'arquivo_nome' => 'a.pdf',
            'validade' => now()->addDays(3)->toDateString()]);

        $this->artisan('osc:avisar-vencimento-certidoes')->assertSuccessful();
        Mail::assertQueued(Aviso::class, fn ($m) => $m->hasTo($this->rl->email) && str_contains($m->assunto, 'Certidão vence'));

        Mail::fake();
        $this->artisan('osc:avisar-vencimento-certidoes')->assertSuccessful();
        Mail::assertNothingQueued();
    }

    public function test_estatuto_ata_e_certidoes_saem_dos_documentos_da_proposta(): void
    {
        $this->assertArrayNotHasKey('estatuto', Documento::tiposParaAnexar());
        $this->assertArrayNotHasKey('certidao', Documento::tiposParaAnexar());
        $this->assertArrayHasKey('experiencia_previa', Documento::tiposParaAnexar());
    }
}
