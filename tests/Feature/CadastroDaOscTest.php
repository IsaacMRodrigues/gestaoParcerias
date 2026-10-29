<?php

namespace Tests\Feature;

use App\Models\Osc;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** O cadastro público da OSC pede o "Não sou um robô" (reCAPTCHA), conferido no Google, como o formulário do PMIS. */
class CadastroDaOscTest extends TestCase
{
    use RefreshDatabase;

    private function dados(array $extra = []): array
    {
        return $extra + ['resp_nome' => 'Ana Responsável', 'email' => 'ana@example.com', 'cpf' => '529.982.247-25',
            'password' => 'senha123', 'password_confirmation' => 'senha123', 'osc_name' => 'Associação Viver',
            'cnpj' => '11.111.111/0001-11', 'tipo' => array_key_first(Osc::TIPOS), 'cidade' => 'São Gonçalo do Rio Abaixo',
            'estado' => 'MG', 'aceite_termos' => '1'];
    }

    public function test_o_cadastro_da_osc_confere_o_recaptcha_no_google(): void
    {
        $this->seed(RolesSeeder::class);
        config(['services.recaptcha.site_key' => 'site', 'services.recaptcha.secret' => 'segredo']);
        Http::fake(['www.google.com/recaptcha/*' => Http::sequence()->push(['success' => false])->push(['success' => true])]);

        $this->get('/cadastro/osc')->assertOk()->assertSee('class="g-recaptcha"', false)->assertSee('data-sitekey="site"', false);

        $this->post('/cadastro/osc', $this->dados())->assertSessionHasErrors('g-recaptcha-response');
        $this->post('/cadastro/osc', $this->dados(['g-recaptcha-response' => 'robo']))->assertSessionHasErrors('g-recaptcha-response');
        $this->assertFalse(User::where('email', 'ana@example.com')->exists());
        $this->assertGuest();

        $this->post('/cadastro/osc', $this->dados(['g-recaptcha-response' => 'gente']))->assertSessionHasNoErrors()
            ->assertRedirect(route('portal.index'));
        $this->assertSame('Associação Viver', Osc::sole()->name);
        Http::assertSent(fn ($r) => $r['secret'] === 'segredo' && $r['response'] === 'gente');
    }

    public function test_sem_chave_em_desenvolvimento_o_formulario_nao_mostra_o_widget(): void
    {
        $this->get('/cadastro/osc')->assertOk()->assertDontSee('class="g-recaptcha"', false);
    }
}
