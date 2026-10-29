<?php

namespace Tests\Feature;

use App\Mail\Aviso;
use App\Models\AssinaturaEvento;
use App\Models\Chamamento;
use App\Models\Orgao;
use App\Models\Processo;
use App\Models\ProcessoPeca;
use App\Models\Peca;
use App\Models\Programa;
use App\Models\User;
use App\Support\CarimboDoTempo;
use App\Support\DocumentoPdf;
use App\Support\HoraLegal;
use App\Support\TrilhaDeAssinatura;
use Carbon\CarbonImmutable;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use LogicException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Assinatura eletrônica avançada (Lei 14.063/2020): senha no ato, hash SHA-256 do texto, carimbo do tempo da Hora
 * Legal (NTP.br), trilha de auditoria encadeada e validação pública — pelo código e pelo arquivo PDF.
 */
class AssinaturaEletronicaAvancadaTest extends TestCase
{
    use RefreshDatabase;

    private User $ug;
    private Processo $processo;
    private ProcessoPeca $memorando;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Mail::fake();

        $orgao = Orgao::forceCreate(['name' => 'Educação']);
        $this->ug = User::factory()->create(['setor' => 'ug', 'orgao_id' => $orgao->id, 'status' => true,
            'approval_status' => 'aprovado', 'cpf' => '123.456.789-09']);
        $this->ug->assignRole('responsavel_unidade_gestora');
        $this->processo = Processo::forceCreate(['numero' => '0207.0001.2026.01', 'orgao_id' => $orgao->id, 'created_by' => $this->ug->id,
            'status' => 'em_tramite', 'setor_atual' => 'ug', 'etapa' => 0]);
        $this->memorando = ProcessoPeca::forceCreate(['processo_id' => $this->processo->id, 'tipo' => 'oficio', 'conteudo' => '<p>Memorando nº 1</p>']);
    }

    private function assinar(?string $senha = 'password', ?ProcessoPeca $peca = null)
    {
        $peca ??= $this->memorando;

        return $this->actingAs($this->ug)->patch("/processos/{$this->processo->id}/pecas/{$peca->id}/assinar", ['senha_assinatura' => $senha]);
    }

    /** O NTP.br respondendo com este instante (UTC). */
    private function horaLegal(string $instante): void
    {
        $this->app->instance(HoraLegal::class, new class($instante) extends HoraLegal {
            public function __construct(private string $instante)
            {
            }

            public function agora(): CarimboDoTempo
            {
                return new CarimboDoTempo(CarbonImmutable::createFromFormat('Y-m-d H:i:s.u', $this->instante, 'UTC'), 'a.st1.ntp.br', -12);
            }
        });
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['setor' => 'ti', 'status' => true, 'approval_status' => 'aprovado']);
        $admin->assignRole('administrador_setorial');

        return $admin;
    }

    public function test_assinar_pede_a_senha_de_novo(): void
    {
        $this->assinar('errada')->assertSessionHas('error', 'Senha incorreta: o documento não foi assinado.');
        $this->assinar(null)->assertSessionHas('error');
        $this->assertNull($this->memorando->fresh()->assinado_em);
        $this->assertSame(0, AssinaturaEvento::count());

        // Volta aberto, com o motivo, no formulário recusado.
        $this->assinar('errada');
        $this->actingAs($this->ug)->get(route('processos.pecas.edit', [$this->processo, $this->memorando]))->assertOk()
            ->assertSee('Para assinar, digite a sua senha')->assertSee('Senha incorreta');

        $this->assinar()->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->assertNotNull($this->memorando->fresh()->assinado_em);
    }

    public function test_cinco_senhas_erradas_travam_a_assinatura(): void
    {
        foreach (range(1, 5) as $i) {
            $this->assinar('errada');
        }

        $this->assinar()->assertSessionHas('error', fn ($m) => str_contains($m, 'Muitas tentativas'));
        $this->assertNull($this->memorando->fresh()->assinado_em);
    }

    public function test_a_assinatura_grava_hash_carimbo_do_tempo_e_trilha(): void
    {
        $this->horaLegal('2026-10-28 13:30:05.250000');
        $this->assinar()->assertSessionHasNoErrors();

        $memorando = $this->memorando->fresh();
        $this->assertSame('2026-10-28 13:30:05', $memorando->assinado_em->format('Y-m-d H:i:s'), 'a hora da assinatura é a da Hora Legal');

        $e = AssinaturaEvento::sole();
        $this->assertSame(['assinatura', 'processo_peca', $memorando->id, $memorando->codigo_validacao, $this->ug->id],
            [$e->evento, $e->documento_type, (int) $e->documento_id, $e->codigo_validacao, (int) $e->user_id]);
        $this->assertSame(hash('sha256', '<p>Memorando nº 1</p>'), $e->hash_documento);
        $this->assertSame(['2026-10-28 13:30:05.250000', 'a.st1.ntp.br', -12], [$e->instante()->format('Y-m-d H:i:s.u'), $e->fonte_tempo, (int) $e->desvio_ms]);
        $this->assertSame(TrilhaDeAssinatura::AUTENTICACAO, $e->autenticacao);
        $this->assertSame('127.0.0.1', $e->ip);
        $this->assertSame('123.456.789-09', $e->dadosDeIdentidade()['cpf']);
        $this->assertArrayHasKey('navegador', $e->dadosDaRequisicao());
        $this->assertSame(AssinaturaEvento::GENESE, $e->hash_anterior);
        $this->assertTrue($e->integro());
        $this->assertSame([], TrilhaDeAssinatura::verificar()['problemas']);

        // O carimbo diz a hora de Brasília, a fonte e o código de integridade.
        $this->actingAs($this->ug)->get(route('processos.pecas.edit', [$this->processo, $memorando]))->assertOk()
            ->assertSee('10:30:05')->assertSee('Hora Legal Brasileira')->assertSee($e->hash_documento);
    }

    public function test_sem_resposta_do_ntp_vale_o_relogio_do_servidor_e_isso_fica_dito(): void
    {
        $this->assinar()->assertSessionHasNoErrors();

        $e = AssinaturaEvento::sole();
        $this->assertNull($e->fonte_tempo);
        $this->get('/validar/' . $e->codigo_validacao)->assertOk()->assertSee('a fonte oficial não respondeu no momento');
    }

    public function test_a_validacao_publica_confere_a_integridade_e_mostra_a_trilha_sem_dado_pessoal(): void
    {
        $this->horaLegal('2026-10-28 13:30:05.250000');
        $this->assinar();
        $codigo = $this->memorando->fresh()->codigo_validacao;

        $this->get("/validar/{$codigo}")->assertOk()
            ->assertSee('Documento autêntico e íntegro')->assertSee(hash('sha256', '<p>Memorando nº 1</p>'))
            ->assertSee('28/10/2026 às 10:30:05')->assertSee('Trilha de auditoria')->assertSee('✓ elo íntegro')
            ->assertSee('***.456.789-**')->assertDontSee('123.456.789-09')->assertDontSee('127.0.0.1');

        // A administração vê o IP e o navegador.
        $this->actingAs($this->admin())->get("/validar/{$codigo}")->assertOk()->assertSee('127.0.0.1');

        // Texto mexido direto no banco: a validação e a verificação diária acusam.
        DB::table('processo_pecas')->where('id', $this->memorando->id)->update(['conteudo' => '<p>Memorando nº 2</p>']);
        $this->get("/validar/{$codigo}")->assertOk()->assertSee('Documento inválido')->assertSee('Validação recusada')->assertDontSee('Documento autêntico e íntegro');
        $this->assertStringContainsString('o texto mudou depois da assinatura', implode(' ', TrilhaDeAssinatura::verificar()['problemas']));
    }

    public function test_mexer_na_trilha_quebra_a_corrente_e_avisa_a_administracao(): void
    {
        $admin = $this->admin();
        $outro = ProcessoPeca::forceCreate(['processo_id' => $this->processo->id, 'tipo' => 'despacho', 'conteudo' => '<p>Despacho</p>']);
        $this->assinar();
        $this->assinar('password', $outro);
        [$primeiro, $segundo] = AssinaturaEvento::orderBy('id')->get()->all();
        $this->assertSame($primeiro->hash, $segundo->hash_anterior);
        $this->artisan('assinaturas:verificar')->assertSuccessful();

        // Pelo Eloquent, a trilha não se altera nem se apaga.
        $this->assertThrows(fn () => $primeiro->forceFill(['ip' => '10.0.0.1'])->save(), LogicException::class);
        $this->assertThrows(fn () => $primeiro->delete(), LogicException::class);

        // Direto no banco: o evento alterado deixa de conferir e o aviso vai ao administrador.
        DB::table('assinatura_eventos')->where('id', $primeiro->id)->update(['ip' => '10.0.0.1']);
        $this->assertContains("Evento nº {$primeiro->id}: os dados do evento foram alterados depois de gravados.", TrilhaDeAssinatura::verificar()['problemas']);
        $this->artisan('assinaturas:verificar')->assertFailed();
        Mail::assertQueued(Aviso::class, fn ($m) => $m->hasTo($admin->email) && str_contains($m->assunto, 'Trilha de auditoria'));

        // Evento apagado: o seguinte perde o elo.
        DB::table('assinatura_eventos')->where('id', $primeiro->id)->delete();
        $this->assertContains("Evento nº {$segundo->id}: o elo com o evento anterior está quebrado (evento apagado ou inserido fora da ordem).",
            TrilhaDeAssinatura::verificar()['problemas']);
        $this->get('/validar/' . $segundo->codigo_validacao)->assertOk()->assertSee('⚠ elo quebrado');
    }

    public function test_devolver_anula_a_assinatura_na_trilha_e_o_codigo_antigo_diz_isso(): void
    {
        $this->assinar();
        $antigo = $this->memorando->fresh()->codigo_validacao;

        $this->actingAs($this->ug);
        $this->memorando->fresh()->reabrirPorDevolucao('O valor está errado.', 'ug', $this->ug->name);

        $anulacao = AssinaturaEvento::where('evento', 'anulacao')->sole();
        $this->assertSame([$antigo, 'O valor está errado.'], [$anulacao->codigo_validacao, $anulacao->dadosDoEvento()['motivo']]);
        $this->get("/validar/{$antigo}")->assertOk()->assertSee('Assinatura anulada')->assertDontSee('O valor está errado.');

        // Corrigido e assinado de novo: outro código, e o antigo aponta para ele.
        $this->assinar()->assertSessionHasNoErrors();
        $novo = $this->memorando->fresh()->codigo_validacao;
        $this->assertNotSame($antigo, $novo);
        $this->get("/validar/{$antigo}")->assertOk()->assertSee('Valide pelo código atual')->assertSee($novo);
        $this->assertSame(3, AssinaturaEvento::count());
        $this->assertSame([], TrilhaDeAssinatura::verificar()['problemas']);
    }

    public function test_dois_cliques_nao_assinam_duas_vezes(): void
    {
        TrilhaDeAssinatura::assinar($this->memorando, $this->ug);
        $codigo = $this->memorando->fresh()->codigo_validacao;

        $this->assertThrows(fn () => TrilhaDeAssinatura::assinar(ProcessoPeca::find($this->memorando->id), $this->ug),
            fn (HttpException $e) => $e->getStatusCode() === 409);
        $this->assertSame($codigo, $this->memorando->fresh()->codigo_validacao);
        $this->assertSame(1, AssinaturaEvento::count());
    }

    public function test_o_pdf_emitido_e_reconhecido_e_o_alterado_nao(): void
    {
        $this->assinar();
        $pdf = DocumentoPdf::gerar($this->memorando->fresh());
        $codigo = $this->memorando->fresh()->codigo_validacao;

        $this->post('/validar/arquivo', ['arquivo' => UploadedFile::fake()->createWithContent('memorando.pdf', $pdf)])->assertOk()
            ->assertSee('Arquivo autêntico e intacto')->assertSee($codigo)->assertSee('Memorando');
        $this->post('/validar/arquivo', ['arquivo' => UploadedFile::fake()->createWithContent('memorando.pdf', $pdf . ' ')])->assertOk()
            ->assertSee('Validação recusada')->assertDontSee('Arquivo autêntico');

        // Documento sem assinatura não entra no registro.
        DocumentoPdf::gerar(ProcessoPeca::forceCreate(['processo_id' => $this->processo->id, 'tipo' => 'despacho', 'conteudo' => '<p>Rascunho</p>']));
        $this->assertSame(1, DB::table('documentos_emitidos')->count());
    }

    public function test_as_assinaturas_anteriores_entram_na_trilha_uma_vez(): void
    {
        $this->horaLegal('2026-10-28 13:00:00.000000');
        $antiga = ProcessoPeca::forceCreate(['processo_id' => $this->processo->id, 'tipo' => 'despacho', 'conteudo' => '<p>Antigo</p>',
            'assinado_em' => '2026-09-01 15:00:00', 'assinado_por' => $this->ug->id, 'assinante_nome' => 'Fulana de Tal',
            'assinante_cargo' => 'Secretária', 'codigo_validacao' => 'ABCD-EFGH-IJ']);

        $this->assertSame(1, TrilhaDeAssinatura::registrarAnteriores());
        $this->assertSame(0, TrilhaDeAssinatura::registrarAnteriores());

        $e = AssinaturaEvento::sole();
        $this->assertSame(['registro_anterior', 'ABCD-EFGH-IJ', 'Fulana de Tal', '2026-09-01 15:00:00'],
            [$e->evento, $e->codigo_validacao, $e->dadosDeIdentidade()['nome'], $e->dadosDoEvento()['assinado_em']]);
        $this->assertSame(hash('sha256', '<p>Antigo</p>'), $e->hash_documento);
        $this->assertTrue(TrilhaDeAssinatura::integro($antiga, 'ABCD-EFGH-IJ'));
        // O registro saiu carimbado pelo NTP.br, mas a hora da assinatura antiga foi a do relógio do servidor.
        $this->assertSame('a.st1.ntp.br', $e->fonte_tempo);
        $this->get('/validar/ABCD-EFGH-IJ')->assertOk()->assertSee('Documento autêntico e íntegro')
            ->assertSee('assinatura anterior à trilha')->assertSee('01/09/2026 às 12:00:00')->assertDontSee('pelo NTP.br');
    }

    public function test_codigo_anulado_nao_volta_a_ser_sorteado(): void
    {
        $this->assinar();
        $antigo = $this->memorando->fresh()->codigo_validacao;
        $this->actingAs($this->ug);
        $this->memorando->fresh()->reabrirPorDevolucao('Correção.', 'ug', 'UG');

        // O código antigo só existe na trilha agora, e mesmo assim conta como usado.
        $this->assertFalse(ProcessoPeca::where('codigo_validacao', $antigo)->exists());
        $this->assertTrue(AssinaturaEvento::where('codigo_validacao', $antigo)->exists());
        $this->assertSame(1, AssinaturaEvento::where('codigo_validacao', $antigo)->where('evento', 'assinatura')->count());
    }

    public function test_texto_assinado_fora_do_tramite_nao_se_edita(): void
    {
        $programa = Programa::forceCreate(['orgao_id' => $this->ug->orgao_id, 'name' => 'P', 'tipo' => 'termo_fomento']);
        $chamamento = Chamamento::forceCreate(['programa_id' => $programa->id, 'numero' => '001/2026', 'titulo' => 'C',
            'objeto' => 'x', 'tipo' => 'chamamento_publico', 'status' => 'rascunho']);
        Peca::sincronizar($chamamento, 'chamamento_publico');
        // Uma peça de texto da fase do edital, que a UG preenche sem trâmite.
        $portaria = $chamamento->pecas()->get()->first(fn (Peca $p) => $p->tipo === 'modelo' && !$p->emTramite()
            && !$p->vemDoPlanejamento() && $p->podePreencher($this->ug));
        $this->assertNotNull($portaria, 'antes de assinar, a UG preenche');

        $portaria->forceFill(['conteudo' => '<p>Portaria</p>', 'assinado_em' => now(), 'assinado_por' => $this->ug->id])->save();

        $this->assertFalse($portaria->fresh()->podePreencher($this->ug));
        $this->actingAs($this->ug)->put("/pecas/{$portaria->id}", ['conteudo' => '<p>Outra portaria</p>'])->assertForbidden();
        $this->assertSame('<p>Portaria</p>', $portaria->fresh()->conteudo);
    }

    public function test_conta_sem_cpf_informa_o_cpf_ao_assinar(): void
    {
        $this->ug->forceFill(['cpf' => null])->save();
        $tela = fn () => $this->actingAs($this->ug)->get(route('processos.pecas.edit', [$this->processo, $this->memorando]));
        $tela()->assertOk()->assertSee('name="cpf_assinatura"', false);

        $this->assinar()->assertSessionHas('error', fn ($m) => str_contains($m, 'informe um CPF válido'));
        $this->actingAs($this->ug)->patch("/processos/{$this->processo->id}/pecas/{$this->memorando->id}/assinar",
            ['senha_assinatura' => 'password', 'cpf_assinatura' => '111.111.111-11'])->assertSessionHas('error');
        $this->assertNull($this->memorando->fresh()->assinado_em);

        // CPF de outra conta não serve.
        User::factory()->create(['cpf' => '529.982.247-25']);
        $this->actingAs($this->ug)->patch("/processos/{$this->processo->id}/pecas/{$this->memorando->id}/assinar",
            ['senha_assinatura' => 'password', 'cpf_assinatura' => '52998224725'])->assertSessionHas('error', fn ($m) => str_contains($m, 'outra conta'));

        $this->actingAs($this->ug)->patch("/processos/{$this->processo->id}/pecas/{$this->memorando->id}/assinar",
            ['senha_assinatura' => 'password', 'cpf_assinatura' => '11144477735'])->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->assertSame('111.444.777-35', $this->ug->fresh()->cpf);
        $this->assertSame('111.444.777-35', AssinaturaEvento::sole()->dadosDeIdentidade()['cpf']);
        $tela()->assertDontSee('name="cpf_assinatura"', false)->assertSee('***.444.777-**');
    }

    public function test_o_pdf_traz_a_barra_de_validacao_em_todas_as_paginas(): void
    {
        $this->horaLegal('2026-10-28 13:30:05.250000');
        $this->assertStringNotContainsString('barra-validacao"', DocumentoPdf::html($this->memorando), 'sem assinatura, sem barra');

        $this->assinar();
        $memorando = $this->memorando->fresh();
        $html = DocumentoPdf::html($memorando);

        // Fixa antes do conteúdo: o dompdf a repete em cada página, na margem de baixo.
        $this->assertMatchesRegularExpression('/<body>\s*(<!--.*?-->\s*)?<div class="barra-validacao">/s', $html);
        $this->assertStringContainsString('.barra-validacao { position: fixed;', $html);
        $barra = substr($html, strpos($html, '<div class="barra-validacao">'), 3000);
        foreach (['DOCUMENTO ASSINADO ELETRONICAMENTE', $this->ug->name, 'CPF ***.456.789-**', '28/10/2026 às 10:30:05',
                     'horário oficial de Brasília', $memorando->codigo_validacao, 'data:image/svg+xml;base64'] as $esperado) {
            $this->assertStringContainsString($esperado, $barra);
        }
        $this->assertStringNotContainsString('123.456.789-09', $html, 'o CPF não sai inteiro');

        // A impressão pelo navegador leva a mesma barra, e a tela do documento oferece o PDF do sistema.
        $this->actingAs($this->ug)->get(route('processos.pecas.imprimir', [$this->processo, $memorando]))->assertOk()
            ->assertSee('class="barra-validacao"', false)->assertSee('class="reserva-barra"', false)->assertSee('CPF ***.456.789-**');
        $pdf = route('processos.pecas.imprimir-lote', [$this->processo, 'pecas' => [$memorando->id]]);
        $this->actingAs($this->ug)->get(route('processos.pecas.edit', [$this->processo, $memorando]))->assertSee('Baixar PDF')->assertSee($pdf, false);
        $this->actingAs($this->ug)->get($pdf)->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_a_resposta_do_ntp_so_vale_se_for_desta_pergunta_e_de_relogio_sincronizado(): void
    {
        $t1 = 1_793_000_000.0;
        $enviado = HoraLegal::paraNtp($t1);
        // O servidor recebe e responde 2 s à frente do relógio local, com 100 ms de ida e volta.
        $resposta = fn (int $cabecalho, int $estrato, string $originate) => chr($cabecalho) . chr($estrato) . str_repeat("\0", 22)
            . $originate . HoraLegal::paraNtp($t1 + 2.05) . HoraLegal::paraNtp($t1 + 2.05);

        [$instante, $desvio] = HoraLegal::interpretar($resposta(0x24, 1, $enviado), $enviado, $t1, $t1 + 0.1);
        $this->assertEqualsWithDelta(2.0, $desvio, 0.001);
        $this->assertEqualsWithDelta($t1 + 2.1, $instante, 0.001);

        $this->assertNull(HoraLegal::interpretar($resposta(0x24, 1, HoraLegal::paraNtp($t1 - 5)), $enviado, $t1, $t1 + 0.1), 'outra pergunta');
        $this->assertNull(HoraLegal::interpretar($resposta(0xE4, 1, $enviado), $enviado, $t1, $t1 + 0.1), 'relógio não sincronizado');
        $this->assertNull(HoraLegal::interpretar($resposta(0x24, 0, $enviado), $enviado, $t1, $t1 + 0.1), 'servidor recusou');
        $this->assertNull(HoraLegal::interpretar($resposta(0x23, 1, $enviado), $enviado, $t1, $t1 + 0.1), 'não é resposta de servidor');
        $this->assertNull(HoraLegal::interpretar('curta', $enviado, $t1, $t1 + 0.1));
    }
}
