<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * A Ordem de Pagamento Global com duas assinaturas, nesta ordem: o Gestor da
 * Parceria e o Responsável da UG (decisão da gestão, 01/10/2026). Cada uma tem
 * a sua etapa: a Celebração passa de 21 para 22, e o empenho, que era a 21ª,
 * vai para a 22ª.
 *
 * A OP já assinada do jeito antigo (pela UG, na coluna da peça) continua
 * valendo como está.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('propostas')->where('celebracao_etapa', '>=', 20)->increment('celebracao_etapa');
        DB::table('pecas')->where('pecaable_type', 'App\\Models\\Proposta')->where('categoria', 'celebracao')
            ->whereNotNull('etapa')->where('etapa', '>=', 20)->increment('etapa');
    }

    public function down(): void
    {
        // As duas etapas de assinatura voltam a ser a única de antes, da UG.
        DB::table('propostas')->whereIn('celebracao_etapa', [19, 20])->update(['celebracao_etapa' => 19, 'celebracao_setor' => 'ug']);
        DB::table('propostas')->where('celebracao_etapa', '>=', 21)->decrement('celebracao_etapa');
        DB::table('pecas')->where('pecaable_type', 'App\\Models\\Proposta')->where('categoria', 'celebracao')
            ->whereNotNull('etapa')->where('etapa', '>=', 21)->decrement('etapa');
    }
};
