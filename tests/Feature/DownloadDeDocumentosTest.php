<?php

namespace Tests\Feature;

use App\Models\Chamamento;
use App\Models\Documento;
use App\Models\Orgao;
use App\Models\Osc;
use App\Models\Peca;
use App\Models\Programa;
use App\Models\Proposta;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Download em todos os fluxos: a SCP baixa todos os documentos; a UG, os da sua Secretaria que lê,
 * preenche ou assina; a OSC, os abertos a ela da própria parceria. Documento de texto sai em PDF.
 */
class DownloadDeDocumentosTest extends TestCase
{
    use RefreshDatabase;

    private Proposta $proposta;
    private Chamamento $chamamento;
    private Peca $termo;
    private Peca $interna;
    private Peca $publicacao;
    private User $scp;
    private User $ug;
    private User $ugDeOutra;
    private User $rl;
    private User $deOutraOsc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Mail::fake();
        Storage::fake('local');

        $educacao = Orgao::forceCreate(['name' => 'Educação']);
        $saude = Orgao::forceCreate(['name' => 'Saúde']);
        $this->chamamento = Chamamento::forceCreate(['programa_id' => Programa::doOrgao($educacao)->id, 'numero' => '001/2026',
            'titulo' => 'Oficinas', 'objeto' => 'x', 'tipo' => 'chamamento_publico', 'status' => 'publicado']);
        $osc = Osc::forceCreate(['name' => 'Associação Viver', 'cnpj' => '11.111.111/0001-11', 'email' => 'osc@example.com']);
        $this->rl = $this->usuario(['osc_id' => $osc->id, 'setor' => 'osc'], 'responsavel_legal');
        $osc->forceFill(['user_id' => $this->rl->id])->save();
        $outra = Osc::forceCreate(['name' => 'Outra', 'cnpj' => '22.222.222/0001-22', 'email' => 'o@example.com']);
        $this->deOutraOsc = $this->usuario(['osc_id' => $outra->id, 'setor' => 'osc'], 'responsavel_legal');
        $outra->forceFill(['user_id' => $this->deOutraOsc->id])->save();

        $this->proposta = Proposta::forceCreate(['chamamento_id' => $this->chamamento->id, 'osc_id' => $osc->id, 'titulo' => 'Oficinas de música',
            'objeto' => 'x', 'status' => 'aprovada', 'valor_solicitado' => 1, 'celebracao_iniciada_em' => now(),
            'celebracao_etapa' => 16, 'celebracao_setor' => 'scp']);
        Peca::sincronizar($this->proposta, 'celebracao');
        $this->termo = $this->proposta->pecas()->where('chave', 'termo')->sole();
        $this->termo->forceFill(['conteudo' => '<p>Termo de Fomento nº 1/2026</p>', 'assinado_em' => now(), 'assinante_nome' => 'Fulano',
            'codigo_validacao' => 'AAAA-BBBB-CC'])->save();
        $this->interna = $this->proposta->pecas()->where('chave', 'pedido_parecer')->sole();
        $this->interna->forceFill(['conteudo' => '<p>Pedido de parecer</p>'])->save();
        Storage::disk('local')->put('pecas/pub.pdf', '%PDF-1.4 publicacao');
        $this->publicacao = $this->proposta->pecas()->where('chave', 'comprovante_publicacao_doe')->sole();
        $this->publicacao->forceFill(['arquivo_path' => 'pecas/pub.pdf', 'arquivo_nome' => 'doe.pdf'])->save();

        $this->scp = $this->usuario(['setor' => 'scp'], 'analista_tecnico_scp');
        $this->ug = $this->usuario(['setor' => 'ug', 'orgao_id' => $educacao->id], 'responsavel_unidade_gestora');
        $this->ugDeOutra = $this->usuario(['setor' => 'ug', 'orgao_id' => $saude->id], 'responsavel_unidade_gestora');
    }

    private function usuario(array $dados, string $papel): User
    {
        $u = User::factory()->create($dados + ['status' => true, 'approval_status' => 'aprovado']);
        $u->assignRole($papel);

        return $u;
    }

    public function test_o_documento_de_texto_sai_em_pdf(): void
    {
        $this->actingAs($this->scp)->get("/pecas/{$this->termo->id}/pdf")->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="termo-de-parceria.pdf"');
    }

    public function test_a_scp_baixa_todos_os_documentos(): void
    {
        foreach ([$this->termo, $this->interna] as $peca) {
            $this->actingAs($this->scp)->get("/pecas/{$peca->id}/pdf")->assertOk();
        }
        $this->actingAs($this->scp)->get("/pecas/{$this->publicacao->id}/arquivo")->assertOk();

        // Os anexos da proposta, que pediam a permissão de propostas, também.
        Storage::disk('local')->put('docs/estatuto.pdf', '%PDF');
        $doc = Documento::forceCreate(['proposta_id' => $this->proposta->id, 'nome_original' => 'estatuto.pdf', 'path' => 'docs/estatuto.pdf',
            'mime_type' => 'application/pdf', 'tamanho' => 4, 'uploaded_by' => $this->rl->id]);
        $this->actingAs($this->scp)->get("/documentos/{$doc->id}/download")->assertOk();
    }

    public function test_a_ug_baixa_os_da_sua_secretaria_e_nao_os_de_outra(): void
    {
        $this->actingAs($this->ug)->get("/pecas/{$this->termo->id}/pdf")->assertOk();
        $this->actingAs($this->ug)->get("/pecas/{$this->publicacao->id}/arquivo")->assertOk();

        $this->actingAs($this->ugDeOutra)->get("/pecas/{$this->termo->id}/pdf")->assertForbidden();

        // Peça da Seleção pertence ao chamamento: o recorte é a Secretaria dele.
        Peca::sincronizar($this->chamamento, 'chamamento_publico');
        $ata = $this->chamamento->pecas()->where('chave', 'ata_comissao')->sole();
        $ata->forceFill(['conteudo' => '<p>Ata</p>'])->save();
        $this->actingAs($this->ug)->get("/pecas/{$ata->id}/pdf")->assertOk();
        $this->actingAs($this->ugDeOutra)->get("/pecas/{$ata->id}/pdf")->assertForbidden();
    }

    public function test_a_osc_baixa_so_o_que_e_aberto_a_ela_da_propria_parceria(): void
    {
        $this->actingAs($this->rl)->get("/pecas/{$this->termo->id}/pdf")->assertOk();
        $this->actingAs($this->rl)->get("/pecas/{$this->publicacao->id}/arquivo")->assertOk();
        $this->actingAs($this->rl)->get("/pecas/{$this->interna->id}/pdf")->assertForbidden();
        $this->actingAs($this->deOutraOsc)->get("/pecas/{$this->termo->id}/pdf")->assertForbidden();
    }

    public function test_baixar_todos_traz_so_o_que_a_pessoa_pode(): void
    {
        $ids = ['pecas' => [$this->termo->id, $this->interna->id, $this->publicacao->id], 'nome' => 'Celebração Oficinas'];

        $resposta = $this->actingAs($this->rl)->get(route('pecas.lote', $ids))->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename=celebracao-oficinas.zip');
        $zip = new \ZipArchive();
        $zip->open($resposta->getFile()->getPathname());
        $nomes = collect(range(0, $zip->numFiles - 1))->map(fn ($i) => $zip->getNameIndex($i))->all();
        $this->assertCount(2, $nomes, 'a peça interna fica de fora para a OSC');
        $this->assertContains('02-doe.pdf', $nomes);

        $this->actingAs($this->deOutraOsc)->get(route('pecas.lote', $ids))->assertNotFound();
    }

    public function test_a_tela_oferece_baixar_pdf_e_baixar_todos(): void
    {
        $this->actingAs($this->scp)->get("/celebracao/{$this->proposta->id}")->assertOk()
            ->assertSee('Baixar todos (ZIP)')->assertSee(route('pecas.pdf', $this->termo), false)
            ->assertSee(route('pecas.pdf', $this->interna), false);

        $this->actingAs($this->rl)->get("/celebracao/{$this->proposta->id}")->assertOk()
            ->assertSee(route('pecas.pdf', $this->termo), false)
            ->assertDontSee(route('pecas.pdf', $this->interna), false);
    }
}
