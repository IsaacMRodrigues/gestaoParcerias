<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A Seleção deixa de ser só um checklist e passa a tramitar entre setores
 * (Fluxo Seleção do cliente):
 *   UG (relatório + ata + resultado provisório) → SCP (publica provisório)
 *   → UG (recursos + resultado definitivo) → SCP (publica definitivo + emite
 *   o Termo de Adjudicação e Homologação) → Prefeito (assina) → encerra.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chamamentos', function (Blueprint $table) {
            $table->unsignedTinyInteger('selecao_etapa')->default(0)->after('status');
            $table->string('selecao_setor', 20)->default('ug')->after('selecao_etapa');
            $table->timestamp('selecao_concluida_em')->nullable()->after('selecao_setor');
        });
    }

    public function down(): void
    {
        Schema::table('chamamentos', function (Blueprint $table) {
            $table->dropColumn(['selecao_etapa', 'selecao_setor', 'selecao_concluida_em']);
        });
    }
};
