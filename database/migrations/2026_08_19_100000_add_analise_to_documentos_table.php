<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Análise dos documentos da proposta.
 *
 * Quem envia é a OSC — são os documentos DELA (estatuto, certidões, ata). O
 * papel do município é conferir: baixar e decidir. Faltava onde registrar essa
 * decisão, então o servidor só tinha "Remover", que apaga a prova em vez de
 * recusá-la e não deixa rastro de quem recusou nem por quê.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documentos', function (Blueprint $table) {
            $table->string('analise_status', 20)->default('pendente')->after('mime_type');
            $table->foreignId('analisado_por')->nullable()->after('analise_status')
                  ->constrained('users')->nullOnDelete();
            $table->timestamp('analisado_em')->nullable()->after('analisado_por');
            // Recusa sem motivo obriga a OSC a adivinhar o que corrigir.
            $table->text('analise_motivo')->nullable()->after('analisado_em');
        });
    }

    public function down(): void
    {
        Schema::table('documentos', function (Blueprint $table) {
            $table->dropForeign(['analisado_por']);
            $table->dropColumn(['analise_status', 'analisado_por', 'analisado_em', 'analise_motivo']);
        });
    }
};
