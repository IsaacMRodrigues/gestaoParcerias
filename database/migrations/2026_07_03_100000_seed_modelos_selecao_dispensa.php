<?php

use App\Models\Peca;
use App\Models\Processo;
use App\Models\ProcessoPeca;
use Illuminate\Database\Migrations\Migration;

/**
 * Semeia os textos-modelo oficiais adicionados pelo cliente (modelos VII–XI):
 *  - Seleção 2.2 (Peca::MODELO): Justificativa, Parecer CNAS, Parecer da UG,
 *    Certidão de Autuação e Protocolo ao Jurídico — preenche as peças vazias;
 *  - Trâmite (ProcessoPeca): re-semeia a Justificativa (VIII) e o Parecer CNAS
 *    (IX) dos processos que ainda tinham o texto-placeholder e não foram assinados.
 * Idempotente e conservador: nunca sobrescreve conteúdo assinado ou já editado.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) Seleção 2.2 (motor Peca) — preenche peças "modelo" vazias
        Peca::where('categoria', 'dispensa_inexigibilidade')
            ->where('tipo', 'modelo')
            ->where(fn ($q) => $q->whereNull('conteudo')->orWhere('conteudo', ''))
            ->get()
            ->each(function (Peca $peca) {
                $modelo = Peca::MODELO['dispensa_inexigibilidade'][$peca->chave] ?? null;
                if ($modelo !== null) {
                    $peca->update(['conteudo' => $modelo]);
                }
            });

        // 2) Trâmite (ProcessoPeca) — re-semeia Justificativa/Parecer CNAS que
        //    ainda tinham o placeholder antigo (não assinados)
        ProcessoPeca::whereIn('tipo', ['justificativa_dispensa', 'parecer_cnas'])
            ->whereNull('assinado_em')
            ->with('processo')
            ->get()
            ->each(function (ProcessoPeca $peca) {
                $marcadorAntigo = str_contains($peca->conteudo ?? '', 'I — DO OBJETO')
                    || str_contains($peca->conteudo ?? '', 'PARECER TÉCNICO — CONSELHO');
                if (($marcadorAntigo || empty($peca->conteudo)) && $peca->processo) {
                    $peca->update([
                        'conteudo' => ProcessoPeca::conteudoInicial($peca->tipo, $peca->processo),
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Não reverte o conteúdo (poderia apagar edições do usuário).
    }
};
