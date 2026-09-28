<?php

namespace Tests\Feature;

use App\Mail\Aviso;
use App\Models\Chamamento;
use App\Models\Orgao;
use App\Models\Osc;
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
 * Prorrogação do prazo de inscrições do chamamento, pela SCP, com o aviso de
 * prorrogação e o comprovante de publicação (decisão da gestão, 28/09/2026).
 */
class ChamamentoProrrogacaoTest extends TestCase
{
    use RefreshDatabase;

    private Chamamento $chamamento;
    private User $scp;
    private User $rl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Mail::fake();
        Storage::fake('local');

        $orgao = Orgao::forceCreate(['name' => 'Educação']);
        $programa = Programa::forceCreate(['orgao_id' => $orgao->id, 'name' => 'P', 'tipo' => 'termo_fomento']);
        // Inscrições que já acabaram ontem.
        $this->chamamento = Chamamento::forceCreate(['programa_id' => $programa->id, 'numero' => '001/2026', 'titulo' => 'Oficinas',
            'objeto' => 'x', 'tipo' => 'chamamento_publico', 'status' => 'publicado', 'selecao_etapa' => 0, 'selecao_setor' => 'ug',
            'data_inicio_inscricao' => now()->subDays(20), 'data_fim_inscricao' => now()->subDay()]);

        $this->scp = User::factory()->create(['setor' => 'scp', 'status' => true, 'approval_status' => 'aprovado']);
        $this->scp->assignRole('analista_tecnico_scp');

        $osc = Osc::forceCreate(['name' => 'OSC', 'cnpj' => '11.111.111/0001-11', 'email' => 'osc@example.com']);
        $this->rl = User::factory()->create(['osc_id' => $osc->id, 'setor' => 'osc', 'status' => true, 'approval_status' => 'aprovado']);
        $this->rl->assignRole('responsavel_legal');
        $osc->forceFill(['user_id' => $this->rl->id])->save();
        Proposta::forceCreate(['chamamento_id' => $this->chamamento->id, 'osc_id' => $osc->id, 'titulo' => 'P', 'objeto' => 'x',
            'status' => 'rascunho', 'valor_solicitado' => 1]);
    }

    private function prorrogar(?User $quem = null, array $extra = [])
    {
        return $this->actingAs($quem ?? $this->scp)->post("/chamamentos/{$this->chamamento->id}/prorrogar", array_merge([
            'fim_novo'   => now()->addDays(15)->format('Y-m-d'),
            'aviso'      => UploadedFile::fake()->create('aviso.pdf', 50, 'application/pdf'),
            'publicacao' => UploadedFile::fake()->create('diario.pdf', 50, 'application/pdf'),
        ], $extra));
    }

    public function test_a_scp_prorroga_com_os_dois_anexos_e_as_inscricoes_reabrem(): void
    {
        $this->assertFalse($this->chamamento->aceitaPropostas(), 'antes: inscrições encerradas');

        $this->prorrogar()->assertSessionHasNoErrors();

        $c = $this->chamamento->fresh();
        $this->assertSame(now()->addDays(15)->format('Y-m-d'), $c->data_fim_inscricao->format('Y-m-d'));
        $this->assertTrue($c->aceitaPropostas(), 'as inscrições reabrem até a nova data');

        $p = $c->prorrogacoes()->sole();
        $this->assertSame(now()->subDay()->format('Y-m-d'), $p->fim_anterior->format('Y-m-d'));
        Storage::disk('local')->assertExists([$p->aviso_path, $p->publicacao_path]);
        Mail::assertQueued(Aviso::class, fn ($m) => $m->hasTo($this->rl->email) && str_contains($m->assunto, 'prorrogadas'));
    }

    public function test_os_dois_documentos_sao_obrigatorios_e_o_prazo_tem_de_avancar(): void
    {
        $this->prorrogar(extra: ['aviso' => null])->assertSessionHasErrors('aviso');
        $this->prorrogar(extra: ['publicacao' => null])->assertSessionHasErrors('publicacao');
        $this->prorrogar(extra: ['fim_novo' => now()->subDays(2)->format('Y-m-d')])->assertSessionHasErrors('fim_novo');

        $this->assertSame(0, $this->chamamento->prorrogacoes()->count());
    }

    public function test_so_a_scp_prorroga(): void
    {
        $ug = User::factory()->create(['setor' => 'ug', 'orgao_id' => $this->chamamento->programa->orgao_id, 'status' => true, 'approval_status' => 'aprovado']);
        $ug->assignRole('responsavel_unidade_gestora');

        $this->prorrogar($ug)->assertForbidden();
        $this->assertSame(0, $this->chamamento->prorrogacoes()->count());
    }

    public function test_depois_de_a_selecao_andar_ou_cancelado_nao_prorroga(): void
    {
        $this->chamamento->forceFill(['selecao_etapa' => 1])->save();
        $this->prorrogar()->assertSessionHasErrors('prorrogacao');

        $this->chamamento->forceFill(['selecao_etapa' => 0, 'status' => 'cancelado'])->save();
        $this->prorrogar()->assertSessionHasErrors('prorrogacao');

        $this->assertSame(0, $this->chamamento->prorrogacoes()->count());
    }

    public function test_os_documentos_sao_publicos_e_aparecem_na_pagina_do_chamamento(): void
    {
        $this->prorrogar();
        $p = $this->chamamento->prorrogacoes()->sole();
        auth()->logout();

        $this->get("/portal/prorrogacoes/{$p->id}/aviso")->assertOk();
        $this->get("/portal/prorrogacoes/{$p->id}/publicacao")->assertOk();
        $this->get("/portal/prorrogacoes/{$p->id}/outro")->assertNotFound();

        $this->get("/portal/chamamentos/{$this->chamamento->id}")
            ->assertOk()->assertSee('Prazo de inscrições prorrogado até ' . now()->addDays(15)->format('d/m/Y'));
    }
}
