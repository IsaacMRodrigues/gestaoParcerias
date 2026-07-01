<?php

use App\Models\Processo;
use App\Models\ProcessoPeca;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Insere o Jurídico no trâmite do Planejamento (8 -> 9 etapas):
 *   6 (UG)  Assina o Edital + Solicitação de Parecer Jurídico
 *   7 (PJ)  Parecer Jurídico
 *   8 (SCP) Publicação.
 *
 * Ajusta os processos já existentes (criados no fluxo antigo de 8 etapas):
 *  - quem estava na antiga última etapa (7 = SCP publica) passa para a nova última (8);
 *  - cria as duas peças novas (solicitação + parecer) já preenchidas com o modelo.
 */
return new class extends Migration
{
    private const NOVAS_PECAS = ['solicitacao_parecer_juridico', 'parecer_juridico'];

    public function up(): void
    {
        // Antiga última etapa (publicação) era o índice 7; agora é o 8.
        DB::table('processos')->where('etapa', 7)->update(['etapa' => 8]);

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

        // Volta a publicação para o índice antigo (7).
        DB::table('processos')->where('etapa', 8)->update(['etapa' => 7]);
    }
};
