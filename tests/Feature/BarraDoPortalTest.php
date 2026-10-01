<?php

namespace Tests\Feature;

use App\Models\Osc;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A barra do portal da OSC logada: nove links numa linha passavam da tela
 * (01/10/2026). Os de propor e os da execução viraram grupos.
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
        $barra = substr($html, strpos($html, '<nav class="hidden lg:flex'), 8000);
        $barra = substr($barra, 0, strpos($barra, '</nav>'));

        foreach (['Chamamentos abertos', 'Minhas inscrições', 'Propor parceria', 'Manifestar interesse', 'Nova Proposta',
                  'Execução', 'Alterações', 'Prestação de contas', 'Arquivos da OSC', 'Suporte'] as $rotulo) {
            $this->assertStringContainsString($rotulo, $barra, $rotulo);
        }
        $this->assertStringNotContainsString('Transparência', $barra, 'para a OSC, a Transparência fica no rodapé');
    }

    public function test_quem_nao_esta_logado_ve_a_transparencia(): void
    {
        $this->get('/portal')->assertOk()->assertSee('Transparência')->assertDontSee('Propor parceria');
    }
}
