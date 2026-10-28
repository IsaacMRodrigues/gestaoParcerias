<?php

namespace Tests\Feature;

use App\Models\Orgao;
use App\Models\Processo;
use App\Models\ProcessoPeca;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Etapa 4 do Planejamento (SEPLAN): o Parecer Técnico Contábil, que só o Contador preenche e assina, de onde
 * estiver lotado (a Fazenda), e o Parecer Financeiro, que só o Responsável pela SEPLAN assina. O processo segue
 * com os dois.
 */
class ParecerContabilNoPlanejamentoTest extends TestCase
{
    use RefreshDatabase;

    private User $contador;
    private User $secretario;
    private Processo $processo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Mail::fake();

        $orgao = Orgao::forceCreate(['name' => 'Educação']);
        $fazenda = Orgao::forceCreate(['name' => 'Fazenda']);
        $this->contador = User::factory()->create(['setor' => 'ug', 'orgao_id' => $fazenda->id, 'status' => true, 'approval_status' => 'aprovado']);
        $this->contador->assignRole('contador');
        $this->secretario = $this->daSeplan('responsavel_seplan');

        $this->processo = Processo::forceCreate(['numero' => '0207.0001.2026.01', 'orgao_id' => $orgao->id,
            'status' => 'em_tramite', 'setor_atual' => 'seplan', 'etapa' => ProcessoPeca::ETAPA['parecer_contabil']]);
        foreach (['parecer_contabil', 'parecer_financeiro'] as $tipo) {
            $this->processo->pecas()->create(['tipo' => $tipo, 'conteudo' => ProcessoPeca::conteudoInicial($tipo, $this->processo)]);
        }
    }

    private function daSeplan(string $papel): User
    {
        $u = User::factory()->create(['setor' => 'seplan', 'status' => true, 'approval_status' => 'aprovado']);
        $u->assignRole($papel);

        return $u->fresh();
    }

    private function peca(string $tipo): ProcessoPeca
    {
        return $this->processo->pecas()->where('tipo', $tipo)->sole();
    }

    private function assinar(User $quem, string $tipo)
    {
        return $this->actingAs($quem)->patch("/processos/{$this->processo->id}/pecas/{$this->peca($tipo)->id}/assinar", ['senha_assinatura' => 'password']);
    }

    public function test_os_dois_pareceres_estao_na_mesma_etapa(): void
    {
        $this->assertSame(ProcessoPeca::ETAPA['parecer_financeiro'], ProcessoPeca::ETAPA['parecer_contabil']);
        $this->assertSame('seplan', ProcessoPeca::SETOR_RESPONSAVEL['parecer_contabil']);
        $this->assertStringContainsString('PARECER TÉCNICO CONTÁBIL', $this->peca('parecer_contabil')->conteudo);
        $this->assertSame([
            'Parecer Técnico Contábil (assinatura do Contador)',
            'Parecer Financeiro (assinatura do Responsável pela SEPLAN)',
        ], $this->processo->fresh()->pendenciasParaAvancar());

        $this->actingAs($this->secretario)->get("/processos/{$this->processo->id}")->assertOk()
            ->assertSee('Parecer Técnico Contábil')->assertSee('assinado pelo Contador');
    }

    public function test_cada_um_assina_so_o_seu(): void
    {
        $this->assinar($this->secretario, 'parecer_contabil')->assertForbidden();
        $this->assinar($this->contador, 'parecer_financeiro')->assertForbidden();
        $this->actingAs($this->secretario)->get("/processos/{$this->processo->id}/pecas/{$this->peca('parecer_contabil')->id}")
            ->assertOk()->assertSee('preenchido e assinado pelo Contador');

        // Fora da SEPLAN e de outra Secretaria, o Contador acha o parecer na caixa, abre o processo, preenche e assina.
        $item = \App\Support\CaixaDeEntrada::para($this->contador)->itens->sole();
        $this->assertStringContainsString('Parecer Técnico Contábil', $item['titulo']);
        $this->actingAs($this->contador)->get("/processos/{$this->processo->id}")->assertOk();
        $this->actingAs($this->contador)->put("/processos/{$this->processo->id}/pecas/{$this->peca('parecer_contabil')->id}",
            ['conteudo' => '<p>Parecer contábil: dotação 02.01, ficha 123.</p>'])->assertSessionHasNoErrors();
        $this->assinar($this->contador, 'parecer_contabil')->assertRedirect()->assertSessionHasNoErrors();
        $this->assertTrue(\App\Support\CaixaDeEntrada::para($this->contador)->vazia());
        $this->assertSame(['Parecer Financeiro (assinatura do Responsável pela SEPLAN)'], $this->processo->fresh()->pendenciasParaAvancar());

        $this->assinar($this->secretario, 'parecer_financeiro')->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame([], $this->processo->fresh()->pendenciasParaAvancar());

        $this->actingAs($this->secretario)->post("/processos/{$this->processo->id}/avancar")->assertSessionHasNoErrors();
        $this->assertSame([4, 'ug'], [(int) $this->processo->fresh()->etapa, $this->processo->fresh()->setor_atual]);
    }

    public function test_processo_novo_ja_nasce_com_o_parecer_contabil(): void
    {
        $this->assertArrayHasKey('parecer_contabil', ProcessoPeca::TIPOS);
    }

    public function test_o_contador_nao_abre_processo_nem_mexe_no_resto(): void
    {
        $this->actingAs($this->contador)->get('/processos/create')->assertForbidden();
        $this->actingAs($this->contador)->get('/processos')->assertForbidden();
        $this->assertFalse($this->peca('parecer_financeiro')->podeEditarConteudo($this->processo, $this->contador));
        // A SEPLAN não preenche o parecer do Contador.
        $this->assertFalse($this->peca('parecer_contabil')->podeEditarConteudo($this->processo, $this->secretario));
    }
}
