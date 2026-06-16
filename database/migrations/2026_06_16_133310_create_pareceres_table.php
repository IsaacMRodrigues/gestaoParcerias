<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pareceres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposta_id')->constrained('propostas')->cascadeOnDelete();
            $table->string('tipo');         // tecnico | juridico | decisao
            $table->string('resultado');    // aprovado | aprovado_ressalvas | reprovado | diligencia
            $table->text('texto');
            $table->string('responsavel')->nullable();
            $table->date('data_parecer');
            $table->text('observacoes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pareceres');
    }
};
