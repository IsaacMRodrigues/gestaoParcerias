<?php

namespace Tests\Feature\Concerns;

use App\Models\Osc;
use App\Models\OscArquivo;
use App\Models\OscArquivoAnalise;
use App\Models\Proposta;

/**
 * "Arquivos da OSC" completos e em dia: sem eles, a OSC não envia manifestação, Nova Proposta nem
 * inscrição, e não passa da sua etapa na Celebração. A UG os aprova em cada parceria.
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

    /** A UG aprova, nesta parceria, a versão atual de cada arquivo da OSC. */
    protected function aprovarArquivosDaOsc(Proposta $proposta): void
    {
        foreach ($proposta->osc->arquivosAtuais() as $arquivo) {
            OscArquivoAnalise::updateOrCreate(
                ['proposta_id' => $proposta->id, 'osc_arquivo_id' => $arquivo->id],
                ['situacao' => 'aprovado', 'analisado_em' => now()],
            );
        }
    }
}
