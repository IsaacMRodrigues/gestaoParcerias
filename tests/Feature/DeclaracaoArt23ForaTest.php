<?php

namespace Tests\Feature;

use App\Models\Chamamento;
use App\Models\Orgao;
use App\Models\Osc;
use App\Models\Peca;
use App\Models\Programa;
use App\Models\Proposta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** A declaração do art. 23, XIV, saiu da Celebração (decisão da gestão, 30/09/2026). */
class DeclaracaoArt23ForaTest extends TestCase
{
    use RefreshDatabase;

    public function test_o_checklist_da_celebracao_nao_tem_mais_a_declaracao_do_art_23(): void
    {
        $orgao = Orgao::forceCreate(['name' => 'Educação']);
        $programa = Programa::forceCreate(['orgao_id' => $orgao->id, 'name' => 'P', 'tipo' => 'termo_fomento']);
        $chamamento = Chamamento::forceCreate(['programa_id' => $programa->id, 'numero' => '001/2026', 'titulo' => 'C',
            'objeto' => 'x', 'tipo' => 'chamamento_publico', 'status' => 'publicado']);
        $osc = Osc::forceCreate(['name' => 'OSC', 'cnpj' => '11.111.111/0001-11', 'email' => 'osc@example.com']);
        $proposta = Proposta::forceCreate(['chamamento_id' => $chamamento->id, 'osc_id' => $osc->id, 'titulo' => 'P',
            'objeto' => 'x', 'status' => 'aprovada', 'valor_solicitado' => 1]);

        Peca::sincronizar($proposta, 'celebracao');

        $chaves = $proposta->pecas()->pluck('chave');
        $this->assertNotContains('decl_art23', $chaves);
        $this->assertContains('decl_art7', $chaves, 'as demais declarações continuam');
        $this->assertNull(Peca::modeloTexto('celebracao', 'decl_art23'));
    }
}
