<?php

namespace Tests\Feature;

use App\Mail\Aviso;
use App\Models\Chamado;
use App\Models\Chamamento;
use App\Models\Orgao;
use App\Models\Osc;
use App\Models\Programa;
use App\Models\Proposta;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Avisos por e-mail: quem recebe e, tão importante quanto, quem não recebe.
 * Ver App\Support\Avisos.
 */
class AvisosPorEmailTest extends TestCase
{
    use RefreshDatabase;

    private Orgao $educacao;
    private Orgao $administracao;
    private Osc $osc;
    private Proposta $proposta;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Mail::fake();

        $this->educacao      = Orgao::forceCreate(['name' => 'Educação']);
        $this->administracao = Orgao::forceCreate(['name' => 'Administração']);
        $programa = Programa::forceCreate(['orgao_id' => $this->educacao->id, 'name' => 'Programa', 'tipo' => 'termo_fomento']);
        $chamamento = Chamamento::forceCreate([
            'programa_id' => $programa->id, 'numero' => '001/2026', 'titulo' => 'Chamamento', 'objeto' => 'x',
            'tipo' => 'chamamento_publico', 'status' => 'publicado',
        ]);
        $this->osc = Osc::forceCreate(['name' => 'OSC Dona', 'cnpj' => '11.111.111/0001-11', 'email' => 'osc@example.com']);
        $this->proposta = Proposta::forceCreate([
            'chamamento_id' => $chamamento->id, 'osc_id' => $this->osc->id, 'titulo' => 'Parceria da Educação',
            'objeto' => 'x', 'status' => 'rascunho', 'valor_solicitado' => 1000,
        ]);
    }

    private function servidor(string $papel, string $setor, ?Orgao $orgao = null): User
    {
        $u = User::factory()->create(['setor' => $setor, 'orgao_id' => $orgao?->id, 'status' => true, 'approval_status' => 'aprovado']);
        $u->assignRole($papel);

        return $u->fresh();
    }

    private function daOsc(string $papel, array $funcoes = []): User
    {
        $u = User::factory()->create(['osc_id' => $this->osc->id, 'setor' => 'osc', 'status' => true, 'approval_status' => 'aprovado']);
        $u->assignRole($papel);
        $u->givePermissionTo($funcoes);
        if ($papel === 'responsavel_legal') {
            $this->osc->forceFill(['user_id' => $u->id])->save();
        }

        return $u->fresh();
    }

    private function recebeu(User $u): bool
    {
        return Mail::queued(Aviso::class, fn (Aviso $m) => $m->hasTo($u->email))->isNotEmpty();
    }

    // ── Contas ───────────────────────────────────────────────────────────

    public function test_conta_de_osc_pendente_avisa_quem_aprova_e_a_aprovacao_avisa_a_pessoa(): void
    {
        $ti  = $this->servidor('administrador_setorial', 'ti');
        $scp = $this->servidor('analista_tecnico_scp', 'scp');
        $ug  = $this->servidor('responsavel_unidade_gestora', 'ug', $this->educacao);
        $rl  = $this->daOsc('responsavel_legal');

        $this->actingAs($rl)->post('/portal/usuarios', [
            'name' => 'Nova Integrante', 'email' => 'nova@example.com', 'password' => 'Senha123', 'password_confirmation' => 'Senha123',
        ]);

        $this->assertTrue($this->recebeu($ti));
        $this->assertTrue($this->recebeu($scp), 'a SCP aprova conta de OSC');
        $this->assertFalse($this->recebeu($ug), 'a UG não aprova conta');
        $this->assertFalse($this->recebeu($rl), 'quem cadastrou não é avisado do próprio ato');

        $nova = User::where('email', 'nova@example.com')->sole();
        $this->actingAs($scp)->patch("/usuarios/{$nova->id}/aprovar");
        $this->assertTrue($this->recebeu($nova));
    }

    // ── Suporte ──────────────────────────────────────────────────────────

    public function test_chamado_avisa_a_equipe_e_a_resposta_avisa_quem_abriu_mas_nota_interna_nao(): void
    {
        $scp    = $this->servidor('analista_tecnico_scp', 'scp');
        $autora = $this->servidor('cadastrador', 'ug', $this->educacao);

        $this->actingAs($autora)->post('/suporte', ['categoria' => 'duvida', 'assunto' => 'Como anexo?', 'mensagem' => 'Não achei.']);
        $this->assertTrue($this->recebeu($scp));
        $this->assertFalse($this->recebeu($autora));

        $chamado = Chamado::sole();
        Mail::fake();
        $this->actingAs($scp)->post("/suporte/{$chamado->id}/mensagens", ['mensagem' => 'Nota só da equipe', 'interna' => 1]);
        Mail::assertNothingQueued();

        $this->actingAs($scp)->post("/suporte/{$chamado->id}/mensagens", ['mensagem' => 'É pelo botão Anexar.']);
        $this->assertTrue($this->recebeu($autora));
    }

    public function test_pedido_de_senha_avisa_a_equipe_e_a_dona_da_conta(): void
    {
        $scp  = $this->servidor('analista_tecnico_scp', 'scp');
        $dona = $this->servidor('cadastrador', 'ug', $this->educacao);

        $this->post('/esqueci-a-senha', ['identificacao' => $dona->email, 'nome' => 'Alguém', 'contato' => '31 99999-0000']);

        $this->assertTrue($this->recebeu($scp));
        $this->assertTrue($this->recebeu($dona), 'se não foi ela quem pediu, fica sabendo');
    }

    // ── Vez no trâmite ───────────────────────────────────────────────────

    public function test_vez_da_osc_na_celebracao_avisa_quem_tem_a_funcao(): void
    {
        $rl        = $this->daOsc('responsavel_legal');
        $comFuncao = $this->daOsc('membro_osc', ['osc_celebracao']);
        $semFuncao = $this->daOsc('membro_osc', ['osc_documentos']);

        $this->proposta->forceFill(['status' => 'aprovada', 'celebracao_etapa' => 1, 'celebracao_setor' => 'osc'])->save();

        $this->assertTrue($this->recebeu($rl));
        $this->assertTrue($this->recebeu($comFuncao));
        $this->assertFalse($this->recebeu($semFuncao), 'sem a função da Celebração, não é com ela');
    }

    public function test_vez_do_setor_avisa_so_a_secretaria_dona(): void
    {
        $ugDona   = $this->servidor('responsavel_unidade_gestora', 'ug', $this->educacao);
        $ugAlheia = $this->servidor('responsavel_unidade_gestora', 'ug', $this->administracao);

        $this->proposta->forceFill(['status' => 'aprovada', 'celebracao_etapa' => 2, 'celebracao_setor' => 'ug'])->save();

        $this->assertTrue($this->recebeu($ugDona));
        $this->assertFalse($this->recebeu($ugAlheia), 'parceria de outra Secretaria não chega por e-mail');
    }

    public function test_setor_que_nao_muda_nao_repete_o_aviso(): void
    {
        $ugDona = $this->servidor('responsavel_unidade_gestora', 'ug', $this->educacao);
        $this->proposta->forceFill(['status' => 'aprovada', 'celebracao_etapa' => 2, 'celebracao_setor' => 'ug'])->save();
        Mail::fake();

        $this->proposta->forceFill(['celebracao_etapa' => 3])->save();

        $this->assertFalse($this->recebeu($ugDona));
    }

    public function test_proposta_submetida_chega_a_ug_e_o_resultado_chega_a_osc(): void
    {
        $ugDona = $this->servidor('responsavel_unidade_gestora', 'ug', $this->educacao);
        $rl     = $this->daOsc('responsavel_legal');

        $this->proposta->forceFill(['status' => 'submetida'])->save();
        $this->assertTrue($this->recebeu($ugDona));

        $this->proposta->forceFill(['status' => 'aprovada'])->save();
        $this->assertTrue($this->recebeu($rl));
    }

    // ── O e-mail ─────────────────────────────────────────────────────────

    public function test_o_aviso_sai_em_portugues_com_o_botao_e_o_endereco(): void
    {
        $aviso = new Aviso(assunto: 'Assunto', titulo: 'É a vez do seu setor', linhas: ['Linha & detalhe'],
            url: 'https://parcerias.pmsgra.net/x', botao: 'Continuar no sistema');

        $aviso->assertHasSubject('[Parcerias] Assunto');
        $aviso->assertSeeInHtml('É a vez do seu setor');
        $aviso->assertSeeInHtml('Continuar no sistema');
        $aviso->assertSeeInText('Linha & detalhe');
        $aviso->assertSeeInText('https://parcerias.pmsgra.net/x');
    }
}
