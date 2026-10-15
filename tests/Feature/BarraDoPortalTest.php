<?php

namespace Tests\Feature;

use App\Models\Osc;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * O menu do portal da OSC logada: na barra lateral à esquerda (13/10/2026), em seções — participar,
 * propor parceria, execução, organização e ajuda. O visitante segue com o cabeçalho de cima.
 */
class BarraDoPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_osc_ve_os_grupos_e_todos_os_destinos(): void
    {
        $this->seed(RolesSeeder::class);
        $osc = Osc::forceCreate(['name' => 'OSC', 'cnpj' => '11.111.111/0001-11', 'email' => 'osc@example.com']);
        $rl = User::factory()->create(['osc_id' => $osc->id, 'setor' => 'osc', 'status' => true, 'approval_status' => 'aprovado']);
        $rl->assignRole('responsavel_legal');
        $osc->forceFill(['user_id' => $rl->id])->save();

        $html = $this->actingAs($rl)->get('/portal/minhas-propostas')->assertOk()->getContent();
        $this->assertStringNotContainsString('<nav class="hidden lg:flex', $html, 'sem a barra de cima');
        $lateral = substr($html, strpos($html, '<aside'));
        $barra = substr($lateral, strpos($lateral, '<nav'));
        $barra = substr($barra, 0, strpos($barra, '</nav>'));

        foreach (['Chamamentos abertos', 'Minhas inscrições', 'Propor parceria', 'Manifestar interesse', 'Nova Proposta',
                  'Execução', 'Alterações', 'Prestação de contas', 'Arquivos da OSC', 'Suporte'] as $rotulo) {
            $this->assertStringContainsString($rotulo, $barra, $rotulo);
        }
        $this->assertStringNotContainsString('Transparência', $barra, 'para a OSC, a Transparência fica no rodapé');
        $this->assertStringContainsString('lg:pl-64', $html, 'o conteúdo abre espaço para a barra lateral');
    }

    public function test_na_tela_de_uma_nova_proposta_o_menu_marca_nova_proposta(): void
    {
        $this->seed(RolesSeeder::class);
        $osc = Osc::forceCreate(['name' => 'OSC', 'cnpj' => '11.111.111/0001-11', 'email' => 'osc@example.com']);
        $rl = User::factory()->create(['osc_id' => $osc->id, 'setor' => 'osc', 'status' => true, 'approval_status' => 'aprovado']);
        $rl->assignRole('responsavel_legal');
        $osc->forceFill(['user_id' => $rl->id])->save();
        $m = \App\Models\ManifestacaoInteresse::forceCreate(['osc_id' => $osc->id, 'tipo' => 'proposta', 'titulo' => 'Reforço', 'objeto' => 'x',
            'justificativa' => 'x', 'valor_solicitado' => 1, 'status' => 'rascunho']);

        $html = $this->actingAs($rl)->get("/portal/manifestacoes/{$m->id}")->assertOk()->getContent();
        $ativo = fn (string $rotulo) => (bool) preg_match('#<a href="[^"]*"\s+class="[^"]*bg-brand-600[^"]*">\s*<svg[^>]*>.*?</svg>\s*' . preg_quote($rotulo, '#') . '\s*</a>#s', $html);
        $this->assertTrue($ativo('Nova Proposta'), 'marca Nova Proposta');
        $this->assertFalse($ativo('Manifestar interesse'), 'não marca Manifestar interesse');
    }

    public function test_quem_nao_esta_logado_ve_a_transparencia(): void
    {
        $this->get('/portal')->assertOk()->assertSee('Transparência')->assertDontSee('Propor parceria');
    }
}
