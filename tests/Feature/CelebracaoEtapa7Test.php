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
 * Etapa 7 da Celebração: a Minuta do Termo e a Certidão de Autuação, junto
 * com o Protocolo na Unidade Jurídica, todos da SCP (pedido da gestão,
 * 01/10/2026).
 */
class CelebracaoEtapa7Test extends TestCase
{
    use RefreshDatabase;

    public function test_minuta_e_certidao_entram_na_etapa_7_com_o_protocolo(): void
    {
        $this->seed(RolesSeeder::class);
        Mail::fake();
        $orgao = Orgao::forceCreate(['name' => 'Educação']);
        $programa = Programa::forceCreate(['orgao_id' => $orgao->id, 'name' => 'P', 'tipo' => 'termo_fomento']);
        $chamamento = Chamamento::forceCreate(['programa_id' => $programa->id, 'numero' => '001/2026', 'titulo' => 'C',
            'objeto' => 'x', 'tipo' => 'chamamento_publico', 'status' => 'publicado']);
        $osc = Osc::forceCreate(['name' => 'OSC', 'cnpj' => '11.111.111/0001-11', 'email' => 'osc@example.com']);
        $proposta = Proposta::forceCreate(['chamamento_id' => $chamamento->id, 'osc_id' => $osc->id, 'titulo' => 'P', 'objeto' => 'x',
            'status' => 'aprovada', 'valor_solicitado' => 1, 'celebracao_iniciada_em' => now(), 'celebracao_etapa' => 6, 'celebracao_setor' => 'scp']);

        Peca::sincronizar($proposta, 'celebracao');
        $pecas = $proposta->pecas()->get()->keyBy('chave');

        // Na ordem: minuta, certidão (que cita a minuta) e protocolo.
        $this->assertLessThan($pecas['certidao_autuacao']->ordem, $pecas['minuta_termo']->ordem);
        $this->assertLessThan($pecas['protocolo_juridico']->ordem, $pecas['certidao_autuacao']->ordem);
        foreach (['minuta_termo', 'certidao_autuacao', 'protocolo_juridico'] as $chave) {
            $this->assertSame([6, 'scp'], [$pecas[$chave]->selecaoEtapa(), $pecas[$chave]->selecaoSetor()], $chave);
        }

        $this->assertStringContainsString('CERTIDÃO DE AUTUAÇÃO', $pecas['certidao_autuacao']->conteudo);
        $this->assertNotEmpty($pecas['minuta_termo']->conteudo, 'a minuta nasce com o texto do termo');
        $this->assertFalse($pecas['certidao_autuacao']->visivel_osc, 'a certidão é instrução interna');

        $pendencias = $proposta->fresh()->pendenciasCelebracao();
        $this->assertContains('Minuta do Termo (modelo padrão) (assinar)', $pendencias);
        $this->assertContains('Certidão de Autuação (modelo padrão) (assinar)', $pendencias);

        $scp = User::factory()->create(['setor' => 'scp', 'status' => true, 'approval_status' => 'aprovado']);
        $scp->assignRole('analista_tecnico_scp');
        $this->assertTrue($pecas['minuta_termo']->podePreencher($scp));
        $this->assertTrue($pecas['certidao_autuacao']->podeAssinar($scp));
    }
}
