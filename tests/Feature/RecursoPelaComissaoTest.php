<?php

namespace Tests\Feature;

use App\Mail\Aviso;
use App\Models\Chamamento;
use App\Models\Orgao;
use App\Models\Osc;
use App\Models\Peca;
use App\Models\Programa;
use App\Models\Proposta;
use App\Models\Recurso;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Etapa 3 da Seleção, "Recurso e resposta ao recurso" (decisão da gestão,
 * 29/09/2026): o recurso é o arquivo que a OSC anexa, e aparece para a
 * Comissão de Seleção da Secretaria; a resposta é a peça "Resposta ao
 * recurso", opcional, preenchida e assinada pela Comissão. Na etapa 4, a UG
 * anexa a Ata do resultado definitivo.
 */
class RecursoPelaComissaoTest extends TestCase
{
    use RefreshDatabase;

    private Orgao $orgao;
    private Chamamento $chamamento;
    private Proposta $proposta;
    private User $ug;
    private User $comissao;
    private User $rl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Mail::fake();
        Storage::fake('local');

        $this->orgao = Orgao::forceCreate(['name' => 'Educação']);
        $programa = Programa::forceCreate(['orgao_id' => $this->orgao->id, 'name' => 'P', 'tipo' => 'termo_fomento']);
        $this->chamamento = Chamamento::forceCreate(['programa_id' => $programa->id, 'numero' => '001/2026', 'titulo' => 'Oficinas',
            'objeto' => 'x', 'tipo' => 'chamamento_publico', 'status' => 'em_analise',
            'selecao_etapa' => Chamamento::ETAPA_PRAZO_RECURSO, 'selecao_setor' => 'ug', 'prazo_recurso_ate' => now()->addDays(3)]);

        $this->ug = $this->servidor('responsavel_unidade_gestora', $this->orgao);
        $this->comissao = $this->servidor('comissao_selecao', $this->orgao);

        $osc = Osc::forceCreate(['name' => 'Associação Viver', 'cnpj' => '11.111.111/0001-11', 'email' => 'osc@example.com']);
        $this->rl = User::factory()->create(['osc_id' => $osc->id, 'setor' => 'osc', 'status' => true, 'approval_status' => 'aprovado']);
        $this->rl->assignRole('responsavel_legal');
        $osc->forceFill(['user_id' => $this->rl->id])->save();
        $this->proposta = Proposta::forceCreate(['chamamento_id' => $this->chamamento->id, 'osc_id' => $osc->id, 'titulo' => 'Oficinas de música',
            'objeto' => 'x', 'status' => 'em_analise', 'valor_solicitado' => 1]);

        Peca::sincronizar($this->chamamento, 'chamamento_publico');
    }

    private function servidor(string $papel, Orgao $orgao): User
    {
        $u = User::factory()->create(['setor' => 'ug', 'orgao_id' => $orgao->id, 'status' => true, 'approval_status' => 'aprovado']);
        $u->assignRole($papel);

        return $u;
    }

    private function recorrer(): Recurso
    {
        $this->actingAs($this->rl)->post("/portal/chamamentos/{$this->chamamento->id}/recurso", [
            'arquivo' => UploadedFile::fake()->create('recurso.pdf', 50, 'application/pdf'),
        ])->assertSessionHas('success');

        return Recurso::sole();
    }

    private function peca(string $chave): Peca
    {
        return $this->chamamento->pecas()->where('chave', $chave)->sole();
    }

    public function test_o_arquivo_do_recurso_aparece_para_a_comissao_da_secretaria(): void
    {
        $recurso = $this->recorrer();

        Mail::assertQueued(Aviso::class, fn ($m) => $m->hasTo($this->comissao->email) && str_contains($m->assunto, 'Recurso protocolado'));
        $this->actingAs($this->comissao)->get("/propostas/{$this->proposta->id}")->assertOk()
            ->assertSee('Baixar o recurso da OSC (PDF)')->assertDontSee('Julgar recurso');
        $this->actingAs($this->comissao)->get("/recursos/{$recurso->id}/arquivo")->assertOk()->assertDownload('recurso.pdf');
        $this->actingAs($this->comissao)->get('/propostas')->assertOk()->assertSee('recurso');

        $outra = $this->servidor('comissao_selecao', Orgao::forceCreate(['name' => 'Saúde']));
        $this->actingAs($outra)->get("/recursos/{$recurso->id}/arquivo")->assertForbidden();
        $this->actingAs($outra)->get("/chamamentos/{$this->chamamento->id}/selecao")->assertForbidden();
    }

    public function test_a_resposta_ao_recurso_e_da_comissao_e_opcional(): void
    {
        $this->recorrer();
        $resposta = $this->peca('resposta_recurso');
        $this->assertFalse($resposta->obrigatorio);

        // A Comissão abre a Seleção só para a peça: sem os botões do trâmite.
        $this->actingAs($this->comissao)->get("/chamamentos/{$this->chamamento->id}/selecao")->assertOk()
            ->assertSee('Resposta ao recurso')->assertDontSee('Encerrar o prazo de recurso');

        $this->assertTrue($resposta->podePreencher($this->comissao));
        $this->assertFalse($resposta->podePreencher($this->ug), 'o resto da UG não responde pela Comissão');
        $this->actingAs($this->ug)->put("/pecas/{$resposta->id}", ['conteudo' => '<p>x</p>'])->assertForbidden();

        $this->actingAs($this->comissao)->put("/pecas/{$resposta->id}", ['conteudo' => '<p>Improvido.</p>'])->assertSessionHasNoErrors();
        $this->actingAs($this->ug)->patch("/pecas/{$resposta->id}/assinar")->assertForbidden();
        $this->actingAs($this->comissao)->patch("/pecas/{$resposta->id}/assinar")->assertSessionHasNoErrors();
        $this->assertTrue($resposta->fresh()->assinado());
    }

    public function test_sem_resposta_a_etapa_se_encerra_depois_do_prazo(): void
    {
        $this->recorrer();
        $this->travel(4)->days();

        $this->actingAs($this->comissao)->post("/chamamentos/{$this->chamamento->id}/selecao/avancar")->assertForbidden();
        $this->actingAs($this->ug)->post("/chamamentos/{$this->chamamento->id}/selecao/avancar")->assertSessionHasNoErrors();
        $this->assertSame(Chamamento::ETAPA_PRAZO_RECURSO + 1, (int) $this->chamamento->fresh()->selecao_etapa);
    }

    public function test_na_etapa_4_a_ug_anexa_a_ata_do_resultado_definitivo(): void
    {
        $this->chamamento->forceFill(['selecao_etapa' => Chamamento::ETAPA_PRAZO_RECURSO + 1])->save();
        $ata = $this->peca('ata_resultado_definitivo');

        $this->assertSame('arquivo', $ata->tipo);
        $this->actingAs($this->ug)->post("/pecas/{$ata->id}/arquivo", [
            'arquivo' => UploadedFile::fake()->create('ata.pdf', 50, 'application/pdf'),
        ])->assertSessionHasNoErrors();
        $this->assertTrue($ata->fresh()->temArquivo());

        $this->actingAs($this->ug)->get("/chamamentos/{$this->chamamento->id}/selecao")->assertOk()
            ->assertSee('Ata do resultado definitivo');
    }
}
