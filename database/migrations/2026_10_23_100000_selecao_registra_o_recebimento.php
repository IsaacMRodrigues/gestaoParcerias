<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A Seleção passa a registrar o recebimento, como o Processo: o setor que recebe confirma antes de atuar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('selecao_tramitacoes', function (Blueprint $table) {
            $table->foreignId('recebido_por')->nullable()->after('enviado_em')->constrained('users')->nullOnDelete();
            $table->timestamp('recebido_em')->nullable()->after('recebido_por');
        });

        // Entre etapas do mesmo setor a Seleção não muda de mãos: já chegou recebida.
        DB::table('selecao_tramitacoes')->whereColumn('de_setor', 'para_setor')
            ->update(['recebido_por' => DB::raw('enviado_por'), 'recebido_em' => DB::raw('enviado_em')]);
    }

    public function down(): void
    {
        Schema::table('selecao_tramitacoes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recebido_por');
            $table->dropColumn('recebido_em');
        });
    }
};
