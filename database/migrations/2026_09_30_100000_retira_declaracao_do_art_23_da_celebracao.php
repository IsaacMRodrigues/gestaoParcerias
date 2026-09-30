<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * A declaração do art. 23, XIV, do Decreto Municipal 048/2020 (sem contas
 * pendentes) deixou de ser exigida na Celebração (decisão da gestão,
 * 30/09/2026). Saiu do checklist; nas Celebrações em curso, a peça ainda não
 * assinada é apagada. A assinada fica, como histórico do que a OSC declarou,
 * mas deixa de ser obrigatória.
 */
return new class extends Migration
{
    public function up(): void
    {
        $pecas = DB::table('pecas')->where('categoria', 'celebracao')->where('chave', 'decl_art23');

        (clone $pecas)->whereNull('assinado_em')->delete();
        (clone $pecas)->update(['obrigatorio' => false]);
    }

    public function down(): void
    {
        // As peças apagadas voltam sozinhas: o checklist as recria ao abrir a
        // Celebração, se a declaração voltar ao template.
        DB::table('pecas')->where('categoria', 'celebracao')->where('chave', 'decl_art23')->update(['obrigatorio' => true]);
    }
};
