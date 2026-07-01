<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modalidade da seleção, decidida pelo SCP na etapa de análise:
 * Chamamento Público, Dispensa ou Inexigibilidade de Chamamento Público.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('processos', function (Blueprint $table) {
            $table->string('modalidade')->nullable()->after('esfera');
        });
    }

    public function down(): void
    {
        Schema::table('processos', function (Blueprint $table) {
            $table->dropColumn('modalidade');
        });
    }
};
