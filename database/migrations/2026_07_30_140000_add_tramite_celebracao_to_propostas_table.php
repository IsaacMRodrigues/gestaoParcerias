<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trâmite da Celebração (Fluxo Etapa de Celebração do cliente), ancorado na
 * proposta aprovada — é ela que vira a parceria:
 *   UG convoca → OSC (plano + habilitação) → UG (aprova o plano) → SCP
 *   → SEPLAN (parecer financeiro) → UG (portarias + parecer técnico) → SCP
 *   (protocolo) → PJ (parecer) → SCP (termo + publicação) → SCP (autorização)
 *   → OSC (dados bancários) → SCP (OP global) → UG (assina) → SCP (empenho).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('propostas', function (Blueprint $table) {
            $table->unsignedTinyInteger('celebracao_etapa')->default(0)->after('status');
            $table->string('celebracao_setor', 20)->default('ug')->after('celebracao_etapa');
            $table->timestamp('celebracao_iniciada_em')->nullable()->after('celebracao_setor');
            $table->timestamp('celebracao_concluida_em')->nullable()->after('celebracao_iniciada_em');
        });
    }

    public function down(): void
    {
        Schema::table('propostas', function (Blueprint $table) {
            $table->dropColumn([
                'celebracao_etapa', 'celebracao_setor',
                'celebracao_iniciada_em', 'celebracao_concluida_em',
            ]);
        });
    }
};
