<?php

use App\Support\TrilhaDeAssinatura;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Assinatura eletrônica avançada: a trilha de auditoria encadeada das assinaturas e o registro dos PDFs emitidos.
 * As assinaturas já feitas entram na trilha como "anteriores", com o hash do texto de hoje.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assinatura_eventos', function (Blueprint $table) {
            $table->id();
            $table->string('evento', 30);
            $table->string('documento_type', 30);
            $table->unsignedBigInteger('documento_id');
            $table->string('documento_rotulo')->nullable();
            $table->string('codigo_validacao', 20)->nullable()->index();
            $table->string('papel', 30)->nullable();
            // Sem chave estrangeira: a trilha fica mesmo se a conta ou o documento saírem.
            $table->unsignedBigInteger('user_id')->nullable();
            // Textos JSON gravados como texto: o hash é do texto exato, e a coluna JSON do MySQL reordena chaves.
            $table->text('identidade')->nullable();
            $table->string('autenticacao', 100)->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('metadados')->nullable();
            $table->char('hash_documento', 64)->nullable();
            $table->dateTime('carimbo_tempo', 6);
            $table->string('fonte_tempo', 100)->nullable();
            $table->integer('desvio_ms')->nullable();
            $table->text('detalhes')->nullable();
            // Único: dois eventos não podem nascer do mesmo elo.
            $table->char('hash_anterior', 64)->unique();
            $table->char('hash', 64)->unique();

            $table->index(['documento_type', 'documento_id']);
        });

        Schema::create('documentos_emitidos', function (Blueprint $table) {
            $table->id();
            $table->char('hash_pdf', 64)->unique();
            $table->string('documento_type', 30);
            $table->unsignedBigInteger('documento_id');
            $table->string('documento_rotulo')->nullable();
            $table->char('hash_documento', 64);
            $table->text('codigos');
            $table->unsignedBigInteger('emitido_por')->nullable();
            $table->dateTime('emitido_em');
        });

        TrilhaDeAssinatura::registrarAnteriores();
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_emitidos');
        Schema::dropIfExists('assinatura_eventos');
    }
};
