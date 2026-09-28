<?php

namespace Tests\Feature;

use App\Models\Chamamento;
use App\Models\Orgao;
use App\Models\Processo;
use App\Models\ProcessoPeca;
use App\Models\ProcessoPecaAnexo;
use App\Models\Programa;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Página pública do chamamento (decisão da gestão, 28/09/2026): só o edital e
 * os anexos dele; na dispensa e na inexigibilidade, só a justificativa. O
 * Parecer CNAS e as demais peças do Planejamento não aparecem.
 */
class ConsultaPublicaDoChamamentoTest extends TestCase
{
    use RefreshDatabase;

    private Orgao $orgao;
    private User $ug;
    private Programa $programa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Mail::fake();
        Storage::fake('local');

        $this->orgao = Orgao::forceCreate(['name' => 'Educação']);
        $this->ug = User::factory()->create(['setor' => 'ug', 'orgao_id' => $this->orgao->id, 'status' => true, 'approval_status' => 'aprovado']);
        $this->ug->assignRole('responsavel_unidade_gestora');
        $this->programa = Programa::forceCreate(['orgao_id' => $this->orgao->id, 'name' => 'Programa', 'tipo' => 'termo_fomento']);
    }

    /** Chamamento com um processo e as peças pedidas, todas assinadas. */
    private function chamamento(string $tipo, array $pecas): Chamamento
    {
        static $n = 0;
        $n++;
        $processo = Processo::forceCreate(['numero' => "0207.000$n.2026.01", 'orgao_id' => $this->orgao->id, 'created_by' => $this->ug->id,
            'status' => 'concluido', 'setor_atual' => 'scp', 'etapa' => 9]);

        foreach ($pecas as $i => $peca) {
            ProcessoPeca::forceCreate(['processo_id' => $processo->id, 'tipo' => $peca, 'conteudo' => "<p>$peca</p>", 'visivel_osc' => true,
                'assinado_por' => $this->ug->id, 'assinado_em' => now(), 'assinante_nome' => 'UG', 'codigo_validacao' => "AAAA-$n$i-CC"]);
        }

        return Chamamento::forceCreate(['programa_id' => $this->programa->id, 'processo_id' => $processo->id, 'numero' => "00$n/2026",
            'titulo' => "Chamamento $n", 'objeto' => 'x', 'tipo' => $tipo, 'status' => 'publicado']);
    }

    private function anexo(ProcessoPeca $peca, string $nome): ProcessoPecaAnexo
    {
        Storage::disk('local')->put("anexos/$nome", 'conteúdo');

        return ProcessoPecaAnexo::forceCreate(['processo_peca_id' => $peca->id, 'arquivo_path' => "anexos/$nome", 'arquivo_nome' => $nome,
            'tamanho' => 8, 'mime_type' => 'application/pdf', 'enviado_por' => $this->ug->id]);
    }

    private function peca(Chamamento $c, string $tipo): ProcessoPeca
    {
        return $c->processo->pecas()->where('tipo', $tipo)->sole();
    }

    public function test_chamamento_publico_mostra_so_o_edital_e_os_anexos(): void
    {
        $c = $this->chamamento('chamamento_publico', ['edital', 'parecer_cnas', 'termo_referencia']);
        $anexo = $this->anexo($this->peca($c, 'edital'), 'modelo-de-plano.pdf');

        $this->get("/portal/chamamentos/{$c->id}")->assertOk()
            ->assertSee(ProcessoPeca::TIPOS['edital'] . ' (ler documento)')
            ->assertSee('Anexo — modelo-de-plano.pdf')
            ->assertSee(route('portal.edital.anexo', [$c, $anexo]), false)
            ->assertDontSee(ProcessoPeca::TIPOS['parecer_cnas'])
            ->assertDontSee(ProcessoPeca::TIPOS['termo_referencia'] . ' (ler documento)');
    }

    public function test_dispensa_mostra_so_a_justificativa(): void
    {
        $c = $this->chamamento('dispensa', ['justificativa_dispensa', 'parecer_cnas']);

        $this->get("/portal/chamamentos/{$c->id}")->assertOk()
            ->assertSee(ProcessoPeca::TIPOS['justificativa_dispensa'] . ' (ler documento)')
            ->assertDontSee(ProcessoPeca::TIPOS['parecer_cnas']);
    }

    public function test_o_anexo_do_edital_baixa_sem_login_e_so_pelo_proprio_chamamento(): void
    {
        $c = $this->chamamento('chamamento_publico', ['edital', 'termo_referencia']);
        $outro = $this->chamamento('chamamento_publico', ['edital']);
        $doEdital = $this->anexo($this->peca($c, 'edital'), 'anexo-i.pdf');
        $doTermo = $this->anexo($this->peca($c, 'termo_referencia'), 'interno.pdf');

        $this->get(route('portal.edital.anexo', [$c, $doEdital]))->assertOk()->assertDownload('anexo-i.pdf');

        $this->get(route('portal.edital.anexo', [$c, $doTermo]))->assertNotFound();
        $this->get(route('portal.edital.anexo', [$outro, $doEdital]))->assertNotFound();
    }

    public function test_anexo_de_edital_ainda_nao_assinado_nao_sai(): void
    {
        $c = $this->chamamento('chamamento_publico', ['edital']);
        $edital = $this->peca($c, 'edital');
        $anexo = $this->anexo($edital, 'rascunho.pdf');
        $edital->forceFill(['assinado_em' => null])->save();

        $this->get(route('portal.edital.anexo', [$c, $anexo]))->assertNotFound();
    }

    public function test_na_dispensa_o_anexo_de_edital_tambem_nao_sai(): void
    {
        $c = $this->chamamento('dispensa', ['edital']);
        $anexo = $this->anexo($this->peca($c, 'edital'), 'edital-antigo.pdf');

        $this->get(route('portal.edital.anexo', [$c, $anexo]))->assertNotFound();
    }
}
