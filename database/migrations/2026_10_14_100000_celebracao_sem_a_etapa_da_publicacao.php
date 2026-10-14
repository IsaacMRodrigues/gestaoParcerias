<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * A etapa 17 da Celebração (SCP: publicação e Autorização de Início) saiu; os documentos dela foram
 * para a etapa da OP Global. As celebrações e os anexos avulsos das etapas seguintes andam uma casa.
 * Quem estava na etapa que saiu vai para a dos dados bancários, a próxima: a publicação fica para a SCP
 * logo depois, com a OP Global.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('propostas')->whereNull('celebracao_concluida_em')->where('celebracao_etapa', 16)
            ->update(['celebracao_setor' => 'osc']);
        DB::table('propostas')->where('celebracao_etapa', '>', 16)->decrement('celebracao_etapa');

        // Anexo avulso criado na etapa que saiu acompanha os documentos dela, na etapa da OP Global.
        DB::table('pecas')->where('categoria', 'celebracao')->whereNotNull('etapa')->where('etapa', '>=', 16)
            ->update(['etapa' => DB::raw('CASE WHEN etapa = 16 THEN 17 ELSE etapa - 1 END')]);
    }

    public function down(): void
    {
        DB::table('propostas')->where('celebracao_etapa', '>', 16)->increment('celebracao_etapa');
        DB::table('pecas')->where('categoria', 'celebracao')->whereNotNull('etapa')->where('etapa', '>=', 16)
            ->update(['etapa' => DB::raw('etapa + 1')]);
    }
};
