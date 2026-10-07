<?php

namespace Tests\Feature;

use App\Models\Chamamento;
use App\Models\Orgao;
use App\Models\Peca;
use App\Models\Programa;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * O chamamento é da Secretaria dele: a UG de outra Secretaria não abre a Seleção, não preenche nem
 * assina as peças e não encaminha, nem pelo endereço. A página pública segue aberta.
 */
class SelecaoDaSecretariaTest extends TestCase
{
    use RefreshDatabase;

    public function test_ug_de_outra_secretaria_nao_mexe_na_selecao(): void
    {
        $this->seed(RolesSeeder::class);
        Mail::fake();

        $educacao = Orgao::forceCreate(['name' => 'Educação']);
        $saude = Orgao::forceCreate(['name' => 'Saúde']);
        $chamamento = Chamamento::forceCreate(['programa_id' => Programa::doOrgao($educacao)->id, 'numero' => '001/2026',
            'titulo' => 'Oficinas', 'objeto' => 'x', 'tipo' => 'chamamento_publico', 'status' => 'publicado',
            'selecao_etapa' => 0, 'selecao_setor' => 'ug']);
        Peca::sincronizar($chamamento, 'chamamento_publico');
        $ata = $chamamento->pecas()->where('chave', 'ata_comissao')->sole();

        $servidor = function (Orgao $o) {
            $u = User::factory()->create(['setor' => 'ug', 'orgao_id' => $o->id, 'status' => true, 'approval_status' => 'aprovado']);
            $u->assignRole('responsavel_unidade_gestora');

            return $u;
        };
        $daEducacao = $servidor($educacao);
        $daSaude = $servidor($saude);

        $this->actingAs($daSaude)->get("/chamamentos/{$chamamento->id}/selecao")->assertForbidden();
        $this->actingAs($daSaude)->put("/pecas/{$ata->id}", ['conteudo' => '<p>intrusa</p>'])->assertForbidden();
        $this->actingAs($daSaude)->post("/chamamentos/{$chamamento->id}/selecao/avancar")->assertForbidden();
        $this->assertNotSame('<p>intrusa</p>', $ata->fresh()->conteudo);

        $this->actingAs($daEducacao)->get("/chamamentos/{$chamamento->id}/selecao")->assertOk();
        $this->actingAs($daEducacao)->put("/pecas/{$ata->id}", ['conteudo' => '<p>Ata</p>'])->assertSessionHasNoErrors();
        $this->assertSame('<p>Ata</p>', $ata->fresh()->conteudo);

        $this->get("/portal/chamamentos/{$chamamento->id}")->assertOk();
    }
}
