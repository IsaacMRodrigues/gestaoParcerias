<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alterações da Parceria (módulo 3.3).
 *
 * Durante a execução a OSC precisa mudar o pactuado — remanejar rubricas,
 * prorrogar prazo, trocar metas. Até aqui não havia por onde pedir: ela ligava
 * para a Secretaria e o pedido vivia fora do sistema.
 *
 * O fluxo do modelo: a OSC monta o pedido com o seu checklist e assina; a
 * Unidade Gestora autoriza; a SCP processa. O que a alteração produz — aditivo
 * ou apostilamento — continua nos módulos que já existem: aqui é o pedido e a
 * sua instrução.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alteracoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instrumento_id')->constrained('instrumentos')->cascadeOnDelete();
            $table->unsignedSmallInteger('numero');
            $table->string('titulo');
            $table->text('descricao');
            $table->text('justificativa');
            $table->string('status', 20)->default('rascunho');
            $table->string('setor_atual', 20)->nullable();
            $table->unsignedTinyInteger('etapa')->default(0);

            // Retrato do plano no momento do pedido. Sem ele, quem analisa vê
            // o plano já alterado e não tem como saber o que mudou — a OSC
            // edita o próprio plano de trabalho durante a alteração.
            $table->json('plano_antes')->nullable();

            $table->foreignId('criada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('enviada_em')->nullable();
            $table->text('decisao_motivo')->nullable();
            $table->foreignId('decidida_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decidida_em')->nullable();
            $table->timestamps();
        });

        Schema::create('alteracao_tramitacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alteracao_id')->constrained('alteracoes')->cascadeOnDelete();
            $table->string('de_setor', 20);
            $table->string('para_setor', 20);
            $table->foreignId('enviado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('enviado_em');
            $table->text('parecer')->nullable();
            $table->string('status', 20)->default('enviado');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alteracao_tramitacoes');
        Schema::dropIfExists('alteracoes');
    }
};
