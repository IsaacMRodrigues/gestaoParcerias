<?php

namespace Tests\Feature;

use App\Models\Chamamento;
use App\Models\Orgao;
use App\Models\Peca;
use App\Models\Processo;
use App\Models\ProcessoPeca;
use App\Models\Programa;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Salvar ou assinar um documento não o fecha nem devolve o usuário ao fluxo (01/10/2026). */
class DocumentoContinuaAbertoTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_checklist_o_documento_volta_aberto(): void
    {
        $this->seed(RolesSeeder::class);
        Mail::fake();
        $orgao = Orgao::forceCreate(['name' => 'Educação']);
        $programa = Programa::forceCreate(['orgao_id' => $orgao->id, 'name' => 'P', 'tipo' => 'termo_fomento']);
        $chamamento = Chamamento::forceCreate(['programa_id' => $programa->id, 'numero' => '001/2026', 'titulo' => 'C',
            'objeto' => 'x', 'tipo' => 'chamamento_publico', 'status' => 'em_analise', 'selecao_etapa' => 0, 'selecao_setor' => 'ug']);
        Peca::sincronizar($chamamento, 'chamamento_publico');
        $ata = $chamamento->pecas()->where('chave', 'ata_comissao')->sole();
        $ug = User::factory()->create(['setor' => 'ug', 'orgao_id' => $orgao->id, 'status' => true, 'approval_status' => 'aprovado']);
        $ug->assignRole('responsavel_unidade_gestora');

        $url = "/chamamentos/{$chamamento->id}/selecao";
        $this->actingAs($ug)->from($url)->put("/pecas/{$ata->id}", ['conteudo' => '<p>Ata</p>'])
            ->assertRedirect($url . '#peca-' . $ata->id)->assertSessionHas('peca_aberta', $ata->id);

        $html = $this->actingAs($ug)->withSession(['peca_aberta' => $ata->id])->get($url)->assertOk()->getContent();
        $linha = substr($html, strpos($html, 'id="peca-' . $ata->id . '"'), 20000);
        $this->assertMatchesRegularExpression('/<details class="mt-2 group"\s+open\s*>/', $linha, 'o documento salvo volta aberto');
    }

    public function test_no_planejamento_salvar_e_assinar_ficam_no_documento(): void
    {
        $this->seed(RolesSeeder::class);
        $orgao = Orgao::forceCreate(['name' => 'Educação']);
        $ug = User::factory()->create(['setor' => 'ug', 'orgao_id' => $orgao->id, 'status' => true, 'approval_status' => 'aprovado']);
        $ug->assignRole('responsavel_unidade_gestora');
        $processo = Processo::forceCreate(['numero' => '0207.0001.2026.01', 'orgao_id' => $orgao->id, 'created_by' => $ug->id,
            'status' => 'em_tramite', 'setor_atual' => 'ug', 'etapa' => 0]);
        $memorando = ProcessoPeca::forceCreate(['processo_id' => $processo->id, 'tipo' => 'oficio', 'conteudo' => '<p>Memorando</p>']);

        $doc = route('processos.pecas.edit', [$processo, $memorando]);
        $this->actingAs($ug)->put("/processos/{$processo->id}/pecas/{$memorando->id}", ['conteudo' => '<p>Memorando revisto</p>'])
            ->assertRedirect($doc);
        $this->actingAs($ug)->patch("/processos/{$processo->id}/pecas/{$memorando->id}/assinar")->assertRedirect($doc);
        $this->actingAs($ug)->get($doc)->assertOk();
    }

    public function test_edital_mostra_se_foi_salvo_e_que_a_vez_e_de_encaminhar_a_ug(): void
    {
        $this->seed(RolesSeeder::class);
        $orgao = Orgao::forceCreate(['name' => 'Cultura']);
        $ug = User::factory()->create(['setor' => 'ug', 'orgao_id' => $orgao->id, 'status' => true, 'approval_status' => 'aprovado']);
        $scp = User::factory()->create(['setor' => 'scp', 'status' => true, 'approval_status' => 'aprovado']);
        $scp->assignRole('analista_tecnico_scp');
        $processo = Processo::forceCreate(['numero' => '0216.0001.2026.01', 'orgao_id' => $orgao->id, 'created_by' => $ug->id,
            'status' => 'em_tramite', 'setor_atual' => 'scp', 'etapa' => 5, 'modalidade' => 'chamamento_publico']);
        // Nasce com o texto-modelo, como na abertura do processo: isso não é "preenchido".
        $edital = ProcessoPeca::forceCreate(['processo_id' => $processo->id, 'tipo' => 'edital',
            'conteudo' => ProcessoPeca::conteudoInicial('edital', $processo)]);
        $doc = route('processos.pecas.edit', [$processo, $edital]);
        $tela = route('processos.show', $processo);

        $this->actingAs($scp)->get($doc)->assertOk()->assertDontSee('Preenchido e salvo');
        $this->actingAs($scp)->get($tela)->assertOk()->assertSee('Modelo — ainda não editado')
            ->assertSee('Falta antes de encaminhar')->assertSee('Edital (elaborar e salvar)')->assertDontSee('Tudo pronto nesta etapa');

        $this->actingAs($scp)->put($doc, ['conteudo' => '<p>Edital do chamamento</p>'])->assertRedirect($doc);
        $this->actingAs($scp)->get($doc)->assertOk()->assertSee('Preenchido e salvo')
            ->assertSee('Quem assina é a Unidade Gestora')->assertSee(route('processos.show', $processo) . '#acao', false);
        $html = $this->actingAs($scp)->get($tela)->assertOk()->assertSee('a Unidade Gestora assina na etapa seguinte')
            ->assertSee('Tudo pronto nesta etapa')->assertSee('encaminhe para Unidade Gestora')->assertDontSee('Falta antes de encaminhar')
            ->getContent();
        $this->assertMatchesRegularExpression('/>\s*Editar\s*<\/a>/', $html, 'o link do documento salvo vira "Editar"');
    }
}
