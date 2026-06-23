<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Alinha o Termo de Referência ao modelo real do cliente (Arquivo II).
     */
    public function up(): void
    {
        Schema::table('termo_referencias', function (Blueprint $table) {
            // remove campos detalhados que não existem no modelo do cliente
            $table->dropColumn([
                'problema_identificado', 'publico_alvo', 'qtd_beneficiarios', 'area_tematica',
                'justificativa_necessidade', 'indicadores', 'programa_governo', 'acao_governamental',
                'objeto_resumido', 'vigencia_prevista', 'local_execucao', 'objetivo_geral',
                'dotacao_orcamentaria', 'fonte_recurso',
            ]);
        });

        Schema::table('termo_referencias', function (Blueprint $table) {
            $table->text('descricao_realidade')->nullable()->after('processo_id');
            $table->text('objeto')->nullable()->after('justificativa');
            $table->string('dotacao')->nullable()->after('valor_total');
            $table->string('ficha')->nullable()->after('dotacao');
            $table->string('fonte')->nullable()->after('ficha');
            $table->unsignedInteger('prazo_meses')->nullable()->after('fonte');
        });
    }

    public function down(): void
    {
        Schema::table('termo_referencias', function (Blueprint $table) {
            $table->dropColumn(['descricao_realidade', 'objeto', 'dotacao', 'ficha', 'fonte', 'prazo_meses']);
            $table->string('problema_identificado')->nullable();
            $table->string('publico_alvo')->nullable();
            $table->unsignedInteger('qtd_beneficiarios')->nullable();
            $table->string('area_tematica')->nullable();
            $table->text('justificativa_necessidade')->nullable();
            $table->text('indicadores')->nullable();
            $table->string('programa_governo')->nullable();
            $table->string('acao_governamental')->nullable();
            $table->text('objeto_resumido')->nullable();
            $table->string('vigencia_prevista')->nullable();
            $table->string('local_execucao')->nullable();
            $table->text('objetivo_geral')->nullable();
            $table->string('dotacao_orcamentaria')->nullable();
            $table->string('fonte_recurso')->nullable();
        });
    }
};
