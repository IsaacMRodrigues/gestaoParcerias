<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Etapa conjunta na Celebração (decisão da gestão, 30/09/2026): depois da
 * OSC, a etapa 3 fica com a UG e a SCP ao mesmo tempo, e só avança quando as
 * duas concluírem a sua parte. Aqui se guarda quem já concluiu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('propostas', function (Blueprint $table) {
            $table->json('celebracao_partes_concluidas')->nullable()->after('celebracao_setor');
        });
    }

    public function down(): void
    {
        Schema::table('propostas', fn (Blueprint $table) => $table->dropColumn('celebracao_partes_concluidas'));
    }
};
