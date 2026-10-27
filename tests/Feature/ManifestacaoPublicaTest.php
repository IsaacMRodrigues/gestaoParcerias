<?php

namespace Tests\Feature;

use App\Mail\Aviso;
use App\Models\ManifestacaoInteresse;
use App\Models\Orgao;
use App\Models\Osc;
use App\Models\User;
use App\Support\CaixaDeEntrada;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Manifestação de interesse no modelo do PMIS (arts. 18 a 21 da Lei 13.019/2014): qualquer pessoa envia pelo
 * formulário público, sem login; a OSC logada usa o mesmo formulário e fica vinculada. Quem enviou acompanha
 * pelo link pessoal; a lista pública mostra os textos, sem e-mail nem PDF.
 */
class ManifestacaoPublicaTest extends TestCase
{
    use RefreshDatabase;

    private Orgao $educacao;
    private User $ug;
    private User $scp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Mail::fake();
        Storage::fake('local');

        $this->educacao = Orgao::forceCreate(['name' => 'Educação']);
        $this->ug = User::factory()->create(['setor' => 'ug', 'orgao_id' => $this->educacao->id, 'status' => true, 'approval_status' => 'aprovado']);
        $this->ug->assignRole('responsavel_unidade_gestora');
        $this->scp = User::factory()->create(['setor' => 'scp', 'status' => true, 'approval_status' => 'aprovado']);
        $this->scp->assignRole('analista_tecnico_scp');
    }

    private function dados(array $extra = []): array
    {
        return $extra + [
            'proponente_nome'   => 'Coletivo Bairro Vivo',
            'proponente_tipo'   => 'pj',
            'proponente_email'  => 'coletivo@example.com',
            'orgao_id'          => $this->educacao->id,
            'interesse_publico' => 'Reforço escolar para crianças do bairro.',
            'diagnostico'       => 'Evasão alta nos anos finais.',
            'viabilidade'       => 'Doze meses, R$ 50 mil, monitores da comunidade.',
            'arquivo'           => UploadedFile::fake()->create('proposta.pdf', 300, 'application/pdf'),
        ];
    }

    private function enviar(array $extra = [])
    {
        return $this->post(route('manifestacao.publica.store'), $this->dados($extra));
    }

    public function test_qualquer_pessoa_envia_sem_login_e_acompanha_pelo_link(): void
    {
        $this->get(route('manifestacao.publica.create'))->assertOk()
            ->assertSee('Manifestação de Interesse Social (PMIS)')->assertSee('name="proponente_nome"', false)
            ->assertSee('Educação');

        $resposta = $this->enviar();

        $m = ManifestacaoInteresse::sole();
        $resposta->assertRedirect(route('manifestacao.acompanhar', $m->token));
        $this->assertSame(['manifestacao', 'em_analise', 'ug', null], [$m->tipo, $m->status, $m->setor_atual, $m->osc_id]);
        $this->assertMatchesRegularExpression('#^\d{4}/\d{4}$#', $m->protocolo);
        $this->assertTrue($m->ehPmis());
        Storage::disk('local')->assertExists($m->arquivo_path);

        // Protocolo e link pessoal por e-mail; a UG da Secretaria escolhida recebe na caixa.
        Mail::assertQueued(Aviso::class, fn ($mail) => $mail->hasTo('coletivo@example.com')
            && str_contains($mail->assunto, $m->protocolo) && $mail->url === route('manifestacao.acompanhar', $m->token));
        $this->assertSame('Manifestação nº ' . $m->protocolo, CaixaDeEntrada::para($this->ug)->itens->sole()['titulo']);

        $this->get(route('manifestacao.acompanhar', $m->token))->assertOk()
            ->assertSee('Protocolo nº ' . $m->protocolo)->assertSee('Reforço escolar')->assertSee('Em análise');
        $this->get(route('manifestacao.acompanhar.arquivo', $m->token))->assertOk()->assertDownload('proposta.pdf');
        $this->get(route('manifestacao.acompanhar', 'chave-errada'))->assertNotFound();
    }

    public function test_todos_os_campos_sao_obrigatorios_e_o_arquivo_e_pdf_de_ate_25_mb(): void
    {
        $this->post(route('manifestacao.publica.store'), [])->assertSessionHasErrors([
            'proponente_nome', 'proponente_tipo', 'proponente_email', 'orgao_id',
            'interesse_publico', 'diagnostico', 'viabilidade', 'arquivo',
        ]);
        $this->enviar(['arquivo' => UploadedFile::fake()->create('proposta.docx', 10)])->assertSessionHasErrors('arquivo');
        $this->enviar(['arquivo' => UploadedFile::fake()->create('grande.pdf', 26000, 'application/pdf')])->assertSessionHasErrors('arquivo');
        $this->assertSame(0, ManifestacaoInteresse::count());
    }

    public function test_o_recaptcha_e_conferido_no_google(): void
    {
        config(['services.recaptcha.site_key' => 'site', 'services.recaptcha.secret' => 'segredo']);
        Http::fake(['www.google.com/recaptcha/*' => Http::sequence()
            ->push(['success' => false])
            ->push(['success' => true])]);

        $this->get(route('manifestacao.publica.create'))->assertSee('g-recaptcha', false);
        $this->enviar()->assertSessionHasErrors('g-recaptcha-response');
        $this->enviar(['g-recaptcha-response' => 'robo'])->assertSessionHasErrors('g-recaptcha-response');
        $this->enviar(['g-recaptcha-response' => 'gente'])->assertSessionHasNoErrors();
        $this->assertSame(1, ManifestacaoInteresse::count());
    }

    public function test_a_osc_logada_usa_o_mesmo_formulario_e_fica_vinculada(): void
    {
        $osc = Osc::forceCreate(['name' => 'Associação Viver', 'cnpj' => '11.111.111/0001-11', 'email' => 'viver@example.com']);
        $rl = User::factory()->create(['osc_id' => $osc->id, 'setor' => 'osc', 'status' => true, 'approval_status' => 'aprovado']);
        $rl->assignRole('responsavel_legal');
        $osc->forceFill(['user_id' => $rl->id])->save();

        $this->actingAs($rl)->get(route('manifestacao.publica.create'))->assertOk()->assertSee('Associação Viver');
        $this->actingAs($rl)->post(route('manifestacao.publica.store'), $this->dados([
            'proponente_nome' => 'ignorado', 'proponente_tipo' => 'pf', 'proponente_email' => 'viver@example.com',
        ]))->assertSessionHasNoErrors();

        $m = ManifestacaoInteresse::sole();
        $this->assertSame([$osc->id, 'Associação Viver', 'pj'], [$m->osc_id, $m->proponente_nome, $m->proponente_tipo]);
        // Na lista da OSC, abrir leva ao acompanhamento.
        $this->actingAs($rl)->get(route('portal.minhas-propostas'))->assertSee('Manifestação nº ' . $m->protocolo);
        $this->actingAs($rl)->get(route('portal.manifestacoes.show', $m))->assertRedirect(route('manifestacao.acompanhar', $m->token));
    }

    public function test_a_lista_publica_mostra_os_textos_sem_email_nem_pdf(): void
    {
        $this->enviar();
        $m = ManifestacaoInteresse::sole();
        [$ano, $numero] = explode('/', $m->protocolo);

        $this->get(route('manifestacao.publica.index'))->assertOk()
            ->assertSee('Protocolo nº ' . $m->protocolo)->assertSee('Coletivo Bairro Vivo')->assertSee('Reforço escolar')
            ->assertDontSee('coletivo@example.com');
        $this->get(route('manifestacao.publica.show', [$ano, $numero]))->assertOk()
            ->assertSee('Evasão alta')->assertSee('Doze meses')
            ->assertDontSee('coletivo@example.com')->assertDontSee($m->token)->assertDontSee('proposta.pdf');
    }

    public function test_a_ug_opina_a_scp_decide_e_o_proponente_recebe_a_decisao(): void
    {
        $this->enviar();
        $m = ManifestacaoInteresse::sole();

        $this->actingAs($this->ug)->get(route('manifestacoes.show', $m))->assertOk()
            ->assertSee('Coletivo Bairro Vivo')->assertSee('coletivo@example.com')->assertSee('Baixar a proposta em PDF');
        $this->actingAs($this->ug)->get(route('manifestacoes.arquivo', $m))->assertOk()->assertDownload('proposta.pdf');
        $this->actingAs($this->ug)->post(route('manifestacoes.parecer', $m), ['parecer_favoravel' => 0, 'parecer_ug' => 'Sem orçamento.'])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->scp)->get(route('manifestacoes.show', $m))->assertOk()->assertSee('Acatar')->assertSee('Não acatar');
        $this->actingAs($this->scp)->post(route('manifestacoes.indeferir', $m), ['decisao_motivo' => 'Não há orçamento neste exercício.'])
            ->assertSessionHasNoErrors();

        Mail::assertQueued(Aviso::class, fn ($mail) => $mail->hasTo('coletivo@example.com') && str_contains($mail->assunto, 'não acatada'));
        $this->get(route('manifestacao.acompanhar', $m->token))->assertSee('Não acatada')->assertSee('Não há orçamento');
    }

    public function test_o_envio_tem_limite_por_endereco(): void
    {
        foreach (range(1, 5) as $i) {
            $this->enviar(['proponente_email' => "p{$i}@example.com"])->assertSessionHasNoErrors();
        }
        $this->enviar()->assertStatus(429);
        $this->assertSame(5, ManifestacaoInteresse::count());
    }
}
