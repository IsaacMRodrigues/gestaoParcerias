<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Prorrogação do prazo de inscrições do chamamento, feita pela SCP com dois
 * anexos obrigatórios: o aviso de prorrogação e o comprovante da publicação
 * (decisão da gestão, 28/09/2026). Cada prorrogação fica registrada — o
 * prazo pode ser prorrogado mais de uma vez.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chamamento_prorrogacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chamamento_id')->constrained()->cascadeOnDelete();
            $table->date('fim_anterior')->nullable();
            $table->date('fim_novo');
            $table->string('aviso_path');
            $table->string('aviso_nome');
            $table->string('publicacao_path');
            $table->string('publicacao_nome');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('autor_nome')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chamamento_prorrogacoes');
    }
};
