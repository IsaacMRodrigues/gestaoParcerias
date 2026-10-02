<?php

namespace Tests\Feature;

use App\Models\Chamamento;
use App\Models\Orgao;
use App\Models\Programa;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * O cadastro do chamamento — criar, editar, remover — é só da SCP (decisão da
 * gestão, 29/09/2026). A UG continua na Seleção.
 */
class ChamamentoSoScpEditaTest extends TestCase
{
    use RefreshDatabase;

    private Programa $programa;
    private Chamamento $chamamento;
    private User $scp;
    private User $ug;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Mail::fake();

        $orgao = Orgao::forceCreate(['name' => 'Educação']);
        $this->programa = Programa::forceCreate(['orgao_id' => $orgao->id, 'name' => 'P', 'tipo' => 'termo_fomento']);
        $this->chamamento = Chamamento::forceCreate(['programa_id' => $this->programa->id, 'numero' => '001/2026', 'titulo' => 'Oficinas',
            'objeto' => 'x', 'tipo' => 'chamamento_publico', 'status' => 'publicado']);

        $this->scp = User::factory()->create(['setor' => 'scp', 'status' => true, 'approval_status' => 'aprovado']);
        $this->scp->assignRole('analista_tecnico_scp');
        $this->ug = User::factory()->create(['setor' => 'ug', 'orgao_id' => $orgao->id, 'status' => true, 'approval_status' => 'aprovado']);
        $this->ug->assignRole('responsavel_unidade_gestora');
    }

    private function dados(array $extra = []): array
    {
        return array_merge([
            'numero' => '001/2026', 'titulo' => 'Oficinas de música', 'objeto' => 'x',
            'tipo' => 'chamamento_publico', 'status' => 'publicado',
        ], $extra);
    }

    private function base(): string
    {
        return '/chamamentos';
    }

    public function test_a_ug_nao_cria_nao_edita_nem_remove(): void
    {
        $this->actingAs($this->ug)->get("{$this->base()}/create")->assertForbidden();
        $this->actingAs($this->ug)->post($this->base(), $this->dados(['numero' => '002/2026', 'orgao_id' => $this->programa->orgao_id]))->assertForbidden();
        $this->actingAs($this->ug)->get("{$this->base()}/{$this->chamamento->id}/edit")->assertForbidden();
        $this->actingAs($this->ug)->put("{$this->base()}/{$this->chamamento->id}", $this->dados())->assertForbidden();
        $this->actingAs($this->ug)->delete("{$this->base()}/{$this->chamamento->id}")->assertForbidden();

        $this->assertSame('Oficinas', $this->chamamento->fresh()->titulo);
        $this->assertSame(1, Chamamento::count());
    }

    public function test_a_ug_nao_ve_os_botoes_mas_segue_na_selecao(): void
    {
        $this->actingAs($this->ug)->get($this->base())->assertOk()
            ->assertDontSee('+ Novo Chamamento')
            ->assertDontSee(route('chamamentos.edit', $this->chamamento), false);

        $this->actingAs($this->ug)->get("/chamamentos/{$this->chamamento->id}/selecao")->assertOk()
            ->assertDontSee('Editar dados');
    }

    public function test_a_scp_edita(): void
    {
        $this->actingAs($this->scp)->get($this->base())->assertOk()
            ->assertSee('+ Novo Chamamento')
            ->assertSee(route('chamamentos.edit', $this->chamamento), false);
        $this->actingAs($this->scp)->get("{$this->base()}/{$this->chamamento->id}/edit")->assertOk();

        $this->actingAs($this->scp)->put("{$this->base()}/{$this->chamamento->id}", $this->dados())
            ->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame('Oficinas de música', $this->chamamento->fresh()->titulo);
    }
}
