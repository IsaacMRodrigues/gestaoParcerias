<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Liga o Chamamento ao Processo de planejamento que o originou.
 * Ao concluir o trâmite, o sistema gera o Chamamento (publicação) automaticamente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chamamentos', function (Blueprint $table) {
            $table->foreignId('processo_id')
                ->nullable()
                ->after('programa_id')
                ->constrained('processos')
                ->nullOnDelete();
            $table->unique('processo_id');
        });
    }

    public function down(): void
    {
        Schema::table('chamamentos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('processo_id');
        });
    }
};
