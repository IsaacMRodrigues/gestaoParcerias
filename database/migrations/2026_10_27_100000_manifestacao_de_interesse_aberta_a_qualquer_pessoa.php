<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Manifestação de interesse no modelo do PMIS (arts. 18 a 21 da Lei 13.019/2014): qualquer pessoa física ou
 * jurídica propõe, por formulário público, sem ser OSC cadastrada. Quem propõe se identifica no próprio
 * formulário; a OSC logada fica vinculada, e a manifestação continua de pé se ela sair do cadastro.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manifestacoes_interesse', function (Blueprint $table) {
            $table->dropForeign(['osc_id']);
        });

        Schema::table('manifestacoes_interesse', function (Blueprint $table) {
            $table->foreignId('osc_id')->nullable()->change();
            $table->foreign('osc_id')->references('id')->on('oscs')->nullOnDelete();

            // O formulário do PMIS não tem título, objeto nem justificativa; a Nova Proposta segue pedindo.
            $table->string('titulo')->nullable()->change();
            $table->text('objeto')->nullable()->change();
            $table->text('justificativa')->nullable()->change();

            $table->string('proponente_nome')->nullable()->after('osc_id');
            $table->string('proponente_tipo', 2)->nullable()->after('proponente_nome');   // pf | pj
            $table->string('proponente_email')->nullable()->after('proponente_tipo');
            $table->text('interesse_publico')->nullable()->after('justificativa');
            $table->text('diagnostico')->nullable()->after('interesse_publico');
            $table->text('viabilidade')->nullable()->after('diagnostico');
            $table->string('arquivo_path')->nullable()->after('viabilidade');
            $table->string('arquivo_nome')->nullable()->after('arquivo_path');
            $table->unsignedBigInteger('arquivo_tamanho')->nullable()->after('arquivo_nome');
            // Link pessoal de acompanhamento de quem enviou sem login.
            $table->string('token', 64)->nullable()->unique()->after('protocolo');
        });
    }

    public function down(): void
    {
        Schema::table('manifestacoes_interesse', function (Blueprint $table) {
            $table->dropUnique(['token']);
            $table->dropColumn([
                'proponente_nome', 'proponente_tipo', 'proponente_email',
                'interesse_publico', 'diagnostico', 'viabilidade',
                'arquivo_path', 'arquivo_nome', 'arquivo_tamanho', 'token',
            ]);
            $table->dropForeign(['osc_id']);
        });

        Schema::table('manifestacoes_interesse', function (Blueprint $table) {
            $table->foreign('osc_id')->references('id')->on('oscs')->cascadeOnDelete();
        });
    }
};
