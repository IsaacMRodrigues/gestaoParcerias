<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ferramentas de acessibilidade em todas as telas, como no Agiliza: "Pular para o conteúdo", o menu (texto e alto
 * contraste), os atalhos de 0 a 7, o VLibras, a página de ajuda e o mapa do sítio.
 */
class AcessibilidadeTest extends TestCase
{
    use RefreshDatabase;

    private function temAsFerramentas($resposta): void
    {
        $resposta->assertOk()
            ->assertSee('css/acessibilidade.css', false)
            ->assertSee('Pular para o conteúdo')
            ->assertSee('data-a11y-conteudo', false)
            ->assertSee('data-a11y-menu', false)
            ->assertSee('data-a11y="contraste-mais"', false)
            ->assertSee('vlibras.gov.br/app/vlibras-plugin.js', false)
            ->assertSee(route('acessibilidade'), false);
    }

    public function test_as_ferramentas_estao_em_todas_as_entradas(): void
    {
        // Tela inicial (HTML próprio), entrada (guest), portal público e área interna.
        foreach (['/', '/login', '/portal'] as $url) {
            $this->temAsFerramentas($this->get($url));
        }
        $this->get('/')->assertSee('Aviso de cookies');

        $this->seed(RolesSeeder::class);
        $scp = User::factory()->create(['setor' => 'scp', 'status' => true, 'approval_status' => 'aprovado']);
        $scp->assignRole('analista_tecnico_scp');
        $this->temAsFerramentas($this->actingAs($scp)->get('/dashboard'));
    }

    public function test_a_ajuda_traz_os_atalhos_e_o_mapa_do_sitio_os_caminhos(): void
    {
        $this->get('/acessibilidade')->assertOk()->assertSee('Atalhos de teclado')->assertSee('Ir para o conteúdo')
            ->assertSee('Abrir o mapa do sítio')->assertSee('VLibras')->assertSee('e-MAG');
        $this->get('/mapa-do-sitio')->assertOk()->assertSee(route('manifestacao.publica.create'), false)
            ->assertSee(route('validacao.index'), false)->assertSee(route('termos.index'), false)->assertDontSee('Sua área');
    }

    public function test_o_qr_code_mantem_o_fundo_branco_no_alto_contraste(): void
    {
        $this->assertStringContainsString('data-a11y-preservar', file_get_contents(resource_path('views/processos/_carimbo.blade.php')));
        $css = file_get_contents(public_path('css/acessibilidade.css'));
        $this->assertStringContainsString('html.a11y-contraste body [data-a11y-preservar]', $css);
        $this->assertStringContainsString('html.a11y-contraste body *:not([vw], [vw] *)', $css, 'o VLibras fica com o próprio visual');
    }
}
