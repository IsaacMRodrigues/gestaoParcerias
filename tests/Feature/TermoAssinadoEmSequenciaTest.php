<?php

namespace Tests\Feature;

use App\Models\Chamamento;
use App\Models\Orgao;
use App\Models\Osc;
use App\Models\Peca;
use App\Models\Programa;
use App\Models\Proposta;
use App\Models\User;
use App\Support\CaixaDeEntrada;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * O Termo de Parceria assinado em sequência (decisão da gestão, 01/10/2026):
 * a SCP emite sem assinar; assinam a OSC, o Responsável da UG, o Gestor da
 * Parceria (que a SCP escolhe) e, por último, o Gabinete — e entre uma
 * assinatura e outra o Termo volta à SCP, que o encaminha.
 */
class TermoAssinadoEmSequenciaTest extends TestCase
{
    use RefreshDatabase;

    private Proposta $proposta;
    private Peca $termo;
    private User $rl;
    private User $scp;
    private User $responsavelUg;
    private User $outroDaUg;
    private User $gestor;
    private User $outroGestor;
    private User $prefeito;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Mail::fake();

        $orgao = Orgao::forceCreate(['name' => 'Educação']);
        $programa = Programa::forceCreate(['orgao_id' => $orgao->id, 'name' => 'P', 'tipo' => 'termo_fomento']);
        $chamamento = Chamamento::forceCreate(['programa_id' => $programa->id, 'numero' => '001/2026', 'titulo' => 'C',
            'objeto' => 'x', 'tipo' => 'chamamento_publico', 'status' => 'publicado']);
        $osc = Osc::forceCreate(['name' => 'Associação Viver', 'cnpj' => '11.111.111/0001-11', 'email' => 'osc@example.com']);
        $this->rl = User::factory()->create(['osc_id' => $osc->id, 'setor' => 'osc', 'status' => true, 'approval_status' => 'aprovado']);
        $this->rl->assignRole('responsavel_legal');
        $osc->forceFill(['user_id' => $this->rl->id])->save();

        $this->proposta = Proposta::forceCreate(['chamamento_id' => $chamamento->id, 'osc_id' => $osc->id, 'titulo' => 'Oficinas de música',
            'objeto' => 'x', 'status' => 'aprovada', 'valor_solicitado' => 1,
            'celebracao_iniciada_em' => now(), 'celebracao_etapa' => 8, 'celebracao_setor' => 'scp']);
        Peca::sincronizar($this->proposta, 'celebracao');
        $this->proposta->pecas()->where('chave', 'parecer_scp')->update(['conteudo' => '<p>ok</p>', 'assinado_em' => now()]);
        $this->termo = $this->proposta->pecas()->where('chave', 'termo')->sole();

        $servidor = function (string $setor, ?string $papel, ?Orgao $o = null) {
            $u = User::factory()->create(['setor' => $setor, 'orgao_id' => $o?->id, 'status' => true, 'approval_status' => 'aprovado']);
            if ($papel) {
                $u->assignRole($papel);
            }

            return $u;
        };
        $this->scp = $servidor('scp', 'analista_tecnico_scp');
        $this->responsavelUg = $servidor('ug', 'responsavel_unidade_gestora', $orgao);
        $this->outroDaUg = $servidor('ug', 'cadastrador', $orgao);
        $this->gestor = $servidor('ug', 'gestor_parceria', $orgao);
        $this->outroGestor = $servidor('ug', 'gestor_parceria', $orgao);
        $this->prefeito = $servidor('pm', 'prefeito_municipal');
    }

    private function avancar(User $quem, array $dados = [])
    {
        return $this->actingAs($quem)->post("/celebracao/{$this->proposta->id}/avancar", $dados);
    }

    private function assinar(User $quem)
    {
        return $this->actingAs($quem)->patch("/pecas/{$this->termo->id}/assinar-parte");
    }

    private function etapa(): int
    {
        return (int) $this->proposta->fresh()->celebracao_etapa;
    }

    public function test_a_sequencia_inteira_osc_ug_gestor_gabinete(): void
    {
        // Etapa 9: a SCP emite o Termo, sem assinar.
        $this->assertFalse($this->termo->podeAssinar($this->scp));
        $this->actingAs($this->scp)->put("/pecas/{$this->termo->id}", ['conteudo' => '<p>Termo de Fomento nº 1/2026</p>'])->assertSessionHasNoErrors();
        $this->avancar($this->scp)->assertSessionHasNoErrors();
        $this->assertSame(9, $this->etapa());

        // Etapa 10: a OSC assina e devolve à SCP.
        $this->avancar($this->rl)->assertStatus(422);
        $this->assinar($this->rl)->assertSessionHasNoErrors();
        $this->avancar($this->rl)->assertSessionHasNoErrors();
        $this->assertSame(10, $this->etapa());

        // Etapa 11: a SCP encaminha à UG. Etapa 12: só o Responsável da UG assina.
        $this->avancar($this->scp)->assertSessionHasNoErrors();
        $this->assertSame(11, $this->etapa());
        $this->assinar($this->outroDaUg)->assertForbidden();
        $this->assinar($this->responsavelUg)->assertSessionHasNoErrors();
        $this->avancar($this->responsavelUg)->assertSessionHasNoErrors();

        // Etapa 13: a SCP escolhe o Gestor.
        $this->actingAs($this->scp)->get("/celebracao/{$this->proposta->id}")->assertOk()
            ->assertSee('Gestor da Parceria que vai assinar o Termo');
        $this->avancar($this->scp)->assertSessionHasErrors('gestor_id');
        $this->avancar($this->scp, ['gestor_id' => $this->gestor->id])->assertSessionHasNoErrors();
        $p = $this->proposta->fresh();
        $this->assertSame([13, 'gestor', $this->gestor->id], [(int) $p->celebracao_etapa, $p->celebracao_setor, (int) $p->celebracao_gestor_id]);
        $this->assertTrue(CaixaDeEntrada::para($this->gestor)->itens->contains(fn ($i) => $i['tramite'] === 'Celebração'));
        $this->assertFalse(CaixaDeEntrada::para($this->outroGestor)->itens->contains(fn ($i) => $i['tramite'] === 'Celebração'));

        // Etapa 14: só o Gestor escolhido assina.
        $this->actingAs($this->gestor)->get("/celebracao/{$this->proposta->id}")->assertOk()
            ->assertSee('Assinar como Gestor da Parceria');
        $this->actingAs($this->outroGestor)->get("/celebracao/{$this->proposta->id}")->assertOk()
            ->assertDontSee('Assinar como Gestor da Parceria');
        $this->assinar($this->outroGestor)->assertForbidden();
        $this->assinar($this->gestor)->assertSessionHasNoErrors();
        $this->avancar($this->gestor)->assertSessionHasNoErrors();

        // Etapa 15: a SCP encaminha ao Gabinete. Etapa 16: a última assinatura.
        $this->avancar($this->scp)->assertSessionHasNoErrors();
        $this->assertFalse($this->termo->fresh()->assinado(), 'sem o Gabinete, o Termo não está assinado');
        $this->assinar($this->prefeito)->assertSessionHasNoErrors();
        $this->assertTrue($this->termo->fresh()->assinado());
        $this->avancar($this->prefeito)->assertSessionHasNoErrors();
        $this->assertSame(16, $this->etapa(), 'segue para a publicação, com a SCP');

        $termo = $this->termo->fresh();
        $this->assertSame(['osc', 'ug', 'gestor', 'pm'], $termo->assinaturasPartes->pluck('papel')->all());
        $this->actingAs($this->scp)->get("/celebracao/{$this->proposta->id}")->assertOk()
            ->assertSee('assinado eletronicamente (Gabinete do Prefeito)');
        $this->assertCount(4, $termo->assinaturasPartes->pluck('codigo_validacao')->unique());

        // Cada código valida e mostra o documento com as quatro assinaturas.
        $this->get('/validar/' . $termo->assinaturasPartes->first()->codigo_validacao)->assertOk()
            ->assertSee('Assinaturas do documento')->assertSee('Gabinete do Prefeito')->assertDontSee('Ainda faltam assinaturas');
    }

    public function test_ninguem_assina_fora_da_vez(): void
    {
        $this->termo->forceFill(['conteudo' => '<p>Termo</p>'])->save();
        $this->proposta->forceFill(['celebracao_etapa' => 11, 'celebracao_setor' => 'ug'])->save();

        // A OSC ainda não assinou: a UG não passa na frente.
        $this->assinar($this->responsavelUg)->assertForbidden();
        $this->assinar($this->prefeito)->assertForbidden();
        $this->assertSame(0, $this->termo->assinaturasPartes()->count());
    }
}
