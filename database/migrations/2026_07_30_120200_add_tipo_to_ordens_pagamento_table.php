<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A Ordem de Pagamento passa a ter tipo (Fluxo de Celebração do cliente):
 * GLOBAL  — solicita o empenho global do exercício (emitida uma vez);
 * PARCIAL — solicita o subempenho de cada parcela (várias durante a vigência).
 *
 * As ordens já existentes ficam como 'parcial', que é o uso corrente (repasses
 * por parcela) — o empenho global é emitido uma única vez, no início.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordens_pagamento', function (Blueprint $table) {
            $table->string('tipo', 20)->default('parcial')->after('numero');
        });
    }

    public function down(): void
    {
        Schema::table('ordens_pagamento', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
    }
};
