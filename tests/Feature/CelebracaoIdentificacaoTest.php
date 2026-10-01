<?php

namespace Tests\Feature;

use App\Models\Chamamento;
use App\Models\Orgao;
use App\Models\Osc;
use App\Models\Processo;
use App\Models\Programa;
use App\Models\Proposta;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Identificação da Celebração: o processo e o número do edital, no lugar do "Chamamento" (01/10/2026). */
class CelebracaoIdentificacaoTest extends TestCase
{
    use RefreshDatabase;

    private function celebracao(string $tipo, string $numero): array
    {
        $orgao = Orgao::forceCreate(['name' => 'Educação']);
        $ug = User::factory()->create(['setor' => 'ug', 'orgao_id' => $orgao->id, 'status' => true, 'approval_status' => 'aprovado']);
        $ug->assignRole('responsavel_unidade_gestora');
        $processo = Processo::forceCreate(['numero' => '0207.0001.2026.01', 'orgao_id' => $orgao->id, 'created_by' => $ug->id,
            'status' => 'concluido', 'setor_atual' => 'scp', 'etapa' => 9]);
        $programa = Programa::forceCreate(['orgao_id' => $orgao->id, 'name' => 'P', 'tipo' => 'termo_fomento']);
        $chamamento = Chamamento::forceCreate(['programa_id' => $programa->id, 'processo_id' => $processo->id, 'numero' => $numero,
            'titulo' => 'C', 'objeto' => 'x', 'tipo' => $tipo, 'status' => 'publicado']);
        $osc = Osc::forceCreate(['name' => 'OSC', 'cnpj' => '11.111.111/0001-11', 'email' => 'osc@example.com']);
        $proposta = Proposta::forceCreate(['chamamento_id' => $chamamento->id, 'osc_id' => $osc->id, 'titulo' => 'P', 'objeto' => 'x',
            'status' => 'aprovada', 'valor_solicitado' => 1, 'celebracao_iniciada_em' => now(), 'celebracao_etapa' => 0, 'celebracao_setor' => 'ug']);

        return [$ug, $proposta];
    }

    public function test_mostra_o_processo_e_o_numero_do_edital(): void
    {
        $this->seed(RolesSeeder::class);
        Mail::fake();
        [$ug, $proposta] = $this->celebracao('chamamento_publico', '005/2026');

        $this->actingAs($ug)->get("/celebracao/{$proposta->id}")->assertOk()
            ->assertSeeInOrder(['Processo', '0207.0001.2026.01', 'Edital nº', '005/2026'])
            ->assertDontSee('tracking-wide">Chamamento</dt>', false);
    }

    public function test_na_dispensa_o_numero_e_o_dela(): void
    {
        $this->seed(RolesSeeder::class);
        Mail::fake();
        [$ug, $proposta] = $this->celebracao('dispensa', '002/2026');

        $this->actingAs($ug)->get("/celebracao/{$proposta->id}")->assertOk()
            ->assertSee('Dispensa nº')->assertDontSee('Edital nº');
    }
}
