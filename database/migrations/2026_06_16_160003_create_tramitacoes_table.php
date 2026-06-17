<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tramitacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('processo_id')->constrained()->cascadeOnDelete();
            $table->string('de_setor');
            $table->string('para_setor');
            $table->foreignId('enviado_por')->constrained('users');
            $table->timestamp('enviado_em');
            $table->foreignId('recebido_por')->nullable()->constrained('users');
            $table->timestamp('recebido_em')->nullable();
            $table->text('parecer')->nullable();      // observação/análise do setor
            $table->string('status')->default('enviado'); // enviado, recebido, devolvido
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tramitacoes');
    }
};
