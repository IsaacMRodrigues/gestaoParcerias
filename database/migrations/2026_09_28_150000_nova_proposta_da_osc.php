<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "Nova Proposta" da OSC (decisão da gestão, 28/09/2026).
 *
 * Tem o conteúdo da manifestação de interesse — dados, plano de trabalho,
 * documentos — e mora na mesma tabela, distinguida por `tipo`. Muda o
 * caminho: a OSC informa o fundamento (dispensa ou inexigibilidade), a SCP
 * recebe e escolhe a Unidade Gestora que a atende, e a UG defere ou
 * indefere. Por isso `orgao_id` passa a aceitar vazio: na Nova Proposta, a
 * Secretaria só existe depois do encaminhamento da SCP.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manifestacoes_interesse', function (Blueprint $table) {
            $table->string('tipo', 20)->default('manifestacao')->after('id');
            $table->string('fundamento_pedido', 20)->nullable()->after('tipo'); // dispensa | inexigibilidade
            $table->unsignedBigInteger('orgao_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Proposta ainda sem Secretaria não cabe no esquema antigo: melhor
        // parar do que apagar o que a OSC enviou.
        if (DB::table('manifestacoes_interesse')->whereNull('orgao_id')->exists()) {
            throw new RuntimeException('Há Nova Proposta ainda sem Secretaria definida; encaminhe-as antes de desfazer esta migração.');
        }

        Schema::table('manifestacoes_interesse', function (Blueprint $table) {
            $table->unsignedBigInteger('orgao_id')->nullable(false)->change();
            $table->dropColumn(['tipo', 'fundamento_pedido']);
        });
    }
};
