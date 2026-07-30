<?php

use App\Models\Peca;
use App\Models\Proposta;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * A Celebração ganhou uma etapa (14 → 15): depois de a SCP emitir o Parecer da
 * SCP e assinar o Termo pelo Município (etapa 8), o processo vai à **OSC para
 * contra-assinar** (nova etapa 9) — é a "assinatura das partes" prevista no
 * Fluxo Celebração. As etapas seguintes deslocam +1.
 *
 * Também cria a peça nova (Parecer da SCP) nas celebrações já em andamento e
 * realinha rótulo/ordem do checklist.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Desloca de trás para frente, para não colidir.
        for ($etapa = 13; $etapa >= 9; $etapa--) {
            DB::table('propostas')
                ->where('celebracao_etapa', $etapa)
                ->update(['celebracao_etapa' => $etapa + 1]);
        }

        // Cria as peças novas nas celebrações já iniciadas e realinha a ordem.
        Proposta::whereNotNull('celebracao_iniciada_em')->get()
            ->each(fn (Proposta $p) => Peca::sincronizar($p, 'celebracao'));

        foreach (Peca::TEMPLATES['celebracao'] as $ordem => $item) {
            Peca::where('categoria', 'celebracao')
                ->where('chave', $item['chave'])
                ->update(['rotulo' => $item['rotulo'], 'ordem' => $ordem]);
        }
    }

    public function down(): void
    {
        for ($etapa = 10; $etapa <= 14; $etapa++) {
            DB::table('propostas')
                ->where('celebracao_etapa', $etapa)
                ->update(['celebracao_etapa' => $etapa - 1]);
        }
    }
};
