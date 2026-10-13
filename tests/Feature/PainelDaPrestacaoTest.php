<?php

namespace Tests\Feature;

use App\Mail\Aviso;
use App\Models\Chamamento;
use App\Models\Despesa;
use App\Models\Instrumento;
use App\Models\Lancamento;
use App\Models\Orgao;
use App\Models\Osc;
use App\Models\PrestacaoContas;
use App\Models\Programa;
use App\Models\Proposta;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Painel da prestação de contas: a OSC lança ao longo da execução, a Prefeitura confere ou devolve, e a
 * prestação de um período nasce montada com o que foi lançado nele.
 */
class PainelDaPrestacaoTest extends TestCase
{
    use RefreshDatabase;

    private Instrumento $instrumento;
    private Proposta $proposta;
    private User $rl;
    private User $ug;
    private User $ugDeOutra;
    private User $deOutraOsc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Mail::fake();
        Storage::fake('local');

        $educacao = Orgao::forceCreate(['name' => 'Educação']);
        $saude = Orgao::forceCreate(['name' => 'Saúde']);
        $chamamento = Chamamento::forceCreate(['programa_id' => Programa::doOrgao($educacao)->id, 'numero' => '001/2026',
            'titulo' => 'Oficinas', 'objeto' => 'x', 'tipo' => 'chamamento_publico', 'status' => 'encerrado']);
        $osc = Osc::forceCreate(['name' => 'Associação Viver', 'cnpj' => '11.111.111/0001-11', 'email' => 'osc@example.com']);
        $this->rl = $this->usuario(['osc_id' => $osc->id, 'setor' => 'osc'], 'responsavel_legal');
        $osc->forceFill(['user_id' => $this->rl->id])->save();
        $outra = Osc::forceCreate(['name' => 'Outra', 'cnpj' => '22.222.222/0001-22', 'email' => 'o@example.com']);
        $this->deOutraOsc = $this->usuario(['osc_id' => $outra->id, 'setor' => 'osc'], 'responsavel_legal');
        $outra->forceFill(['user_id' => $this->deOutraOsc->id])->save();

        $this->proposta = Proposta::forceCreate(['chamamento_id' => $chamamento->id, 'osc_id' => $osc->id, 'titulo' => 'Oficinas de música',
            'objeto' => 'x', 'status' => 'aprovada', 'valor_solicitado' => 10000]);
        $this->proposta->criarMeta(['descricao' => 'Atender 40 crianças']);
        $this->instrumento = Instrumento::forceCreate(['proposta_id' => $this->proposta->id, 'numero' => '001/2026', 'tipo' => 'termo_fomento',
            'objeto' => 'Oficinas de música', 'valor_repasse' => 10000, 'data_inicio' => '2026-01-01', 'data_fim' => '2026-12-31', 'status' => 'vigente']);

        $this->ug = $this->usuario(['setor' => 'ug', 'orgao_id' => $educacao->id], 'responsavel_unidade_gestora');
        $this->ugDeOutra = $this->usuario(['setor' => 'ug', 'orgao_id' => $saude->id], 'responsavel_unidade_gestora');
    }

    private function usuario(array $dados, string $papel): User
    {
        $u = User::factory()->create($dados + ['status' => true, 'approval_status' => 'aprovado']);
        $u->assignRole($papel);

        return $u;
    }

    private function pdf(string $nome): UploadedFile
    {
        return UploadedFile::fake()->create($nome, 20, 'application/pdf');
    }

    private function lancarDespesa(string $data, string $valor, string $nota): void
    {
        $this->actingAs($this->rl)->post(route('lancamentos.despesas.store', $this->instrumento), [
            'data_despesa' => $data, 'valor' => $valor, 'natureza' => 'material_consumo', 'fornecedor' => 'Papelaria',
            'nota_fiscal' => $this->pdf($nota),
        ])->assertSessionHasNoErrors();
    }

    private function lancar(array $dados, ?string $arquivo = null): void
    {
        $this->actingAs($this->rl)->post(route('lancamentos.store', $this->instrumento),
            $dados + ($arquivo ? ['arquivo' => $this->pdf($arquivo)] : []))->assertSessionHasNoErrors();
    }

    public function test_a_osc_lanca_e_a_prefeitura_ve_no_painel_mas_nao_lanca(): void
    {
        $this->lancarDespesa('2026-02-10', '1.250,00', 'nf-10.pdf');
        $this->lancar(['tipo' => 'extrato_conta', 'competencia' => '2026-02'], 'extrato-fev.pdf');
        $this->lancar(['tipo' => 'evidencia', 'competencia' => '2026-02-15', 'descricao' => 'Lista de presença da oficina',
            'meta_id' => $this->proposta->metas()->value('id')], 'lista.pdf');

        $despesa = Despesa::sole();
        $this->assertSame('1250.00', $despesa->valor);
        $this->assertTrue($despesa->aConferir());
        $this->assertSame('2026-02-01', Lancamento::where('tipo', 'extrato_conta')->sole()->competencia->toDateString());

        $this->actingAs($this->ug)->get(route('lancamentos.painel', $this->instrumento))->assertOk()
            ->assertSee('Papelaria')->assertSee('Lista de presença da oficina')->assertSee('Conferir todos os pendentes');
        $this->actingAs($this->ug)->post(route('lancamentos.despesas.store', $this->instrumento), [])->assertForbidden();
        $this->assertFalse(Route::has('despesas.store'), 'a Prefeitura não lança mais despesa na Execução');

        // Outra Secretaria e outra OSC não abrem o painel.
        $this->actingAs($this->ugDeOutra)->get(route('lancamentos.painel', $this->instrumento))->assertForbidden();
        $this->actingAs($this->deOutraOsc)->get(route('lancamentos.painel', $this->instrumento))->assertForbidden();
    }

    public function test_a_prefeitura_confere_ou_devolve_e_a_osc_corrige(): void
    {
        $this->lancarDespesa('2026-03-05', '300,00', 'nf-1.pdf');
        $this->lancarDespesa('2026-03-06', '450,00', 'nf-2.pdf');
        [$primeira, $segunda] = Despesa::orderBy('id')->get()->all();

        $this->actingAs($this->ug)->post(route('lancamentos.despesas.conferir', $primeira), ['acao' => 'conferir'])->assertSessionHasNoErrors();
        $this->actingAs($this->ug)->post(route('lancamentos.despesas.conferir', $segunda), ['acao' => 'devolver'])->assertSessionHasErrors('motivo');
        $this->actingAs($this->ug)->post(route('lancamentos.despesas.conferir', $segunda),
            ['acao' => 'devolver', 'motivo' => 'Nota em nome de outra entidade'])->assertSessionHasNoErrors();
        Mail::assertQueued(Aviso::class, fn ($m) => $m->hasTo($this->rl->email));

        // Conferido não se altera; o devolvido se corrige e volta à conferência.
        $this->actingAs($this->rl)->delete(route('lancamentos.despesas.destroy', $primeira))->assertStatus(422);
        $this->actingAs($this->rl)->put(route('lancamentos.despesas.update', $segunda), [
            'data_despesa' => '2026-03-06', 'valor' => '450,00', 'natureza' => 'material_consumo', 'nota_fiscal' => $this->pdf('nf-2-correta.pdf'),
        ])->assertSessionHasNoErrors();
        $segunda->refresh();
        $this->assertTrue($segunda->aConferir());
        $this->assertSame('nf-2-correta.pdf', $segunda->nota_fiscal_nome);

        // A OSC não confere.
        $this->actingAs($this->rl)->post(route('lancamentos.despesas.conferir', $segunda), ['acao' => 'conferir'])->assertForbidden();
    }

    public function test_a_prestacao_nasce_montada_com_os_lancamentos_do_periodo(): void
    {
        $this->lancarDespesa('2026-01-20', '100,00', 'nf-a.pdf');
        $this->lancarDespesa('2026-02-20', '200,00', 'nf-b.pdf');
        $this->lancarDespesa('2026-02-25', '999,00', 'nf-devolvida.pdf');
        Despesa::where('valor', 999)->sole()->devolver($this->ug, 'Sem relação com o objeto');
        $this->lancarDespesa('2026-05-02', '50,00', 'nf-fora.pdf');
        $this->lancar(['tipo' => 'extrato_conta', 'competencia' => '2026-01'], 'extrato-jan.pdf');
        $this->lancar(['tipo' => 'extrato_conta', 'competencia' => '2026-02'], 'extrato-fev.pdf');
        $this->lancar(['tipo' => 'evidencia', 'competencia' => '2026-02-10', 'descricao' => 'Fotos da apresentação'], 'fotos.pdf');
        $this->lancar(['tipo' => 'folha', 'competencia' => '2026-01', 'folha_funcionarios' => 3, 'folha_salarios' => '3.000,00', 'folha_inss' => '330,00']);
        $this->lancar(['tipo' => 'folha', 'competencia' => '2026-02', 'folha_funcionarios' => 4, 'folha_salarios' => '4.000,00', 'folha_inss' => '440,00']);
        $this->lancar(['tipo' => 'bem', 'competencia' => '2026-02-03', 'descricao' => 'Violão', 'quantidade' => 2, 'valor' => '800,00', 'documento' => '555']);

        $this->actingAs($this->rl)->post(route('prestacao-contas.store'), ['instrumento_id' => $this->instrumento->id,
            'tipo' => 'parcial', 'periodo_inicio' => '2026-01-01', 'periodo_fim' => '2026-03-31'])->assertSessionHasNoErrors();
        $pc = PrestacaoContas::sole();
        $peca = fn (string $chave) => $pc->pecas()->where('chave', $chave)->sole();
        $nomesNoZip = function (string $chave) use ($peca) {
            $zip = new \ZipArchive();
            $zip->open(Storage::disk('local')->path($peca($chave)->arquivo_path));

            return collect(range(0, $zip->numFiles - 1))->map(fn ($i) => $zip->getNameIndex($i))->all();
        };

        // Folha somada; funcionários, o maior do período.
        $this->assertSame(4, (int) $pc->folha_funcionarios);
        $this->assertEquals(7000, (float) $pc->folha_salarios);
        $this->assertEquals(770, (float) $pc->folha_inss);
        // O bem no Anexo VII.
        $this->assertSame('Violão', $pc->bens()->sole()->especificacao);
        $this->assertEquals(1600, $pc->bens()->sole()->total());
        // Arquivos: dois extratos num ZIP, a evidência como veio, as notas do período sem a devolvida.
        $this->assertCount(2, $nomesNoZip('extrato_conta'));
        $this->assertSame('fotos.pdf', $peca('docs_execucao')->arquivo_nome);
        $notas = $nomesNoZip('comprovantes_despesas');
        $this->assertCount(2, $notas);
        $this->assertStringContainsString('nf-a.pdf', $notas[0]);
        $this->assertTrue($peca('extrato_conta')->dos_lancamentos);
        $this->assertFalse($peca('extrato_aplicacao')->temArquivo());

        // O relatório cita a evidência; a OSC só envia depois de corrigir o devolvido.
        $this->actingAs($this->rl)->get(route('prestacao-contas.show', $pc))->assertOk()->assertSee('Atualizar com os lançamentos');
        $this->assertStringContainsString('Fotos da apresentação', $peca('relatorio')->conteudo);
        $this->assertContains('1 lançamento(s) do período devolvido(s) pela Prefeitura (corrigir no painel)', $pc->fresh()->pendencias());

        // Arquivo enviado à mão na peça não é refeito pela atualização.
        $this->actingAs($this->rl)->post(route('pecas.upload', $peca('extrato_aplicacao')), ['arquivo' => $this->pdf('aplicacao-trimestre.pdf')])
            ->assertSessionHasNoErrors();
        $this->lancar(['tipo' => 'extrato_aplicacao', 'competencia' => '2026-03'], 'aplicacao-mar.pdf');
        $this->actingAs($this->rl)->post(route('prestacao-contas.montar', $pc))->assertSessionHasNoErrors();
        $this->assertSame('aplicacao-trimestre.pdf', $peca('extrato_aplicacao')->arquivo_nome);
    }

    public function test_enviada_a_prestacao_os_lancamentos_do_periodo_ficam_travados(): void
    {
        $this->lancarDespesa('2026-01-20', '100,00', 'nf-a.pdf');
        $this->lancarDespesa('2026-06-20', '100,00', 'nf-depois.pdf');
        [$noPeriodo, $depois] = Despesa::orderBy('data_despesa')->get()->all();
        $pc = PrestacaoContas::forceCreate(['instrumento_id' => $this->instrumento->id, 'tipo' => 'parcial', 'numero' => 1,
            'periodo_inicio' => '2026-01-01', 'periodo_fim' => '2026-03-31', 'etapa' => 1, 'setor' => 'scp', 'iniciada_em' => now()]);

        $this->assertTrue($noPeriodo->travadoPorPrestacao());
        $this->actingAs($this->rl)->delete(route('lancamentos.despesas.destroy', $noPeriodo))->assertStatus(422);
        $this->actingAs($this->ug)->post(route('lancamentos.despesas.conferir', $noPeriodo), ['acao' => 'conferir'])->assertStatus(422);
        $this->actingAs($this->rl)->delete(route('lancamentos.despesas.destroy', $depois))->assertRedirect();

        // Lançar com data num período já enviado não entra: ficaria preso sem conferência.
        $this->actingAs($this->rl)->post(route('lancamentos.despesas.store', $this->instrumento), [
            'data_despesa' => '2026-02-01', 'valor' => '10,00', 'natureza' => 'material_consumo', 'nota_fiscal' => $this->pdf('atrasada.pdf'),
        ])->assertSessionHasErrors('periodo');
        $this->assertSame(1, Despesa::count());

        // Na última etapa, a UG só encerra com os lançamentos do período conferidos.
        $pc->forceFill(['etapa' => 2, 'setor' => 'ug'])->save();
        $this->assertContains('1 lançamento(s) do período ainda a conferir no painel', $pc->fresh()->pendencias());
    }

    public function test_a_lista_aponta_o_painel_de_cada_parceria(): void
    {
        $this->lancarDespesa('2026-01-20', '100,00', 'nf-a.pdf');

        $this->actingAs($this->rl)->get(route('prestacao-contas.index'))->assertOk()
            ->assertSee('Painel de cada parceria')->assertSee(route('lancamentos.painel', $this->instrumento), false);
        $this->actingAs($this->ug)->get(route('prestacao-contas.index'))->assertOk()
            ->assertSee('Lançamentos a conferir')->assertSee('1 a conferir');
        $this->actingAs($this->ugDeOutra)->get(route('prestacao-contas.index'))->assertOk()->assertDontSee('1 a conferir');
    }
}
