<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Histórico de movimentações da Seleção (espelha `tramitacoes`, que é do
 * Processo): quem encaminhou, para qual setor, quando e com qual parecer —
 * inclusive o motivo das devoluções.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('selecao_tramitacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chamamento_id')->constrained('chamamentos')->cascadeOnDelete();
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
        Schema::dropIfExists('selecao_tramitacoes');
    }
};
