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
        Schema::create('instrumentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposta_id')->unique()->constrained('propostas')->restrictOnDelete();
            $table->string('numero', 50);
            $table->string('tipo');
            $table->text('objeto');
            $table->decimal('valor_repasse', 15, 2);
            $table->decimal('valor_proprio', 15, 2)->default(0);
            $table->date('data_assinatura')->nullable();
            $table->date('data_inicio');
            $table->date('data_fim');
            $table->boolean('publicado_doe')->default(false);
            $table->date('data_publicacao_doe')->nullable();
            $table->string('numero_doe')->nullable();
            $table->string('status')->default('minuta');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instrumentos');
    }
};
