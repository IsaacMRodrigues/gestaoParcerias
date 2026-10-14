<?php

namespace Tests\Feature;

use App\Models\Chamamento;
use App\Models\Despesa;
use App\Models\Instrumento;
use App\Models\Orgao;
use App\Models\Osc;
use App\Models\PrestacaoContas;
use App\Models\Programa;
use App\Models\Proposta;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Relatórios da prestação de contas: a execução financeira de cada parceria e a situação das
 * prestações, na tela, em planilha e em PDF, cada um vendo o que é seu.
 */
class RelatorioPrestacaoTest extends TestCase
{
    use RefreshDatabase;

    private Instrumento $educacao;
    private Instrumento $saude;
    private User $ug;
    private User $rl;
    private User $scp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);

        [$this->educacao, $oscEducacao] = $this->parceria('Educação', 'Associação Viver', '001/2026');
        [$this->saude] = $this->parceria('Saúde', 'Instituto Saúde', '002/2026');

        $this->educacao->proposta->planoItens()->create(['numero' => 1, 'descricao' => 'Material', 'tipo_despesa' => 'material_consumo', 'quantidade' => 1, 'valor_unitario' => 2000]);
        $this->educacao->proposta->planoItens()->create(['numero' => 2, 'descricao' => 'Instrutor', 'tipo_despesa' => 'servicos_pf', 'quantidade' => 1, 'valor_unitario' => 8000]);
        $this->educacao->repasses()->create(['parcela' => 1, 'data_repasse' => '2026-02-01', 'valor' => 5000]);
        $this->educacao->repasses()->create(['parcela' => 2, 'data_repasse' => '2026-06-01', 'valor' => 5000]);
        $this->despesa($this->educacao, '2026-02-10', 1250, 'material_consumo', 'conferido');
        $this->despesa($this->educacao, '2026-03-10', 3000, 'servicos_pf', 'pendente');
        $this->despesa($this->educacao, '2026-07-10', 700, 'material_consumo', 'devolvido');
        $this->despesa($this->saude, '2026-02-15', 999, 'material_consumo', 'conferido');

        PrestacaoContas::forceCreate(['instrumento_id' => $this->educacao->id, 'tipo' => 'parcial', 'numero' => 1, 'periodo_inicio' => '2026-01-01',
            'periodo_fim' => '2026-04-30', 'etapa' => 1, 'setor' => 'scp', 'iniciada_em' => now()]);
        PrestacaoContas::forceCreate(['instrumento_id' => $this->educacao->id, 'tipo' => 'parcial', 'numero' => 2, 'periodo_inicio' => '2026-05-01',
            'periodo_fim' => '2026-08-31', 'etapa' => 0, 'setor' => 'osc', 'iniciada_em' => now()]);
        PrestacaoContas::forceCreate(['instrumento_id' => $this->saude->id, 'tipo' => 'final', 'periodo_inicio' => '2026-01-01',
            'periodo_fim' => '2026-12-31', 'etapa' => 2, 'setor' => 'ug', 'iniciada_em' => now(), 'concluida_em' => now()]);

        $this->ug = $this->usuario(['setor' => 'ug', 'orgao_id' => $this->educacao->proposta->chamamento->programa->orgao_id], 'responsavel_unidade_gestora');
        $this->scp = $this->usuario(['setor' => 'scp'], 'analista_tecnico_scp');
        $this->rl = $this->usuario(['osc_id' => $oscEducacao->id, 'setor' => 'osc'], 'responsavel_legal');
        $oscEducacao->forceFill(['user_id' => $this->rl->id])->save();
    }

    private function parceria(string $secretaria, string $osc, string $numero): array
    {
        $orgao = Orgao::forceCreate(['name' => $secretaria]);
        $chamamento = Chamamento::forceCreate(['programa_id' => Programa::doOrgao($orgao)->id, 'numero' => $numero, 'titulo' => 'x',
            'objeto' => 'x', 'tipo' => 'chamamento_publico', 'status' => 'encerrado']);
        $o = Osc::forceCreate(['name' => $osc, 'cnpj' => fake()->numerify('##.###.###/0001-##'), 'email' => fake()->safeEmail()]);
        $p = Proposta::forceCreate(['chamamento_id' => $chamamento->id, 'osc_id' => $o->id, 'titulo' => 'x', 'objeto' => 'x',
            'status' => 'aprovada', 'valor_solicitado' => 10000]);

        return [Instrumento::forceCreate(['proposta_id' => $p->id, 'numero' => $numero, 'tipo' => 'termo_fomento', 'objeto' => 'x',
            'valor_repasse' => 10000, 'data_inicio' => '2026-01-01', 'data_fim' => '2026-12-31', 'status' => 'vigente']), $o];
    }

    private function despesa(Instrumento $i, string $data, float $valor, string $natureza, string $situacao): void
    {
        Despesa::forceCreate(['instrumento_id' => $i->id, 'data_despesa' => $data, 'valor' => $valor, 'natureza' => $natureza, 'situacao' => $situacao]);
    }

    private function usuario(array $dados, string $papel): User
    {
        $u = User::factory()->create($dados + ['status' => true, 'approval_status' => 'aprovado']);
        $u->assignRole($papel);

        return $u;
    }

    public function test_a_execucao_financeira_por_parceria_respeita_a_secretaria_e_o_periodo(): void
    {
        $this->actingAs($this->ug)->get(route('prestacao-contas.relatorios'))->assertOk()
            ->assertSee('Execução financeira')->assertSee('001/2026')->assertDontSee('002/2026')
            ->assertSee('R$ 10.000,00')   // repassado
            ->assertSee('R$ 4.950,00')    // despesas
            ->assertSee('R$ 3.000,00');   // a conferir

        // O período corta repasses e despesas.
        $this->actingAs($this->ug)->get(route('prestacao-contas.relatorios', ['de' => '2026-01-01', 'ate' => '2026-04-30']))->assertOk()
            ->assertSee('R$ 5.000,00')->assertSee('R$ 4.250,00')->assertDontSee('R$ 4.950,00');

        // A SCP vê o município inteiro.
        $this->actingAs($this->scp)->get(route('prestacao-contas.relatorios'))->assertSee('001/2026')->assertSee('002/2026');
    }

    public function test_uma_parceria_mostra_o_plano_aprovado_contra_o_executado(): void
    {
        $this->actingAs($this->ug)->get(route('prestacao-contas.relatorios', ['instrumento_id' => $this->educacao->id]))->assertOk()
            ->assertSee('Plano de aplicação aprovado × executado')
            ->assertSee('Material de Consumo')->assertSee('R$ 2.000,00')->assertSee('R$ 1.950,00')
            ->assertSee('97,5%');
    }

    public function test_a_situacao_das_prestacoes_com_filtro(): void
    {
        $this->actingAs($this->scp)->get(route('prestacao-contas.relatorios', ['relatorio' => 'prestacoes']))->assertOk()
            ->assertSee('Parcial nº 1')->assertSee('Parcial nº 2')->assertSee('Encerrada em');

        $this->actingAs($this->scp)->get(route('prestacao-contas.relatorios', ['relatorio' => 'prestacoes', 'situacao' => 'com_osc']))
            ->assertSee('Parcial nº 2')->assertDontSee('Parcial nº 1')->assertDontSee('Encerrada em');
    }

    public function test_planilha_e_pdf(): void
    {
        $csv = $this->actingAs($this->ug)->get(route('prestacao-contas.relatorios', ['formato' => 'planilha']))->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')->getContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('Despesas (R$)', $csv);
        $this->assertStringContainsString('001/2026;"Associação Viver";Educação;10000,00;10000,00;4950,00', $csv);

        $pdf = $this->actingAs($this->ug)->get(route('prestacao-contas.relatorios', ['formato' => 'pdf', 'relatorio' => 'prestacoes']))->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')->getContent();
        $this->assertStringStartsWith('%PDF', $pdf);
    }

    public function test_a_osc_ve_so_as_proprias_e_quem_nao_trata_a_prestacao_nao_entra(): void
    {
        $this->actingAs($this->rl)->get(route('prestacao-contas.relatorios', ['osc_id' => $this->saude->proposta->osc_id]))->assertOk()
            ->assertSee('001/2026')->assertDontSee('002/2026')->assertDontSee('Instituto Saúde');

        $cadastrador = $this->usuario(['setor' => 'ug', 'orgao_id' => $this->ug->orgao_id], 'cadastrador');
        $this->actingAs($cadastrador)->get(route('prestacao-contas.relatorios'))->assertForbidden();
    }
}
