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
 * Etapa 4 do Planejamento (SEPLAN): o Parecer Técnico Contábil, que só o Contador assina, e o
 * Parecer Financeiro, que só o Responsável pela SEPLAN assina. O processo segue com os dois.
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
        $this->contador   = $this->daSeplan('contador');
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
        return $this->actingAs($quem)->patch("/processos/{$this->processo->id}/pecas/{$this->peca($tipo)->id}/assinar");
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
            ->assertOk()->assertSee('quem assina é o Contador');

        $this->assinar($this->contador, 'parecer_contabil')->assertRedirect()->assertSessionHasNoErrors();
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
}
