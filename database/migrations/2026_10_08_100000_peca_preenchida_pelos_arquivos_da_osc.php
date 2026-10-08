<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Item da Celebração preenchido com a cópia de um arquivo de "Arquivos da OSC": a versão de
 * onde veio, para acompanhar a nova versão ou sair quando a UG a recusa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pecas', function (Blueprint $table) {
            $table->foreignId('osc_arquivo_id')->nullable()->after('origem_processo_peca_id')->constrained('osc_arquivos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pecas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('osc_arquivo_id');
        });
    }
};
