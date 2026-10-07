<?php

namespace Tests\Feature;

use App\Mail\Aviso;
use App\Models\Aditivo;
use App\Models\Alteracao;
use App\Models\Chamamento;
use App\Models\Instrumento;
use App\Models\Orgao;
use App\Models\Osc;
use App\Models\Peca;
use App\Models\Programa;
use App\Models\Proposta;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\PreencheArquivosDaOsc;
use Tests\TestCase;

/**
 * Os Arquivos da OSC nos fluxos: a recusa da UG trava a Celebração até a OSC enviar nova versão; a inscrição
 * no chamamento público exige a área; a Alteração e o Aditivo usam a área em vez de pedir os documentos de novo.
 */
class ArquivosDaOscNosFluxosTest extends TestCase
{
    use RefreshDatabase;
    use PreencheArquivosDaOsc;

    private Osc $osc;
    private User $rl;
    private User $ug;
    private Chamamento $chamamento;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Mail::fake();
        Storage::fake('local');

        $educacao = Orgao::forceCreate(['name' => 'Educação']);
        $this->chamamento = Chamamento::forceCreate(['programa_id' => Programa::doOrgao($educacao)->id, 'numero' => '001/2026',
            'titulo' => 'Oficinas', 'objeto' => 'x', 'tipo' => 'chamamento_publico', 'status' => 'em_inscricao',
            'data_inicio_inscricao' => now()->subDay(), 'data_fim_inscricao' => now()->addMonth()]);
        $this->osc = Osc::forceCreate(['name' => 'Associação Viver', 'cnpj' => '11.111.111/0001-11', 'email' => 'osc@example.com']);
        $this->rl = User::factory()->create(['osc_id' => $this->osc->id, 'setor' => 'osc', 'status' => true, 'approval_status' => 'aprovado']);
        $this->rl->assignRole('responsavel_legal');
        $this->osc->forceFill(['user_id' => $this->rl->id])->save();
        $this->ug = User::factory()->create(['setor' => 'ug', 'orgao_id' => $educacao->id, 'status' => true, 'approval_status' => 'aprovado']);
        $this->ug->assignRole('responsavel_unidade_gestora');
    }

    private function proposta(array $extra = []): Proposta
    {
        return Proposta::forceCreate($extra + ['chamamento_id' => $this->chamamento->id, 'osc_id' => $this->osc->id, 'titulo' => 'Oficinas de música',
            'objeto' => 'x', 'status' => 'aprovada', 'valor_solicitado' => 1000]);
    }

    public function test_a_recusa_da_ug_trava_a_celebracao_ate_a_nova_versao(): void
    {
        $this->preencherArquivosDaOsc($this->osc);
        $p = $this->proposta(['celebracao_iniciada_em' => now(), 'celebracao_etapa' => 2, 'celebracao_setor' => 'ug']);
        $estatuto = $this->osc->arquivosAtuais()['estatuto'];

        // A parte da UG pede cada arquivo analisado nesta parceria.
        $this->assertContains('Arquivos da OSC: analisar ' . \App\Models\OscArquivo::rotulo('estatuto'), $p->pendenciasCelebracao('ug'));
        $this->aprovarArquivosDaOsc($p);
        $this->assertSame([], $p->pendenciasDaAnaliseDosArquivos());

        // Recusa: a UG não conclui, a OSC é avisada e, na etapa dela, tem de enviar outra versão.
        $this->actingAs($this->ug)->post("/propostas/{$p->id}/arquivos-osc/{$estatuto->id}/analisar",
            ['situacao' => 'recusado', 'motivo' => 'Falta a última alteração'])->assertSessionHasNoErrors();
        $this->assertNotEmpty(array_filter($p->pendenciasDaAnaliseDosArquivos(), fn ($x) => str_contains($x, 'recusado')));
        Mail::assertQueued(Aviso::class, fn ($m) => $m->hasTo($this->rl->email));
        $this->assertNotEmpty(array_filter($this->osc->pendenciasDosArquivos($p), fn ($x) => str_contains($x, 'Falta a última alteração')));

        // Nova versão: a pendência da OSC some e a UG analisa a versão nova.
        $this->actingAs($this->rl)->post('/portal/arquivos/estatuto', ['arquivo' => UploadedFile::fake()->create('estatuto.pdf', 10, 'application/pdf')])
            ->assertSessionHasNoErrors();
        $this->assertSame([], $this->osc->fresh()->pendenciasDosArquivos($p));
        $this->assertContains('Arquivos da OSC: analisar ' . \App\Models\OscArquivo::rotulo('estatuto'), $p->fresh()->pendenciasDaAnaliseDosArquivos());
    }

    public function test_a_inscricao_no_chamamento_exige_a_area(): void
    {
        $p = $this->proposta(['status' => 'rascunho']);
        $p->planoItens()->create(['numero' => 1, 'descricao' => 'Instrutor', 'tipo_despesa' => 'servicos_pf', 'quantidade' => 1, 'valor_unitario' => 1000]);
        $meta = $p->criarMeta(['descricao' => 'Meta 1']);
        $p->desembolsos()->create(['meta_id' => $meta->id, 'parcela' => 1, 'valor' => 1000]);

        $this->actingAs($this->rl)->get("/portal/propostas/{$p->id}")->assertSee('Arquivos da OSC:');
        $this->actingAs($this->rl)->patch("/portal/propostas/{$p->id}/submeter")->assertSessionHasErrors('plano');
        $this->assertSame('rascunho', $p->fresh()->status);

        $this->preencherArquivosDaOsc($this->osc);
        $this->actingAs($this->rl)->patch("/portal/propostas/{$p->id}/submeter")->assertSessionHasNoErrors();
        $this->assertSame('submetida', $p->fresh()->status);
    }

    public function test_alteracao_e_aditivo_usam_a_area(): void
    {
        $p = $this->proposta();
        $instrumento = Instrumento::forceCreate(['proposta_id' => $p->id, 'numero' => '001/2026', 'tipo' => 'termo_fomento', 'objeto' => 'x',
            'valor_repasse' => 1000, 'data_inicio' => now(), 'data_fim' => now()->addYear(), 'status' => 'vigente']);

        $alteracao = Alteracao::forceCreate(['instrumento_id' => $instrumento->id, 'numero' => 1, 'titulo' => 'Remanejamento', 'descricao' => 'x', 'justificativa' => 'x',
            'status' => 'rascunho', 'setor_atual' => 'osc', 'etapa' => 0]);
        Peca::sincronizar($alteracao, 'alteracao');
        $chaves = $alteracao->pecas()->pluck('chave');
        $this->assertNotContains('certidoes', $chaves);
        $this->assertNotContains('decl_autenticidade', $chaves);
        $this->assertNotEmpty(array_filter($alteracao->fresh()->pendencias(), fn ($x) => str_starts_with($x, 'Arquivos da OSC:')));
        $this->preencherArquivosDaOsc($this->osc);
        $this->assertEmpty(array_filter($alteracao->fresh()->pendencias(), fn ($x) => str_starts_with($x, 'Arquivos da OSC:')));

        $aditivo = Aditivo::forceCreate(['instrumento_id' => $instrumento->id, 'numero' => 1, 'tipo' => 'aditivo', 'descricao' => 'x', 'status' => 'rascunho']);
        $this->actingAs($this->ug)->get("/instrumentos/{$instrumento->id}/aditivos/{$aditivo->id}/documentacao")->assertOk()
            ->assertSee('Arquivos da OSC — completos e em dia');
        $this->assertSame([], array_intersect(['certidoes_regularidade', 'ata_eleicao'], $aditivo->pecas()->pluck('chave')->all()));
    }
}
