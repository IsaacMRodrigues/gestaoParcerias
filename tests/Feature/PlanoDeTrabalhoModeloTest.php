<?php

namespace Tests\Feature;

use App\Models\Chamamento;
use App\Models\Despesa;
use App\Models\ManifestacaoInteresse;
use App\Models\Orgao;
use App\Models\Osc;
use App\Models\PrestacaoContas;
use App\Models\Programa;
use App\Models\Proposta;
use App\Models\User;
use App\Support\PlanoDocumento;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * O Plano de Trabalho à risca do modelo da cliente (Docs. Desenvolvimento/
 * Planodetrabalho.docx, 29/09/2026): os 13 itens, e nada além deles.
 */
class PlanoDeTrabalhoModeloTest extends TestCase
{
    use RefreshDatabase;

    private User $rl;
    private Osc $osc;
    private ManifestacaoInteresse $plano;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Mail::fake();

        $this->osc = Osc::forceCreate(['name' => 'Associação Viver', 'cnpj' => '11.111.111/0001-11', 'email' => 'osc@example.com',
            'cep' => '35935-000', 'logradouro' => 'Rua A', 'numero' => '10', 'cidade' => 'São Gonçalo do Rio Abaixo', 'estado' => 'MG',
            'resp_nome' => 'Maria Presidente', 'resp_cpf' => '111.111.111-11', 'resp_rg' => 'MG-1']);
        $this->rl = User::factory()->create(['osc_id' => $this->osc->id, 'setor' => 'osc', 'status' => true, 'approval_status' => 'aprovado']);
        $this->rl->assignRole('responsavel_legal');
        $this->osc->forceFill(['user_id' => $this->rl->id])->save();

        $this->plano = ManifestacaoInteresse::forceCreate(['tipo' => 'proposta', 'osc_id' => $this->osc->id, 'titulo' => 'Oficinas',
            'objeto' => 'x', 'justificativa' => 'x', 'valor_solicitado' => 1000, 'status' => 'rascunho']);
    }

    private function url(string $resto = ''): string
    {
        return "/portal/manifestacoes/{$this->plano->id}/plano{$resto}";
    }

    public function test_itens_2_a_6_gravam_objetivos_especificos_e_metodologia_sem_contrapartida_em_dinheiro(): void
    {
        $this->actingAs($this->rl)->put($this->url(), [
            'titulo' => 'Oficinas de música', 'objeto' => 'Aulas', 'valor_solicitado' => 5000,
            'objetivos' => 'Geral', 'objetivos_especificos' => 'Específicos', 'metodologia' => 'Aulas práticas',
            'valor_proprio' => 999, 'atuacao_rede' => 1,
        ])->assertSessionHasNoErrors();

        $p = $this->plano->fresh();
        $this->assertSame(['Específicos', 'Aulas práticas'], [$p->objetivos_especificos, $p->metodologia]);
        $this->assertSame([null, false], [$p->valor_proprio, (bool) $p->atuacao_rede], 'o que não está no modelo não entra');

        $this->actingAs($this->rl)->get("/portal/manifestacoes/{$this->plano->id}")->assertOk()
            ->assertSee('5 – Metodologia')->assertSee('13 – Plano de aplicação dos recursos (Planilha anexa)')
            ->assertDontSee('Endereços de execução')->assertDontSee('Haverá atuação em rede')
            ->assertDontSee('name="valor_proprio"', false);
    }

    public function test_meta_com_objetivo_especifico_e_atividades_com_periodo_e_estimado(): void
    {
        $this->actingAs($this->rl)->post($this->url('/metas'), [
            'objetivo_especifico' => 'Ensinar música', 'descricao' => 'Formar 30 alunos',
            'indicador' => 'Satisfação', 'meta_quantitativa' => '30 alunos', 'resultados_esperados' => 'Alunos formados',
            'meios_verificacao' => 'Lista de presença',
        ])->assertSessionHasNoErrors();
        $meta = $this->plano->metas()->sole();
        $this->assertSame('Ensinar música', $meta->objetivo_especifico);

        $this->actingAs($this->rl)->post($this->url("/metas/{$meta->id}/etapas"), [
            'descricao' => 'Aulas de violão', 'data_inicio' => '2026-11-01', 'data_fim' => '2026-12-20', 'valor' => 600,
        ])->assertSessionHasNoErrors();
        $this->actingAs($this->rl)->post($this->url("/metas/{$meta->id}/etapas"), ['descricao' => 'Apresentação', 'valor' => 400]);

        $this->assertSame(1000.0, $this->plano->fresh()->load('metas.etapas')->totalDasMetas(), 'item 10: soma das atividades');
    }

    public function test_desembolso_por_meta_e_parcela(): void
    {
        $meta = $this->plano->criarMeta(['descricao' => 'Meta 1']);
        $outroPlano = ManifestacaoInteresse::forceCreate(['tipo' => 'proposta', 'osc_id' => $this->osc->id, 'titulo' => 'Outro',
            'objeto' => 'x', 'justificativa' => 'x', 'status' => 'rascunho']);
        $metaDeOutro = $outroPlano->criarMeta(['descricao' => 'Alheia']);

        $this->actingAs($this->rl)->post($this->url('/desembolsos'), ['meta_id' => $meta->id, 'parcela' => 1, 'valor' => 300])
            ->assertSessionHasNoErrors();
        $this->actingAs($this->rl)->post($this->url('/desembolsos'), ['meta_id' => $meta->id, 'parcela' => 1, 'valor' => 200]);
        $this->actingAs($this->rl)->post($this->url('/desembolsos'), ['meta_id' => $meta->id, 'parcela' => 2, 'valor' => 500]);
        $this->actingAs($this->rl)->post($this->url('/desembolsos'), ['meta_id' => $metaDeOutro->id, 'parcela' => 1, 'valor' => 1])
            ->assertSessionHasErrors('meta_id');

        $parcelas = $this->plano->desembolsos()->get();
        $this->assertSame([1 => '500.00', 2 => '500.00'], $parcelas->pluck('valor', 'parcela')->all(), 'mesma meta e parcela somam');

        $this->actingAs($this->rl)->get("/portal/manifestacoes/{$this->plano->id}")->assertOk()
            ->assertSee('11 – Cronograma de desembolso')->assertSee('12ª par');
    }

    public function test_contrapartida_nao_financeira_e_equipe(): void
    {
        $this->actingAs($this->rl)->post($this->url('/contrapartidas'), ['descricao' => 'Cessão do salão', 'quantidade' => '1 salão'])
            ->assertSessionHasNoErrors();
        $this->actingAs($this->rl)->post($this->url('/equipe'), [
            'cargo_funcao' => 'Professor', 'formacao' => 'Licenciatura em Música', 'carga_horaria_mensal' => '80 horas', 'vinculo' => 'clt',
        ])->assertSessionHasNoErrors();
        $this->actingAs($this->rl)->post($this->url('/equipe'), ['cargo_funcao' => 'X', 'vinculo' => 'estagio'])
            ->assertSessionHasErrors('vinculo');

        $this->assertSame(1, $this->plano->contrapartidas()->sole()->numero);
        $this->assertSame('CLT', $this->plano->equipe()->sole()->vinculoLabel());
    }

    public function test_as_12_naturezas_do_modelo_cabem_nos_blocos_da_prestacao(): void
    {
        $this->assertCount(12, Despesa::NATUREZAS);
        $nosBlocos = collect(PrestacaoContas::BLOCOS)->pluck('naturezas')->flatten()->all();
        foreach (array_keys(Despesa::NATUREZAS) as $natureza) {
            $this->assertContains($natureza, $nosBlocos, "a natureza {$natureza} precisa de um bloco no Anexo VI");
        }

        $this->assertSame('Outros (lista antiga)', Despesa::rotuloNatureza('outros'), 'registro antigo segue legível');
    }

    public function test_o_documento_segue_os_13_itens_do_modelo(): void
    {
        $orgao = Orgao::forceCreate(['name' => 'Educação']);
        $programa = Programa::forceCreate(['orgao_id' => $orgao->id, 'name' => 'P', 'tipo' => 'termo_fomento']);
        $chamamento = Chamamento::forceCreate(['programa_id' => $programa->id, 'numero' => '001/2026', 'titulo' => 'C',
            'objeto' => 'x', 'tipo' => 'chamamento_publico', 'status' => 'publicado']);
        $p = Proposta::forceCreate(['chamamento_id' => $chamamento->id, 'osc_id' => $this->osc->id, 'titulo' => 'Oficinas',
            'objeto' => 'Aulas', 'status' => 'rascunho', 'valor_solicitado' => 1000, 'metodologia' => 'Aulas práticas']);
        $meta = $p->criarMeta(['descricao' => 'Formar alunos', 'objetivo_especifico' => 'Ensinar']);
        $meta->etapas()->create(['numero' => 1, 'descricao' => 'Aulas', 'valor' => 1000]);
        $p->planoItens()->create(['numero' => 1, 'descricao' => 'Instrutor', 'tipo_despesa' => 'servicos_pf', 'quantidade' => 1, 'valor_unitario' => 1000]);
        $p->desembolsos()->create(['meta_id' => $meta->id, 'parcela' => 1, 'valor' => 1000]);

        $html = PlanoDocumento::render($p->fresh());

        $titulos = [
            '1 - Identificação Órgão/Entidade Proponente', '2 - Identificação do projeto', 'PEDIDO DE AVALIAÇÃO',
            '3 - Descrição da realidade', '4 - Objetivos', '5 - Metodologia', '6 – Diagnóstico/Justificativa',
            '7 – Metas, indicadores e resultados', '8 – Descrição da contrapartida não financeira',
            '9 – Descrição da natureza da despesa', '10 – Cronograma de execução física e financeira',
            '11 – Cronograma de desembolso', '12 - Relação da equipe', '13 – Plano de aplicação dos recursos (Planilha anexa)',
        ];
        $posicao = -1;
        foreach ($titulos as $titulo) {
            $achou = mb_strpos($html, e($titulo));
            $this->assertNotFalse($achou, "falta: {$titulo}");
            $this->assertGreaterThan($posicao, $achou, "fora de ordem: {$titulo}");
            $posicao = $achou;
        }

        $this->assertStringContainsString('Maria Presidente', $html);
        $this->assertStringContainsString('Outros Serviços de Terceiros – Pessoa Física', $html);
        $this->assertStringContainsString('1ª par', $html);
        $this->assertStringNotContainsString('Endereços de execução', $html);
        $this->assertStringNotContainsString('Contrapartida da OSC', $html);
    }
}
