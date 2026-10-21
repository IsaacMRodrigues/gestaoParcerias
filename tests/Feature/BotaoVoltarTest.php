<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Toda tela tem o botão Voltar, menos os inícios (Painel e portal): ele volta à tela anterior do sistema
 * e, sem ela, vai ao início da área.
 */
class BotaoVoltarTest extends TestCase
{
    use RefreshDatabase;

    public function test_voltar_em_toda_tela_menos_nos_inicios(): void
    {
        $this->seed(RolesSeeder::class);
        $admin = User::factory()->create(['setor' => 'ti', 'status' => true, 'approval_status' => 'aprovado', 'deve_trocar_senha' => false]);
        $admin->assignRole('administrador_setorial');
        $botao = 'aria-label="Voltar à tela anterior"';

        $this->actingAs($admin)->get(route('dashboard'))->assertOk()->assertDontSee($botao, false);
        foreach (['orgaos.index', 'usuarios.create', 'caixa', 'chamamentos.index'] as $rota) {
            $this->actingAs($admin)->get(route($rota))->assertOk()->assertSee($botao, false)->assertSee('history.back()', false);
        }

        auth()->logout();
        $this->get(route('portal.index'))->assertOk()->assertDontSee($botao, false);
        $this->get(route('transparencia'))->assertOk()->assertSee($botao, false);
    }
}
