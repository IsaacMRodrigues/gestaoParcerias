<?php

namespace App\Console\Commands;

use App\Models\OscArquivo;
use App\Support\Avisos;
use Illuminate\Console\Command;

/** Avisa a OSC das certidões que vencem em breve: só a versão atual, uma vez por versão. */
class AvisarVencimentoDeCertidoes extends Command
{
    protected $signature = 'osc:avisar-vencimento-certidoes';

    protected $description = 'Avisa as OSCs das certidões que vencem nos próximos dias';

    public function handle(): int
    {
        $limite = now()->addDays(OscArquivo::DIAS_AVISO_VENCIMENTO)->toDateString();

        $candidatas = OscArquivo::with('osc')
            ->whereNotNull('validade')
            ->whereDate('validade', '<=', $limite)
            ->whereDate('validade', '>=', now()->toDateString())
            ->whereNull('aviso_vencimento_em')
            ->get()
            // Só a versão atual: a antiga já foi substituída.
            ->filter(fn (OscArquivo $a) => $a->osc && $a->osc->arquivosAtuais()->get($a->tipo)?->id === $a->id);

        foreach ($candidatas as $arquivo) {
            Avisos::certidaoVencendo($arquivo);
            $arquivo->forceFill(['aviso_vencimento_em' => now()])->save();
        }

        $this->info($candidatas->count() . ' aviso(s) de vencimento.');

        return self::SUCCESS;
    }
}
