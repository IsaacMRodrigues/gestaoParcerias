<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pecas', function (Blueprint $table) {
            $table->id();
            $table->morphs('pecaable');               // chamamento, instrumento, aditivo...
            $table->string('categoria');              // chamamento_publico, dispensa_inexigibilidade, apostilamento...
            $table->string('chave');                  // edital, parecer_juridico, ...
            $table->string('rotulo');
            $table->string('tipo')->default('modelo'); // modelo (texto+assinatura) | arquivo (upload)
            $table->boolean('obrigatorio')->default(true);
            $table->unsignedInteger('ordem')->default(0);

            // conteúdo (tipo modelo)
            $table->longText('conteudo')->nullable();

            // arquivo (tipo arquivo)
            $table->string('arquivo_path')->nullable();
            $table->string('arquivo_nome')->nullable();
            $table->unsignedBigInteger('tamanho')->nullable();
            $table->string('mime_type', 100)->nullable();

            // assinatura simples
            $table->foreignId('assinado_por')->nullable()->constrained('users');
            $table->timestamp('assinado_em')->nullable();

            $table->timestamps();

            $table->unique(['pecaable_type', 'pecaable_id', 'categoria', 'chave'], 'pecas_unica');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pecas');
    }
};
