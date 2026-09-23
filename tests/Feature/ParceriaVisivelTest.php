<?php

namespace Tests\Feature;

use App\Models\Chamamento;
use App\Models\Instrumento;
use App\Models\Orgao;
use App\Models\Osc;
use App\Models\Programa;
use App\Models\Proposta;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Parceria alheia não abre pelo endereço.
 *
 * O recorte por Secretaria vivia só nas listagens; a tela de detalhe abria o
 * que a lista escondia, e a Celebração abria até para outra OSC. Ver o
 * middleware ParceriaVisivel e Proposta::visivelPara().
 */
class ParceriaVisivelTest extends TestCase
{
    use RefreshDatabase;

    private Proposta $proposta;
    private Instrumento $instrumento;
    private Orgao $educacao;
    private Orgao $administracao;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);

        $this->educacao      = Orgao::forceCreate(['name' => 'Educação', 'sigla' => 'EDU']);
        $this->administracao = Orgao::forceCreate(['name' => 'Administração', 'sigla' => 'ADM']);

        $programa = Programa::forceCreate([
            'orgao_id' => $this->educacao->id, 'name' => 'Programa', 'tipo' => 'termo_fomento',
        ]);
        $chamamento = Chamamento::forceCreate([
            'programa_id' => $programa->id, 'numero' => '001/2026', 'titulo' => 'Chamamento',
            'objeto' => 'Objeto', 'tipo' => array_key_first(Chamamento::TIPOS), 'status' => 'publicado',
        ]);
        $osc = Osc::forceCreate(['name' => 'OSC Dona', 'cnpj' => '11.111.111/0001-11', 'email' => 'dona@example.com']);

        $this->proposta = Proposta::forceCreate([
            'chamamento_id' => $chamamento->id, 'osc_id' => $osc->id, 'titulo' => 'Parceria',
            'objeto' => 'Objeto', 'status' => 'aprovada', 'valor_solicitado' => 1000,
        ]);
        $this->instrumento = Instrumento::forceCreate([
            'proposta_id' => $this->proposta->id, 'numero' => 'TF-1', 'tipo' => array_key_first(Instrumento::TIPOS),
            'objeto' => 'Objeto', 'valor_repasse' => 1000, 'data_inicio' => '2026-10-01', 'data_fim' => '2027-10-01',
            'status' => array_key_first(Instrumento::STATUS),
        ]);
    }

    private function servidor(Orgao $orgao, string $papel, string $setor = 'ug'): User
    {
        $u = User::factory()->create([
            'orgao_id' => $orgao->id, 'setor' => $setor, 'status' => true, 'approval_status' => 'aprovado',
        ]);
        $u->assignRole($papel);

        return $u->fresh();
    }

    private function integranteDeOsc(Osc $osc): User
    {
        $u = User::factory()->create([
            'osc_id' => $osc->id, 'setor' => 'osc', 'status' => true, 'approval_status' => 'aprovado',
        ]);
        $u->assignRole('membro_osc');
        $u->givePermissionTo(array_keys(User::FUNCOES_OSC));

        return $u->fresh();
    }

    public function test_servidor_de_outra_secretaria_nao_abre_a_parceria(): void
    {
        $intruso = $this->servidor($this->administracao, 'responsavel_unidade_gestora');

        foreach ([
            "/propostas/{$this->proposta->id}",
            "/celebracao/{$this->proposta->id}",
            "/instrumentos/{$this->instrumento->id}",
            "/instrumentos/{$this->instrumento->id}/execucao",
        ] as $url) {
            $this->actingAs($intruso)->get($url)->assertForbidden();
        }
    }

    public function test_a_secretaria_dona_continua_abrindo(): void
    {
        $dona = $this->servidor($this->educacao, 'responsavel_unidade_gestora');

        $this->actingAs($dona)->get("/propostas/{$this->proposta->id}")->assertOk();
        $this->actingAs($dona)->get("/instrumentos/{$this->instrumento->id}")->assertOk();
    }

    public function test_quem_atua_para_todo_o_municipio_abre_qualquer_secretaria(): void
    {
        $scp = $this->servidor($this->administracao, 'analista_tecnico_scp', 'scp');

        $this->assertTrue($this->proposta->visivelPara($scp));
    }

    public function test_outra_osc_nao_abre_a_celebracao_nem_a_proposta(): void
    {
        $outra   = Osc::forceCreate(['name' => 'OSC Alheia', 'cnpj' => '22.222.222/0001-22', 'email' => 'alheia@example.com']);
        $intruso = $this->integranteDeOsc($outra);

        $this->actingAs($intruso)->get("/celebracao/{$this->proposta->id}")->assertForbidden();
        $this->actingAs($intruso)->get("/portal/propostas/{$this->proposta->id}")->assertForbidden();
    }

    public function test_a_osc_dona_ve_a_propria_parceria(): void
    {
        $this->assertTrue($this->proposta->visivelPara($this->integranteDeOsc($this->proposta->osc)));
    }

    public function test_abrir_a_celebracao_barrada_nao_grava_nada(): void
    {
        $intruso = $this->servidor($this->administracao, 'responsavel_unidade_gestora');

        $this->actingAs($intruso)->get("/celebracao/{$this->proposta->id}")->assertForbidden();

        $this->assertNull($this->proposta->fresh()->celebracao_iniciada_em);
    }

    public function test_servidor_da_secretaria_fora_do_tramite_nao_abre_a_celebracao(): void
    {
        $contador = $this->servidor($this->educacao, 'contador', 'contabilidade');

        $this->actingAs($contador)->get("/celebracao/{$this->proposta->id}")->assertForbidden();
    }

    public function test_nao_se_cria_segundo_instrumento_nem_instrumento_de_proposta_nao_aprovada(): void
    {
        $admin = $this->servidor($this->educacao, 'administrador_setorial', 'ti');
        $dados = [
            'numero' => 'TF-2', 'tipo' => array_key_first(Instrumento::TIPOS), 'objeto' => 'x',
            'valor_repasse' => 1000, 'data_inicio' => '2026-10-01', 'data_fim' => '2027-10-01',
            'status' => array_key_first(Instrumento::STATUS),
        ];

        $this->actingAs($admin)->post("/propostas/{$this->proposta->id}/instrumentos", $dados)->assertForbidden();

        $rascunho = Proposta::forceCreate([
            'chamamento_id' => $this->proposta->chamamento_id, 'osc_id' => $this->proposta->osc_id,
            'titulo' => 'Rascunho', 'objeto' => 'x', 'status' => 'rascunho', 'valor_solicitado' => 1,
        ]);
        $this->actingAs($admin)->post("/propostas/{$rascunho->id}/instrumentos", $dados)->assertForbidden();

        $this->assertSame(1, Instrumento::count());
    }
}
