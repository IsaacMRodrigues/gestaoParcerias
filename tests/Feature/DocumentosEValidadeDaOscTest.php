<?php

namespace Tests\Feature;

use App\Mail\Aviso;
use App\Models\Osc;
use App\Models\OscArquivo;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\PreencheArquivosDaOsc;
use Tests\TestCase;

/**
 * Arquivos da OSC: os documentos II, IV e IX pedidos pela SCP são exigidos (RG/CPF/comprovante do representante
 * legal, comprovante de inscrição no CNPJ e relação nominal dos dirigentes), a existência mínima de três anos é
 * avisada pela data de abertura do CNPJ, e toda versão pode ter data de validade.
 */
class DocumentosEValidadeDaOscTest extends TestCase
{
    use RefreshDatabase;
    use PreencheArquivosDaOsc;

    private Osc $osc;
    private User $rl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Storage::fake('local');
        Mail::fake();
        $this->osc = Osc::forceCreate(['name' => 'Associação Viver', 'cnpj' => '11.111.111/0001-11', 'email' => 'osc@example.com',
            'data_abertura' => now()->subYears(5)]);
        $this->rl = User::factory()->create(['osc_id' => $this->osc->id, 'setor' => 'osc', 'status' => true, 'approval_status' => 'aprovado']);
        $this->rl->assignRole('responsavel_legal');
        $this->osc->forceFill(['user_id' => $this->rl->id])->save();
    }

    public function test_os_tres_documentos_da_scp_sao_exigidos(): void
    {
        $this->preencherArquivosDaOsc($this->osc);
        OscArquivo::where('osc_id', $this->osc->id)->whereIn('tipo', ['docs_presidente', 'cartao_cnpj', 'relacao_dirigentes'])->delete();

        $pendencias = implode(' | ', $this->osc->fresh()->pendenciasDosArquivos());
        $this->assertStringContainsString('Cópia simples do RG, CPF e comprovante de endereço do representante legal (não anexado)', $pendencias);
        $this->assertStringContainsString('Comprovante de inscrição no CNPJ', $pendencias);
        $this->assertStringContainsString('Relação nominal atualizada dos dirigentes, conforme o estatuto', $pendencias);
        $this->assertFalse(OscArquivo::ehComplementar('docs_presidente'));

        $this->actingAs($this->rl)->get('/portal/arquivos')->assertOk()->assertSee('Comprovante de inscrição no CNPJ');
    }

    public function test_a_existencia_minima_de_tres_anos_e_avisada_sem_travar(): void
    {
        $this->assertNull($this->osc->avisoDeExistencia());
        $this->actingAs($this->rl)->get('/portal/arquivos')->assertDontSee('o mínimo exigido é de 3 anos');

        $this->osc->forceFill(['data_abertura' => now()->subYears(2)])->save();
        $this->assertStringContainsString('2 ano(s)', $this->osc->fresh()->avisoDeExistencia());
        $this->actingAs($this->rl->fresh())->get('/portal/arquivos')->assertSee('o mínimo exigido é de 3 anos');

        // Só avisa: com a área completa, não há pendência.
        $this->preencherArquivosDaOsc($this->osc);
        $this->assertSame([], $this->osc->fresh()->pendenciasDosArquivos());
    }

    public function test_toda_versao_pode_ter_validade_e_vencida_vira_pendencia(): void
    {
        // Estatuto: validade opcional.
        $this->actingAs($this->rl)->post('/portal/arquivos/estatuto', ['arquivo' => UploadedFile::fake()->create('estatuto.pdf', 10, 'application/pdf')])
            ->assertSessionHasNoErrors();
        $this->actingAs($this->rl)->post('/portal/arquivos/ata_eleicao', ['arquivo' => UploadedFile::fake()->create('ata.pdf', 10, 'application/pdf'),
            'validade' => now()->addDays(3)->toDateString()])->assertSessionHasNoErrors();
        $this->assertNull(OscArquivo::where('tipo', 'estatuto')->sole()->validade);
        $this->actingAs($this->rl)->get('/portal/arquivos')->assertSee('Válido até (em branco se não vence)')->assertSee('sem data de validade');

        // Certidão segue exigindo a data.
        $this->actingAs($this->rl)->post('/portal/arquivos/cndt', ['arquivo' => UploadedFile::fake()->create('cndt.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('validade');

        // Perto de vencer, o aviso sai como "documento" (o comando roda sem ninguém logado); vencida, a ata vira pendência.
        auth()->guard()->logout();
        $this->artisan('osc:avisar-vencimento-certidoes')->assertSuccessful();
        Mail::assertQueued(Aviso::class, fn ($m) => $m->hasTo($this->rl->email) && str_starts_with($m->assunto, 'Documento vence em'));

        OscArquivo::where('tipo', 'ata_eleicao')->update(['validade' => now()->subDay()->toDateString()]);
        $this->assertNotEmpty(array_filter($this->osc->fresh()->pendenciasDosArquivos(), fn ($p) => str_contains($p, 'ata de eleição') && str_contains($p, 'vencida')));
    }
}
