<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Anexos das peças do trâmite (vários arquivos por peça).
 *
 * Atende três casos do fluxo do Chamamento Público: os anexos do Edital
 * (etapa 5, SCP), a Portaria da Comissão de Seleção (etapa 6, UG) e o
 * Comprovante de Publicação — que são dois documentos: diário oficial e site
 * oficial (etapa 8, SCP). Por isso é 1:N, e não uma coluna por peça.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('processo_peca_anexos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('processo_peca_id')->constrained('processo_pecas')->cascadeOnDelete();
            $table->string('arquivo_path');
            $table->string('arquivo_nome');
            $table->unsignedBigInteger('tamanho')->nullable();
            $table->string('mime_type')->nullable();
            $table->foreignId('enviado_por')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processo_peca_anexos');
    }
};
