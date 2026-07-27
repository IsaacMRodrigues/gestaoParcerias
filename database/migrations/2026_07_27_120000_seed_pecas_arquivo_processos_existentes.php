<?php

use App\Models\Processo;
use App\Models\ProcessoPeca;
use Illuminate\Database\Migrations\Migration;

/**
 * Cria as peças do tipo ARQUIVO (Portaria da Comissão de Seleção e Comprovante
 * de Publicação) nos processos já abertos antes de elas existirem. Nos processos
 * novos elas já nascem pelo ProcessoController::store (que percorre todos os TIPOS).
 *
 * Peças ARQUIVO não têm modelo de texto — o conteúdo fica nulo e o documento é
 * anexado depois (ver ProcessoPeca::ARQUIVO / anexos()).
 */
return new class extends Migration
{
    private const NOVAS_PECAS = ['portaria_comissao', 'comprovante_publicacao'];

    public function up(): void
    {
        Processo::query()->each(function (Processo $processo) {
            $existentes = $processo->pecas()->pluck('tipo')->all();

            foreach (self::NOVAS_PECAS as $tipo) {
                if (! in_array($tipo, $existentes, true)) {
                    $processo->pecas()->create([
                        'tipo'     => $tipo,
                        'conteudo' => ProcessoPeca::conteudoInicial($tipo, $processo),
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        ProcessoPeca::whereIn('tipo', self::NOVAS_PECAS)->delete();
    }
};
