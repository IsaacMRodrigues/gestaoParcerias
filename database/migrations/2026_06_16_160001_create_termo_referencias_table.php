<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('termo_referencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('processo_id')->unique()->constrained()->cascadeOnDelete();

            // 2.1 Descrição da realidade objeto da parceria
            $table->string('problema_identificado')->nullable();
            $table->string('publico_alvo')->nullable();
            $table->unsignedInteger('qtd_beneficiarios')->nullable();
            $table->string('area_tematica')->nullable();
            $table->text('justificativa_necessidade')->nullable();
            $table->text('indicadores')->nullable();

            // 2.2 Vinculação ao Planejamento Governamental
            $table->string('programa_governo')->nullable();
            $table->string('acao_governamental')->nullable();
            $table->string('dotacao_orcamentaria')->nullable();

            // 2.3 Definição do Objeto
            $table->text('objeto_resumido')->nullable();
            $table->string('vigencia_prevista')->nullable();
            $table->string('local_execucao')->nullable();
            $table->text('objetivo_geral')->nullable();
            $table->text('objetivos_especificos')->nullable();

            // 2.4 Justificativa
            $table->text('justificativa')->nullable();

            // 2.5 Recursos Financeiros
            $table->decimal('valor_total', 15, 2)->nullable();
            $table->string('fonte_recurso')->nullable();

            // Assinatura simples
            $table->foreignId('assinado_por')->nullable()->constrained('users');
            $table->timestamp('assinado_em')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('termo_referencias');
    }
};
