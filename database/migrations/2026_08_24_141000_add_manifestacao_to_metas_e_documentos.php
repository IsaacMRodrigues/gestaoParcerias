<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plano de trabalho e documentos antes de existir proposta.
 *
 * Na manifestação de interesse a OSC entrega o dossiê completo — metas, etapas
 * e habilitação — mas a proposta só nasce quando a SCP defere. Metas e
 * documentos passam a poder pertencer à manifestação; no deferimento, os mesmos
 * registros recebem a proposta criada e continuam a valer, sem recadastro.
 *
 * Duas chaves em vez de relação polimórfica: as telas de proposta consultam
 * `proposta_id` e seguem funcionando sem uma linha alterada — o que importa
 * numa base em que documentos e metas aparecem em dezenas de lugares.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('metas', function (Blueprint $table) {
            $table->foreignId('proposta_id')->nullable()->change();
            $table->foreignId('manifestacao_id')->nullable()->after('proposta_id')
                  ->constrained('manifestacoes_interesse')->cascadeOnDelete();
        });

        Schema::table('documentos', function (Blueprint $table) {
            $table->foreignId('proposta_id')->nullable()->change();
            $table->foreignId('manifestacao_id')->nullable()->after('proposta_id')
                  ->constrained('manifestacoes_interesse')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('metas', function (Blueprint $table) {
            $table->dropForeign(['manifestacao_id']);
            $table->dropColumn('manifestacao_id');
        });

        Schema::table('documentos', function (Blueprint $table) {
            $table->dropForeign(['manifestacao_id']);
            $table->dropColumn('manifestacao_id');
        });
    }
};
