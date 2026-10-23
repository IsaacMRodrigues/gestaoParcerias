<?php

use App\Models\Processo;
use App\Models\ProcessoPeca;
use Illuminate\Database\Migrations\Migration;

/**
 * O Parecer Técnico Contábil entra na etapa da SEPLAN, ao lado do Parecer Financeiro. Ganham a peça os
 * processos que ainda não passaram dessa etapa; nos demais ela não tem mais vez.
 */
return new class extends Migration
{
    public function up(): void
    {
        $etapa = ProcessoPeca::ETAPA['parecer_contabil'];

        Processo::where('etapa', '<=', $etapa)->where('status', '!=', 'concluido')
            ->whereDoesntHave('pecas', fn ($q) => $q->where('tipo', 'parecer_contabil'))
            ->get()
            ->each(fn (Processo $processo) => $processo->pecas()->create([
                'tipo'     => 'parecer_contabil',
                'conteudo' => ProcessoPeca::conteudoInicial('parecer_contabil', $processo),
            ]));
    }

    public function down(): void
    {
        ProcessoPeca::where('tipo', 'parecer_contabil')->delete();
    }
};
