<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * A rota do Chamamento Público ganhou uma etapa: depois da UG revisar/assinar o
 * Edital e anexar a Portaria da Comissão (etapa 6), o processo passa a ir para a
 * SCP, que emite e assina o Protocolo (Solicitação de Parecer Jurídico) na nova
 * etapa 7, e só então segue à Procuradoria.
 *
 * Isso desloca em +1 as etapas que vinham depois:
 *   7 (PJ · Parecer Jurídico)  → 8
 *   8 (SCP · Publicação)       → 9
 *
 * A rota Dispensa/Inexigibilidade não muda de tamanho (só o setor da etapa 2,
 * que passou de UG para SCP) — por isso é excluída do reajuste.
 */
return new class extends Migration
{
    public function up(): void
    {
        // De trás para frente, para não colidir (8→9 antes de 7→8).
        DB::table('processos')
            ->where('modalidade', 'chamamento_publico')
            ->where('etapa', 8)
            ->update(['etapa' => 9]);

        DB::table('processos')
            ->where('modalidade', 'chamamento_publico')
            ->where('etapa', 7)
            ->update(['etapa' => 8]);
    }

    public function down(): void
    {
        DB::table('processos')
            ->where('modalidade', 'chamamento_publico')
            ->where('etapa', 8)
            ->update(['etapa' => 7]);

        DB::table('processos')
            ->where('modalidade', 'chamamento_publico')
            ->where('etapa', 9)
            ->update(['etapa' => 8]);
    }
};
