<?php

use App\Models\Processo;
use App\Models\ProcessoPeca;
use Illuminate\Database\Migrations\Migration;

/**
 * Rota da Dispensa/Inexigibilidade de Chamamento Público (Lei 13.019/2014, arts. 30–32).
 *
 * A partir da etapa 5, a rota deixa de ser Edital → Jurídico e passa a ser
 * Justificativa (UG) → Publicação (SCP), num total de 7 etapas (índices 0–6).
 *
 * Esta migration:
 *  - cria as duas peças novas (justificativa + parecer CNAS) nos processos já abertos;
 *  - remapeia os processos de dispensa/inexigibilidade que já haviam avançado no antigo
 *    fluxo de 9 etapas para a posição equivalente na rota curta (evita etapa fora do
 *    intervalo): a publicação final (8) vira a nova final (6, SCP); as etapas do
 *    Edital/Jurídico (5–7) recuam para a Justificativa (5, UG).
 */
return new class extends Migration
{
    private const NOVAS_PECAS = ['justificativa_dispensa', 'parecer_cnas'];

    public function up(): void
    {
        Processo::query()->each(function (Processo $processo) {
            // 1) cria as peças novas que faltarem (idempotente)
            $existentes = $processo->pecas()->pluck('tipo')->all();
            foreach (self::NOVAS_PECAS as $tipo) {
                if (! in_array($tipo, $existentes, true)) {
                    $processo->pecas()->create([
                        'tipo'     => $tipo,
                        'conteudo' => ProcessoPeca::conteudoInicial($tipo, $processo),
                    ]);
                }
            }

            // 2) realinha os processos de dispensa/inexigibilidade que ficaram além
            //    do fim da rota curta (etapa > 6) no fluxo antigo de chamamento
            if ($processo->ehDispensa() && $processo->etapa > 6) {
                if ($processo->etapa >= 8) {
                    // publicação final: 8 (chamamento) -> 6 (dispensa), setor SCP
                    $processo->update(['etapa' => 6, 'setor_atual' => 'scp']);
                } else {
                    // 5/6/7 (Edital/Jurídico) -> 5 (Justificativa), setor UG
                    $processo->update(['etapa' => 5, 'setor_atual' => 'ug']);
                }
            }
        });
    }

    public function down(): void
    {
        // O realinhamento de etapa é irreversível (lossy); apenas removemos as peças.
        ProcessoPeca::whereIn('tipo', self::NOVAS_PECAS)->delete();
    }
};
