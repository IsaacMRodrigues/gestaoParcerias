<?php

namespace Tests\Feature;

use App\Models\Chamamento;
use App\Models\Orgao;
use App\Models\Osc;
use App\Models\Peca;
use App\Models\Programa;
use App\Models\Proposta;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Um chamamento pode ter várias propostas vencedoras, e todas seguem para a
 * Celebração (pedido da gestão, 29/09/2026).
 */
class VariasVencedorasTest extends TestCase
{
    use RefreshDatabase;

    public function test_as_vencedoras_aparecem_todas_na_celebracao(): void
    {
        $this->seed(RolesSeeder::class);
        Mail::fake();

        $orgao = Orgao::forceCreate(['name' => 'Educação']);
        $programa = Programa::forceCreate(['orgao_id' => $orgao->id, 'name' => 'P', 'tipo' => 'termo_fomento']);
        $chamamento = Chamamento::forceCreate(['programa_id' => $programa->id, 'numero' => '001/2026', 'titulo' => 'Oficinas',
            'objeto' => 'x', 'tipo' => 'chamamento_publico', 'status' => 'em_analise', 'selecao_etapa' => 5, 'selecao_setor' => 'pm']);
        Peca::sincronizar($chamamento, 'chamamento_publico');
        $chamamento->pecas()->where('chave', 'termo_homologacao')->update(['assinado_em' => now(), 'conteudo' => '<p>ok</p>']);

        $propostas = collect(['Alfa', 'Beta', 'Gama'])->map(function ($nome, $i) use ($chamamento) {
            $osc = Osc::forceCreate(['name' => "OSC {$nome}", 'cnpj' => "11.111.111/000{$i}-11", 'email' => "{$i}@example.com"]);

            return Proposta::forceCreate(['chamamento_id' => $chamamento->id, 'osc_id' => $osc->id, 'titulo' => "Projeto {$nome}",
                'objeto' => 'x', 'status' => 'em_analise', 'valor_solicitado' => 1]);
        });

        $prefeito = User::factory()->create(['setor' => 'pm', 'status' => true, 'approval_status' => 'aprovado']);
        $prefeito->assignRole('prefeito_municipal');
        $this->actingAs($prefeito)->post("/chamamentos/{$chamamento->id}/selecao/concluir", [
            'vencedoras' => [$propostas[0]->id, $propostas[1]->id],
        ])->assertSessionHasNoErrors();

        $this->assertSame(['aprovada', 'aprovada', 'reprovada'], $propostas->map(fn ($p) => $p->fresh()->status)->all());

        $ug = User::factory()->create(['setor' => 'ug', 'orgao_id' => $orgao->id, 'status' => true, 'approval_status' => 'aprovado']);
        $ug->assignRole('responsavel_unidade_gestora');
        $this->actingAs($ug)->get('/celebracao')->assertOk()
            ->assertSee('Chamamento 001/2026 — Oficinas')->assertSee('2 parcerias vencedoras')
            ->assertSee('Projeto Alfa')->assertSee('Projeto Beta')->assertDontSee('Projeto Gama');

        // Cada vencedora tem a sua Celebração, e aponta para as outras.
        $this->actingAs($ug)->get("/celebracao/{$propostas[0]->id}")->assertOk()
            ->assertSee('Este chamamento tem 2 parcerias vencedoras')
            ->assertSee(route('celebracao.show', $propostas[1]), false);
        $this->actingAs($ug)->get("/celebracao/{$propostas[1]->id}")->assertOk()
            ->assertSee(route('celebracao.show', $propostas[0]), false);

        // A OSC acompanha só a própria parceria.
        $rl = User::factory()->create(['osc_id' => $propostas[0]->osc_id, 'setor' => 'osc', 'status' => true, 'approval_status' => 'aprovado']);
        $rl->assignRole('responsavel_legal');
        $propostas[0]->osc->forceFill(['user_id' => $rl->id])->save();
        $this->actingAs($rl)->get("/celebracao/{$propostas[0]->id}")->assertOk()
            ->assertDontSee('parcerias vencedoras')->assertDontSee('Projeto Beta');
    }
}
