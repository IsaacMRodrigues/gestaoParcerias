<?php

namespace Tests\Feature;

use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Cookies: só os necessários, um aviso que informa e some no "Entendi", e a política pública com a lista. */
class PoliticaDeCookiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_politica_lista_os_cookies_que_o_sistema_grava(): void
    {
        $this->get('/politica-de-cookies')->assertOk()
            ->assertSee(config('session.cookie'))->assertSee('XSRF-TOKEN')->assertSee('remember_web_')
            ->assertSee('pgp_aviso_cookies')->assertSee('reCAPTCHA')->assertSee('pgp:paleta:recentes')
            ->assertSee(config('session.lifetime') . ' minutos sem uso');
    }

    public function test_o_aviso_aparece_ate_o_entendi_em_todas_as_entradas(): void
    {
        foreach (['/login', '/portal', '/termos-de-uso', '/validar'] as $url) {
            $this->get($url)->assertOk()->assertSee('Aviso de cookies')->assertSee(route('cookies.index'), false);
        }

        // Depois do "Entendi" (o navegador grava o cookie, sem criptografia), o aviso não volta.
        $this->withUnencryptedCookie('pgp_aviso_cookies', '1')->get('/login')->assertOk()->assertDontSee('Aviso de cookies');
    }

    public function test_o_aviso_tambem_aparece_na_area_interna(): void
    {
        $this->seed(RolesSeeder::class);
        $scp = \App\Models\User::factory()->create(['setor' => 'scp', 'status' => true, 'approval_status' => 'aprovado']);
        $scp->assignRole('analista_tecnico_scp');

        $this->actingAs($scp)->get('/dashboard')->assertOk()->assertSee('Aviso de cookies')->assertSee(route('cookies.index'), false);
    }
}
