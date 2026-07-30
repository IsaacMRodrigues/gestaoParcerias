<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Histórico de movimentações da Celebração (espelha `selecao_tramitacoes`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('celebracao_tramitacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposta_id')->constrained('propostas')->cascadeOnDelete();
            $table->string('de_setor', 20);
            $table->string('para_setor', 20);
            $table->foreignId('enviado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('enviado_em')->nullable();
            $table->text('parecer')->nullable();
            $table->string('status', 20)->default('enviado'); // enviado | devolvido | concluido
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('celebracao_tramitacoes');
    }
};
