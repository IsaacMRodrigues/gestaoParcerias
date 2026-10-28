<?php

namespace App\Console\Commands;

use App\Support\Avisos;
use App\Support\TrilhaDeAssinatura;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/** Confere a trilha das assinaturas e a integridade dos textos assinados; o último elo vai ao log todo dia. */
class VerificarAssinaturas extends Command
{
    protected $signature = 'assinaturas:verificar';

    protected $description = 'Confere a trilha de auditoria das assinaturas e se os textos assinados continuam iguais';

    public function handle(): int
    {
        $resultado = TrilhaDeAssinatura::verificar();

        if ($resultado['problemas'] === []) {
            Log::info('Trilha das assinaturas íntegra', ['eventos' => $resultado['eventos'], 'ultimo_hash' => $resultado['ultimo']]);
            $this->info("Trilha íntegra: {$resultado['eventos']} evento(s), último elo {$resultado['ultimo']}.");

            return self::SUCCESS;
        }

        Log::critical('Trilha das assinaturas com problema', $resultado);
        foreach ($resultado['problemas'] as $problema) {
            $this->error($problema);
        }
        Avisos::trilhaDeAssinaturasComProblema($resultado['problemas']);

        return self::FAILURE;
    }
}
