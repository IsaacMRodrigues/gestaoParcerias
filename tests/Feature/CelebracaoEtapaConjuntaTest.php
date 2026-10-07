<?php

namespace Tests\Feature;

use App\Mail\Aviso;
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
use Tests\Feature\Concerns\PreencheArquivosDaOsc;
use Tests\TestCase;

/**
 * Etapa 3 da Celebração, conjunta (decisão da gestão, 30/09/2026): o que a
 * OSC entregou vai à UG e à SCP ao mesmo tempo, cada uma conclui a sua parte,
 * e a Celebração só avança quando as duas concluírem.
 */
class CelebracaoEtapaConjuntaTest extends TestCase
{
    use RefreshDatabase;
    use PreencheArquivosDaOsc;

    private Proposta $proposta;
    private User $rl;
    private User $ug;
    private User $scp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Mail::fake();

        $orgao = Orgao::forceCreate(['name' => 'Educação']);
        $programa = Programa::forceCreate(['orgao_id' => $orgao->id, 'name' => 'P', 'tipo' => 'termo_fomento']);
        $chamamento = Chamamento::forceCreate(['programa_id' => $programa->id, 'numero' => '001/2026', 'titulo' => 'Oficinas',
            'objeto' => 'x', 'tipo' => 'chamamento_publico', 'status' => 'publicado']);
        $osc = Osc::forceCreate(['name' => 'Associação Viver', 'cnpj' => '11.111.111/0001-11', 'email' => 'osc@example.com']);
        $this->rl = User::factory()->create(['osc_id' => $osc->id, 'setor' => 'osc', 'status' => true, 'approval_status' => 'aprovado']);
        $this->rl->assignRole('responsavel_legal');
        $osc->forceFill(['user_id' => $this->rl->id])->save();
        $this->preencherArquivosDaOsc($osc);

        $this->proposta = Proposta::forceCreate(['chamamento_id' => $chamamento->id, 'osc_id' => $osc->id, 'titulo' => 'Oficinas de música',
            'objeto' => 'x', 'status' => 'aprovada', 'valor_solicitado' => 1,
            'celebracao_iniciada_em' => now(), 'celebracao_etapa' => 1, 'celebracao_setor' => 'osc']);
        Peca::sincronizar($this->proposta, 'celebracao');
        // Tudo entregue e assinado, menos a Aprovação do Plano, que é da UG na etapa conjunta.
        $this->proposta->pecas()->update(['arquivo_path' => 'x.pdf', 'arquivo_nome' => 'x.pdf', 'conteudo' => '<p>x</p>', 'assinado_em' => now()]);
        $this->proposta->pecas()->where('chave', 'aprovacao_plano')->update(['assinado_em' => null]);

        $this->ug = User::factory()->create(['setor' => 'ug', 'orgao_id' => $orgao->id, 'status' => true, 'approval_status' => 'aprovado']);
        $this->ug->assignRole('responsavel_unidade_gestora');
        $this->scp = User::factory()->create(['setor' => 'scp', 'status' => true, 'approval_status' => 'aprovado']);
        $this->scp->assignRole('analista_tecnico_scp');
    }

    private function avancar(User $quem)
    {
        return $this->actingAs($quem)->post("/celebracao/{$this->proposta->id}/avancar");
    }

    private function naCaixa(User $u): bool
    {
        return CaixaDeEntrada::para($u)->itens->contains(fn ($i) => $i['tramite'] === 'Celebração' && $i['titulo'] === 'Oficinas de música');
    }

    public function test_a_osc_encaminha_e_a_etapa_vai_a_ug_e_a_scp_ao_mesmo_tempo(): void
    {
        $this->avancar($this->rl)->assertSessionHasNoErrors()->assertSessionHas('success', fn ($m) => str_contains($m, 'Unidade Gestora e Setor de Convênios'));

        $p = $this->proposta->fresh();
        $this->assertSame(2, (int) $p->celebracao_etapa);
        $this->assertSame(['ug', 'scp'], $p->setoresComAVezNaCelebracao());
        Mail::assertQueued(Aviso::class, fn ($m) => $m->hasTo($this->ug->email));
        Mail::assertQueued(Aviso::class, fn ($m) => $m->hasTo($this->scp->email));
        $this->assertTrue($this->naCaixa($this->ug));
        $this->assertTrue($this->naCaixa($this->scp));

        $this->actingAs($this->scp)->get("/celebracao/{$p->id}")->assertOk()->assertSee('Concluir a minha parte');
        $this->actingAs($this->ug)->get("/celebracao/{$p->id}")->assertOk()->assertSee('Concluir a minha parte');
    }

    public function test_so_avanca_quando_os_dois_concluem(): void
    {
        $this->proposta->forceFill(['celebracao_etapa' => 2, 'celebracao_setor' => 'ug'])->save();

        // A UG tem a sua pendência (a Aprovação do Plano); a SCP não trava por ela.
        $this->avancar($this->ug)->assertStatus(422);
        $this->avancar($this->scp)->assertSessionHasNoErrors();

        $p = $this->proposta->fresh();
        $this->assertSame([2, ['scp']], [(int) $p->celebracao_etapa, $p->celebracao_partes_concluidas]);
        $this->assertFalse($this->naCaixa($this->scp), 'concluída a parte, sai da caixa da SCP');
        $this->assertTrue($this->naCaixa($this->ug));
        $this->avancar($this->scp)->assertForbidden();

        $this->proposta->pecas()->where('chave', 'aprovacao_plano')->update(['assinado_em' => now()]);
        // A parte da UG também pede os Arquivos da OSC analisados nesta parceria.
        $this->avancar($this->ug)->assertStatus(422);
        $this->aprovarArquivosDaOsc($this->proposta);
        $this->avancar($this->ug)->assertSessionHasNoErrors();

        $p = $this->proposta->fresh();
        $this->assertSame([3, 'scp', null], [(int) $p->celebracao_etapa, $p->celebracao_setor, $p->celebracao_partes_concluidas]);
    }

    public function test_devolver_para_a_etapa_conjunta_reabre_as_duas_partes(): void
    {
        $this->proposta->forceFill(['celebracao_etapa' => 3, 'celebracao_setor' => 'scp'])->save();

        $this->actingAs($this->scp)->post("/celebracao/{$this->proposta->id}/devolver", [
            'parecer' => 'Falta a certidão atualizada.', 'etapa_destino' => 2,
        ])->assertSessionHasNoErrors();

        $this->assertSame(['ug', 'scp'], $this->proposta->fresh()->setoresComAVezNaCelebracao());
    }
}
