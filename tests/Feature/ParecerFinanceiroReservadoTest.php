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
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * O Parecer Financeiro é elaborado por qualquer pessoa da SEPLAN, mas só o
 * Responsável pela SEPLAN o assina (decisão da gestão, 28/09/2026) — no
 * Planejamento (ProcessoPeca) e na Celebração/Aditivo (Peca).
 */
class ParecerFinanceiroReservadoTest extends TestCase
{
    use RefreshDatabase;

    private User $analista;
    private User $responsavel;
    private Orgao $orgao;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Mail::fake();

        $this->orgao = Orgao::forceCreate(['name' => 'Educação']);
        $this->analista    = $this->daSeplan('analista_orcamentario_financeiro');
        $this->responsavel = $this->daSeplan('responsavel_seplan');
    }

    private function daSeplan(string $papel): User
    {
        $u = User::factory()->create(['setor' => 'seplan', 'status' => true, 'approval_status' => 'aprovado']);
        $u->assignRole($papel);

        return $u->fresh();
    }

    // ── Planejamento ─────────────────────────────────────────────────────

    private function parecerDoPlanejamento(): array
    {
        $peca = new ProcessoPeca(['tipo' => 'parecer_financeiro']);
        $processo = Processo::forceCreate(['numero' => '0207.0001.2026.01', 'orgao_id' => $this->orgao->id,
            'created_by' => $this->analista->id, 'status' => 'em_tramite',
            'setor_atual' => $peca->setorAssinatura(), 'etapa' => $peca->etapaAssinatura()]);
        $peca = ProcessoPeca::forceCreate(['processo_id' => $processo->id, 'tipo' => 'parecer_financeiro', 'conteudo' => '<p>Parecer</p>']);

        return [$processo, $peca];
    }

    public function test_no_planejamento_a_analista_elabora_mas_nao_assina(): void
    {
        [$processo, $peca] = $this->parecerDoPlanejamento();

        $this->assertTrue($peca->podeEditarConteudo($processo, $this->analista), 'qualquer um da SEPLAN elabora');
        $this->assertFalse($peca->podeAssinar($processo, $this->analista));

        $this->actingAs($this->analista)->patch("/processos/{$processo->id}/pecas/{$peca->id}/assinar")->assertForbidden();
        $this->assertFalse($peca->fresh()->assinado());

        $this->actingAs($this->analista)->get("/processos/{$processo->id}/pecas/{$peca->id}")
            ->assertOk()->assertSee('quem assina é o Responsável pela SEPLAN');
    }

    public function test_no_planejamento_o_responsavel_assina(): void
    {
        [$processo, $peca] = $this->parecerDoPlanejamento();

        $this->assertTrue($peca->podeAssinar($processo, $this->responsavel));
        $this->actingAs($this->responsavel)->patch("/processos/{$processo->id}/pecas/{$peca->id}/assinar");
        $this->assertTrue($peca->fresh()->assinado());
    }

    // ── Celebração ───────────────────────────────────────────────────────

    public function test_na_celebracao_tambem_so_o_responsavel_assina(): void
    {
        $programa = Programa::forceCreate(['orgao_id' => $this->orgao->id, 'name' => 'P', 'tipo' => 'termo_fomento']);
        $chamamento = Chamamento::forceCreate(['programa_id' => $programa->id, 'titulo' => 'C', 'objeto' => 'x',
            'tipo' => 'chamamento_publico', 'status' => 'encerrado']);
        $osc = Osc::forceCreate(['name' => 'OSC', 'cnpj' => '11.111.111/0001-11', 'email' => 'osc@example.com']);
        $proposta = Proposta::forceCreate(['chamamento_id' => $chamamento->id, 'osc_id' => $osc->id, 'titulo' => 'P', 'objeto' => 'x',
            'status' => 'aprovada', 'valor_solicitado' => 1, 'celebracao_iniciada_em' => now(),
            'celebracao_setor' => 'seplan', 'celebracao_etapa' => Peca::CELEBRACAO_ETAPA['parecer_financeiro']]);
        $peca = Peca::forceCreate(['pecaable_type' => Proposta::class, 'pecaable_id' => $proposta->id, 'categoria' => 'celebracao',
            'chave' => 'parecer_financeiro', 'rotulo' => 'Parecer financeiro', 'tipo' => 'modelo', 'conteudo' => '<p>Parecer</p>']);

        $this->assertTrue($peca->podePreencher($this->analista), 'qualquer um da SEPLAN elabora');
        $this->assertFalse($peca->podeAssinar($this->analista));
        $this->assertTrue($peca->podeAssinar($this->responsavel));
    }

    public function test_o_perfil_novo_e_exclusivo_da_seplan(): void
    {
        $this->assertSame('seplan', User::PERFIS_EXCLUSIVOS['responsavel_seplan']);
        $this->assertSame('Responsável pela SEPLAN', User::$roleLabels['responsavel_seplan']);
    }
}
