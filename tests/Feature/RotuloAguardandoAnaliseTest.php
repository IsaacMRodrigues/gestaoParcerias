<?php

namespace Tests\Feature;

use App\Models\ManifestacaoInteresse;
use Tests\TestCase;

/**
 * Homologação, item 8: o status "Aguardando o SCP" passa a se chamar
 * "Aguardando análise" — só o rótulo; a chave interna continua `submetida`.
 */
class RotuloAguardandoAnaliseTest extends TestCase
{
    public function test_so_o_rotulo_muda_e_a_chave_continua(): void
    {
        $this->assertArrayHasKey('submetida', ManifestacaoInteresse::STATUS);
        $this->assertSame('Aguardando análise', ManifestacaoInteresse::STATUS['submetida']);
        $this->assertNotContains('Aguardando o SCP', ManifestacaoInteresse::STATUS);

        $antiga = new ManifestacaoInteresse(['status' => 'submetida']);
        $this->assertSame('Aguardando análise', $antiga->statusLabel());
    }
}
