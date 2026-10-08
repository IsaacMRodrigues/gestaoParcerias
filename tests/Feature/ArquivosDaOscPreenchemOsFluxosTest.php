<?php

namespace Tests\Feature;

use App\Models\Chamamento;
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
 * O que a OSC já tem em "Arquivos da OSC" preenche os fluxos: os complementares viram o arquivo dos
 * itens iguais da Celebração; a área completa atende a habilitação da dispensa, e a verificação dela
 * já vem com o quadro dos arquivos; os modelos saem com o cadastro da OSC.
 */
class ArquivosDaOscPreenchemOsFluxosTest extends TestCase
{
    use RefreshDatabase;
    use PreencheArquivosDaOsc;

    private Osc $osc;
    private User $rl;
    private User $ug;
    private Programa $programa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Mail::fake();
        Storage::fake('local');

        $educacao = Orgao::forceCreate(['name' => 'Educação']);
        $this->programa = Programa::doOrgao($educacao);
        $this->osc = Osc::forceCreate(['name' => 'Associação Viver', 'cnpj' => '11.111.111/0001-11', 'email' => 'osc@example.com',
            'resp_nome' => 'Maria da Silva', 'resp_cpf' => '123.456.789-00', 'logradouro' => 'Rua A', 'numero' => '10',
            'bairro' => 'Centro', 'cidade' => 'São Gonçalo do Rio Abaixo', 'estado' => 'MG']);
        $this->rl = User::factory()->create(['osc_id' => $this->osc->id, 'setor' => 'osc', 'status' => true, 'approval_status' => 'aprovado']);
        $this->rl->assignRole('responsavel_legal');
        $this->osc->forceFill(['user_id' => $this->rl->id])->save();
        $this->ug = User::factory()->create(['setor' => 'ug', 'orgao_id' => $educacao->id, 'status' => true, 'approval_status' => 'aprovado']);
        $this->ug->assignRole('responsavel_unidade_gestora');
    }

    private function arquivo(string $tipo, array $extra = []): OscArquivo
    {
        $versao = (int) OscArquivo::where('osc_id', $this->osc->id)->where('tipo', $tipo)->max('versao') + 1;
        $path = "osc-arquivos/{$this->osc->id}/{$tipo}-v{$versao}.pdf";
        Storage::disk('local')->put($path, "%PDF {$tipo} v{$versao}");

        return OscArquivo::forceCreate($extra + ['osc_id' => $this->osc->id, 'tipo' => $tipo, 'versao' => $versao,
            'arquivo_path' => $path, 'arquivo_nome' => "{$tipo}-v{$versao}.pdf", 'tamanho' => 10, 'mime_type' => 'application/pdf',
            'validade' => OscArquivo::exigeValidade($tipo) ? now()->addMonths(6)->toDateString() : null]);
    }

    private function celebracao(): Proposta
    {
        $chamamento = Chamamento::forceCreate(['programa_id' => $this->programa->id, 'numero' => '001/2026', 'titulo' => 'Oficinas',
            'objeto' => 'x', 'tipo' => 'chamamento_publico', 'status' => 'publicado']);

        return Proposta::forceCreate(['chamamento_id' => $chamamento->id, 'osc_id' => $this->osc->id, 'titulo' => 'Oficinas de música',
            'objeto' => 'x', 'status' => 'aprovada', 'valor_solicitado' => 1000, 'celebracao_iniciada_em' => now(),
            'celebracao_etapa' => 1, 'celebracao_setor' => 'osc']);
    }

    private function peca(Proposta $p, string $chave): Peca
    {
        return $p->pecas()->where('chave', $chave)->sole();
    }

    public function test_os_complementares_ja_preenchem_os_itens_da_celebracao(): void
    {
        foreach (array_keys(Peca::DA_AREA_DA_OSC) as $tipo) {
            $this->arquivo($tipo);
        }
        $p = $this->celebracao();

        $this->actingAs($this->rl)->get("/celebracao/{$p->id}")->assertOk()
            ->assertSee('Preenchido com "Arquivos da OSC"', false);

        foreach (array_keys(Peca::DA_AREA_DA_OSC) as $chave) {
            $peca = $this->peca($p, $chave);
            $this->assertTrue($peca->vemDaAreaDaOsc(), $chave);
            $this->assertTrue($peca->concluida(), $chave);
            $this->assertSame("%PDF {$chave} v1", Storage::disk('local')->get($peca->arquivo_path));
            $this->assertNotContains($peca->rotulo . ' (anexar arquivo)', $p->fresh()->pendenciasCelebracao('osc'));
        }

        // Continua no fluxo: dá para baixar e trocar por outro, mas não remover.
        $relacao = $this->peca($p, 'relacao_dirigentes');
        $this->actingAs($this->rl)->get("/pecas/{$relacao->id}/arquivo")->assertOk();
        $this->actingAs($this->rl)->delete(route('pecas.arquivo.remover', $relacao))->assertStatus(422);
    }

    public function test_o_item_acompanha_a_nova_versao_e_sai_com_a_recusa(): void
    {
        $this->arquivo('relacao_dirigentes');
        $p = $this->celebracao();
        Peca::sincronizar($p, 'celebracao');

        // Nova versão pela área: o item da Celebração em andamento já a recebe.
        $this->actingAs($this->rl)->post('/portal/arquivos/relacao_dirigentes',
            ['arquivo' => UploadedFile::fake()->create('dirigentes-2026.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
        $peca = $this->peca($p, 'relacao_dirigentes');
        $this->assertSame('dirigentes-2026.pdf', $peca->arquivo_nome);
        $this->assertSame(2, $peca->arquivoDaOsc->versao);

        // A UG recusa a versão nesta parceria: o item volta a pedir o arquivo.
        $this->actingAs($this->ug)->post("/propostas/{$p->id}/arquivos-osc/{$peca->osc_arquivo_id}/analisar",
            ['situacao' => 'recusado', 'motivo' => 'Sem os endereços'])->assertSessionHasNoErrors();
        $peca->refresh();
        $this->assertFalse($peca->vemDaAreaDaOsc());
        $this->assertFalse($peca->temArquivo());
        $this->assertContains($peca->rotulo . ' (anexar arquivo)', $p->fresh()->pendenciasCelebracao('osc'));
    }

    public function test_o_arquivo_enviado_no_item_vale_por_cima_e_a_celebracao_concluida_nao_muda(): void
    {
        $this->arquivo('experiencia_previa');
        $p = $this->celebracao();
        Peca::sincronizar($p, 'celebracao');
        $peca = $this->peca($p, 'experiencia_previa');

        $this->actingAs($this->rl)->post(route('pecas.upload', $peca), ['arquivo' => UploadedFile::fake()->create('atestados.pdf', 10)])
            ->assertSessionHasNoErrors();
        $this->arquivo('experiencia_previa');
        Peca::sincronizar($p->fresh(), 'celebracao');
        $this->assertSame('atestados.pdf', $peca->fresh()->arquivo_nome);
        $this->assertFalse($peca->fresh()->vemDaAreaDaOsc());

        $this->arquivo('balanco_patrimonial');
        $p->forceFill(['celebracao_concluida_em' => now()])->save();
        Peca::sincronizar($p->fresh(), 'celebracao');
        $this->assertFalse($this->peca($p, 'balanco_patrimonial')->temArquivo());
    }

    public function test_balanco_vencido_nao_preenche_e_complementar_nao_e_exigido_na_area(): void
    {
        $this->preencherArquivosDaOsc($this->osc);
        OscArquivo::where('osc_id', $this->osc->id)->whereIn('tipo', array_keys(OscArquivo::GRUPOS['complementares']['itens']))->delete();
        $this->assertSame([], $this->osc->fresh()->pendenciasDosArquivos());

        $this->arquivo('balanco_patrimonial', ['validade' => now()->subDay()->toDateString()]);
        $this->assertSame([], $this->osc->fresh()->pendenciasDosArquivos());

        $p = $this->celebracao();
        Peca::sincronizar($p, 'celebracao');
        $this->assertFalse($this->peca($p, 'balanco_patrimonial')->temArquivo());
    }

    public function test_a_area_completa_atende_a_habilitacao_da_dispensa_e_a_verificacao_vem_com_o_quadro(): void
    {
        $chamamento = Chamamento::forceCreate(['programa_id' => $this->programa->id, 'numero' => '002/2026', 'titulo' => 'Oficinas',
            'objeto' => 'x', 'tipo' => 'dispensa', 'status' => 'rascunho']);
        Proposta::forceCreate(['chamamento_id' => $chamamento->id, 'osc_id' => $this->osc->id, 'titulo' => 'Oficinas',
            'objeto' => 'x', 'status' => 'submetida', 'valor_solicitado' => 1000]);

        Peca::sincronizar($chamamento, 'dispensa_inexigibilidade');
        $habilitacao = $chamamento->pecas()->where('chave', 'docs_habilitacao')->sole();
        $verificacao = $chamamento->pecas()->where('chave', 'verificacao_habilitacao')->sole();
        $this->assertFalse($habilitacao->preenchido());
        $this->assertStringContainsString('Associação Viver', $verificacao->conteudo);
        $this->assertStringContainsString('Não anexado', $verificacao->conteudo);

        // A área se completa: a habilitação fica atendida e o quadro da verificação se refaz.
        $this->preencherArquivosDaOsc($this->osc);
        Peca::sincronizar($chamamento->fresh(), 'dispensa_inexigibilidade');
        $this->assertTrue($habilitacao->fresh()->preenchido());
        $this->assertStringNotContainsString('Não anexado', $verificacao->fresh()->conteudo);
        $this->assertStringContainsString('Versão 1, enviada em', $verificacao->fresh()->conteudo);

        $this->actingAs($this->ug)->get("/chamamentos/{$chamamento->id}/selecao")->assertOk()
            ->assertSee('Atendido por "Arquivos da OSC"', false);

        // Texto editado por alguém não é reescrito.
        $verificacao->update(['conteudo' => '<p>Conferido à mão.</p>']);
        $this->arquivo('estatuto');
        Peca::sincronizar($chamamento->fresh(), 'dispensa_inexigibilidade');
        $this->assertSame('<p>Conferido à mão.</p>', $verificacao->fresh()->conteudo);
    }

    public function test_os_modelos_saem_com_o_cadastro_da_osc(): void
    {
        $p = $this->celebracao();
        Peca::sincronizar($p, 'celebracao');

        foreach (['convocacao_osc', 'termo', 'parecer_scp', 'autorizacao_inicio', 'aprovacao_plano', 'parecer_tecnico'] as $chave) {
            $this->assertStringContainsString('Associação Viver', $this->peca($p, $chave)->conteudo, $chave);
        }
        $termo = $this->peca($p, 'termo')->conteudo;
        $this->assertStringContainsString('11.111.111/0001-11', $termo);
        $this->assertStringContainsString('Maria da Silva', $termo);
        $this->assertStringContainsString('123.456.789-00', $termo);
    }
}
