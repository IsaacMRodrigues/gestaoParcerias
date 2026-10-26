<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * O Relatório e a Ata da Comissão de Seleção são documentos internos: saem do dossiê que a OSC vê
 * (Peca::INTERNAS). A SCP ainda pode abrir um deles na curadoria do dossiê.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('pecas')->where('categoria', 'chamamento_publico')
            ->whereIn('chave', ['relatorio_comissao', 'ata_comissao'])
            ->update(['visivel_osc' => false]);
    }

    public function down(): void
    {
        DB::table('pecas')->where('categoria', 'chamamento_publico')
            ->whereIn('chave', ['relatorio_comissao', 'ata_comissao'])
            ->update(['visivel_osc' => true]);
    }
};
