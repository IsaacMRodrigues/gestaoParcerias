<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 2.3.1 Ordem de Pagamento — emitida (várias) durante a vigência do instrumento.
     * Documento "modelo padrão" (assinatura digital) + anexo de dados bancários.
     */
    public function up(): void
    {
        Schema::create('ordens_pagamento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instrumento_id')->constrained('instrumentos')->cascadeOnDelete();
            $table->unsignedInteger('numero');
            $table->string('favorecido')->nullable();
            $table->decimal('valor', 15, 2)->nullable();
            $table->date('data_emissao')->nullable();
            $table->longText('conteudo')->nullable();
            $table->foreignId('assinado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('assinado_em')->nullable();
            $table->string('codigo_validacao')->nullable()->unique();
            $table->string('dados_bancarios_path')->nullable();
            $table->string('dados_bancarios_nome')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordens_pagamento');
    }
};
