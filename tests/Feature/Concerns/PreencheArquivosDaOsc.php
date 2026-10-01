<?php

namespace Tests\Feature\Concerns;

use App\Models\Osc;
use App\Models\OscArquivo;

/**
 * "Arquivos da OSC" completos e em dia (30/09/2026): sem eles, a OSC não envia
 * manifestação nem Nova Proposta e não passa da sua etapa na Celebração.
 */
trait PreencheArquivosDaOsc
{
    protected function preencherArquivosDaOsc(Osc|int $osc): void
    {
        $oscId = $osc instanceof Osc ? $osc->id : $osc;

        foreach (array_keys(OscArquivo::tipos()) as $tipo) {
            OscArquivo::forceCreate([
                'osc_id' => $oscId, 'tipo' => $tipo, 'versao' => 1,
                'arquivo_path' => "osc-arquivos/{$oscId}/{$tipo}.pdf", 'arquivo_nome' => "{$tipo}.pdf",
                'validade' => OscArquivo::exigeValidade($tipo) ? now()->addMonths(3)->toDateString() : null,
            ]);
        }
    }
}
