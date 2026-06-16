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
        Schema::create('aditivos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instrumento_id')->constrained('instrumentos')->cascadeOnDelete();
            $table->unsignedSmallInteger('numero');
            $table->string('tipo');
            $table->text('descricao');
            $table->date('data_assinatura')->nullable();
            $table->date('nova_data_fim')->nullable();
            $table->decimal('valor_adicional', 15, 2)->nullable();
            $table->string('status')->default('minuta');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aditivos');
    }
};
