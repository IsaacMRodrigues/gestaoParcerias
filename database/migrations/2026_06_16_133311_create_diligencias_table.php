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
        Schema::create('diligencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposta_id')->constrained('propostas')->cascadeOnDelete();
            $table->foreignId('parecer_id')->nullable()->constrained('pareceres')->nullOnDelete();
            $table->text('descricao');
            $table->date('prazo');
            $table->text('resposta')->nullable();
            $table->timestamp('respondida_em')->nullable();
            $table->string('status')->default('pendente'); // pendente | respondida | encerrada
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diligencias');
    }
};
