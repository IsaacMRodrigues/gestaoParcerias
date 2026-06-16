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
        Schema::create('propostas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chamamento_id')->constrained('chamamentos')->restrictOnDelete();
            $table->foreignId('osc_id')->constrained('oscs')->restrictOnDelete();
            $table->string('titulo');
            $table->text('objeto');
            $table->text('justificativa')->nullable();
            $table->decimal('valor_solicitado', 15, 2);
            $table->decimal('valor_proprio', 15, 2)->default(0);
            $table->date('data_inicio_prevista')->nullable();
            $table->date('data_fim_prevista')->nullable();
            $table->string('status')->default('rascunho');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('propostas');
    }
};
