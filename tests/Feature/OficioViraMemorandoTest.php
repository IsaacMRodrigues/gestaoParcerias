<?php

namespace Tests\Feature;

use App\Models\Processo;
use App\Models\ProcessoPeca;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * "Ofício" passou a se chamar "Memorando" em todo o sistema (28/09/2026).
 */
class OficioViraMemorandoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Nenhum "ofício" no que se lê: modelos, rótulos, telas, mensagens. Os
     * identificadores internos (`oficio`, `oficio_pedido`…) são sem acento e
     * não aparecem na tela — ficam.
     */
    public function test_o_codigo_nao_tem_mais_oficio_escrito(): void
    {
        $achados = [];
        foreach (['app', 'resources/views', 'routes', 'config'] as $pasta) {
            $arquivos = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path($pasta)));
            foreach ($arquivos as $arquivo) {
                if (!str_ends_with($arquivo, '.php')) {
                    continue;
                }
                foreach (file($arquivo) as $n => $linha) {
                    if (preg_match('/of(í|&iacute;)cio/iu', $linha)) {
                        $achados[] = str_replace(base_path() . '/', '', $arquivo) . ':' . ($n + 1);
                    }
                }
            }
        }

        $this->assertSame([], $achados, 'Ainda há "ofício" escrito em: ' . implode(', ', $achados));
    }

    public function test_a_peca_do_planejamento_se_chama_memorando(): void
    {
        $this->assertSame('Memorando', ProcessoPeca::TIPOS['oficio']);
        $this->assertStringContainsString('MEMORANDO PARA SOLICITAÇÃO', ProcessoPeca::MODELO['oficio']);
    }

    public function test_a_migracao_troca_os_documentos_gravados_e_desfazer_restaura(): void
    {
        $migracao = require database_path('migrations/2026_09_28_110000_oficio_passa_a_se_chamar_memorando.php');
        Schema::dropIfExists('backup_oficio_memorando'); // já rodou no RefreshDatabase; roda de novo sobre dados

        $u = User::factory()->create();
        $processo = Processo::forceCreate(['numero' => '1', 'orgao_id' => DB::table('orgaos')->insertGetId(['name' => 'X']), 'created_by' => $u->id, 'setor_atual' => 'ug']);
        $original = '<p>OFÍCIO PARA SOLICITAÇÃO</p><p>Of&iacute;cio nº 1/2026 — o ofício segue.</p>';
        $peca = ProcessoPeca::forceCreate(['processo_id' => $processo->id, 'tipo' => 'oficio', 'conteudo' => $original,
            'assinado_em' => now(), 'assinado_por' => $u->id]);

        $migracao->up();
        $this->assertSame('<p>MEMORANDO PARA SOLICITAÇÃO</p><p>Memorando nº 1/2026 — o memorando segue.</p>', $peca->fresh()->conteudo,
            'troca as três grafias, inclusive a codificada, e também no documento assinado');
        $this->assertSame($original, DB::table('backup_oficio_memorando')->where('registro_id', $peca->id)->value('valor_original'));

        $migracao->down();
        $this->assertSame($original, $peca->fresh()->conteudo, 'desfazer devolve o texto original');
    }

    // ── O título do memorando só cita parcerias ─────────────────────────

    public function test_o_titulo_do_memorando_nao_cita_convenios(): void
    {
        $this->assertStringContainsString('MEMORANDO PARA SOLICITAÇÃO DE PARCERIAS', ProcessoPeca::MODELO['oficio']);
        $this->assertStringNotContainsString('CONVÊNIOS', ProcessoPeca::MODELO['oficio']);
    }

    public function test_a_migracao_troca_o_titulo_gravado_e_desfazer_restaura(): void
    {
        $migracao = require database_path('migrations/2026_09_28_120000_titulo_do_memorando_so_parcerias.php');
        Schema::dropIfExists('backup_titulo_memorando');

        $u = User::factory()->create();
        $processo = Processo::forceCreate(['numero' => '1', 'orgao_id' => DB::table('orgaos')->insertGetId(['name' => 'X']), 'created_by' => $u->id, 'setor_atual' => 'ug']);
        $acentuado  = '<p><strong>MEMORANDO PARA SOLICITAÇÃO DE CONVÊNIOS/PARCERIAS</strong></p>';
        $codificado = '<p><strong>MEMORANDO PARA SOLICITA&Ccedil;&Atilde;O DE CONV&Ecirc;NIOS/PARCERIAS</strong></p><p>Setor de Convênios e Parcerias</p>';
        $a = ProcessoPeca::forceCreate(['processo_id' => $processo->id, 'tipo' => 'oficio', 'conteudo' => $acentuado]);
        $b = ProcessoPeca::forceCreate(['processo_id' => $processo->id, 'tipo' => 'abertura', 'conteudo' => $codificado,
            'assinado_em' => now(), 'assinado_por' => $u->id]);

        $migracao->up();
        $this->assertSame('<p><strong>MEMORANDO PARA SOLICITAÇÃO DE PARCERIAS</strong></p>', $a->fresh()->conteudo);
        $this->assertSame('<p><strong>MEMORANDO PARA SOLICITA&Ccedil;&Atilde;O DE PARCERIAS</strong></p><p>Setor de Convênios e Parcerias</p>',
            $b->fresh()->conteudo, 'a forma codificada também muda, e o nome do Setor de Convênios e Parcerias não');

        $migracao->down();
        $this->assertSame($acentuado, $a->fresh()->conteudo);
        $this->assertSame($codificado, $b->fresh()->conteudo);
    }
}
