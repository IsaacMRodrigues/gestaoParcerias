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
        Schema::create('chamamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programa_id')->constrained('programas')->restrictOnDelete();
            $table->string('numero', 50)->nullable();
            $table->string('titulo');
            $table->text('objeto');
            $table->string('tipo');
            $table->decimal('valor_disponivel', 15, 2)->nullable();
            $table->date('data_publicacao')->nullable();
            $table->date('data_inicio_inscricao')->nullable();
            $table->date('data_fim_inscricao')->nullable();
            $table->date('data_resultado')->nullable();
            $table->text('requisitos')->nullable();
            $table->string('status')->default('rascunho');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chamamentos');
    }
};
