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
 * A Ordem de Pagamento Global com duas assinaturas, nesta ordem: o Gestor da
 * Parceria e o Responsável da UG (decisão da gestão, 01/10/2026).
 */
class OpGlobalDuasAssinaturasTest extends TestCase
{
    use RefreshDatabase;

    public function test_gestor_e_depois_o_responsavel_da_ug(): void
    {
        $this->seed(RolesSeeder::class);
        Mail::fake();

        $orgao = Orgao::forceCreate(['name' => 'Educação']);
        $programa = Programa::forceCreate(['orgao_id' => $orgao->id, 'name' => 'P', 'tipo' => 'termo_fomento']);
        $chamamento = Chamamento::forceCreate(['programa_id' => $programa->id, 'numero' => '001/2026', 'titulo' => 'C',
            'objeto' => 'x', 'tipo' => 'chamamento_publico', 'status' => 'publicado']);
        $osc = Osc::forceCreate(['name' => 'OSC', 'cnpj' => '11.111.111/0001-11', 'email' => 'osc@example.com']);

        $servidor = function (string $setor, string $papel, ?Orgao $o = null) {
            $u = User::factory()->create(['setor' => $setor, 'orgao_id' => $o?->id, 'status' => true, 'approval_status' => 'aprovado']);
            $u->assignRole($papel);

            return $u;
        };
        $scp = $servidor('scp', 'analista_tecnico_scp');
        $responsavel = $servidor('ug', 'responsavel_unidade_gestora', $orgao);
        $gestor = $servidor('ug', 'gestor_parceria', $orgao);

        // A SCP elabora a OP (etapa 19); o Gestor já foi escolhido no Termo.
        $proposta = Proposta::forceCreate(['chamamento_id' => $chamamento->id, 'osc_id' => $osc->id, 'titulo' => 'P', 'objeto' => 'x',
            'status' => 'aprovada', 'valor_solicitado' => 1, 'celebracao_iniciada_em' => now(),
            'celebracao_etapa' => 18, 'celebracao_setor' => 'scp', 'celebracao_gestor_id' => $gestor->id]);
        Peca::sincronizar($proposta, 'celebracao');
        $op = $proposta->pecas()->where('chave', 'op_global')->sole();
        $op->forceFill(['conteudo' => '<p>OP Global</p>'])->save();

        $this->assertFalse($op->podeAssinar($scp), 'a SCP só elabora');
        $this->actingAs($scp)->post("/celebracao/{$proposta->id}/avancar")->assertSessionHasNoErrors();
        $proposta = $proposta->fresh();
        $this->assertSame([19, 'gestor', $gestor->id], [(int) $proposta->celebracao_etapa, $proposta->celebracao_setor, (int) $proposta->celebracao_gestor_id],
            'o Gestor do Termo vale para a OP');

        // O Responsável da UG não passa na frente do Gestor.
        $this->actingAs($responsavel)->patch("/pecas/{$op->id}/assinar-parte")->assertForbidden();
        $this->actingAs($gestor)->patch("/pecas/{$op->id}/assinar-parte")->assertSessionHasNoErrors();
        $this->actingAs($gestor)->post("/celebracao/{$proposta->id}/avancar")->assertSessionHasNoErrors();
        $this->assertSame(20, (int) $proposta->fresh()->celebracao_etapa);

        $this->actingAs($responsavel)->post("/celebracao/{$proposta->id}/avancar")->assertStatus(422);
        $this->actingAs($responsavel)->patch("/pecas/{$op->id}/assinar-parte")->assertSessionHasNoErrors();
        $this->assertTrue($op->fresh()->assinado());
        $this->actingAs($responsavel)->post("/celebracao/{$proposta->id}/avancar")->assertSessionHasNoErrors();

        $proposta = $proposta->fresh();
        $this->assertSame([21, 'scp'], [(int) $proposta->celebracao_etapa, $proposta->celebracao_setor], 'segue para o empenho, a última');
        $this->assertTrue($proposta->ultimaEtapaCelebracao());
        $this->assertSame(['gestor', 'ug'], $op->fresh()->assinaturasPartes->pluck('papel')->all());
    }
}
