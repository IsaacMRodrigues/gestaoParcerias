<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Número de protocolo da manifestação de interesse e da Nova Proposta
 * (decisão da gestão, 29/09/2026): nasce no envio, numeração única por ano
 * para as duas — são o mesmo livro de pedidos à Prefeitura —, no formato dos
 * chamados, 2026/0001.
 *
 * As já enviadas ganham número na ordem em que foram enviadas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manifestacoes_interesse', function (Blueprint $table) {
            $table->string('protocolo', 20)->nullable()->unique()->after('tipo');
        });

        $enviadas = DB::table('manifestacoes_interesse')->where('status', '!=', 'rascunho')
            ->orderByRaw('COALESCE(submetida_em, created_at)')->orderBy('id')
            ->get(['id', 'submetida_em', 'created_at']);

        $porAno = [];
        foreach ($enviadas as $m) {
            $ano = (int) substr((string) ($m->submetida_em ?? $m->created_at), 0, 4);
            $porAno[$ano] = ($porAno[$ano] ?? 0) + 1;
            DB::table('manifestacoes_interesse')->where('id', $m->id)
                ->update(['protocolo' => $ano . '/' . str_pad((string) $porAno[$ano], 4, '0', STR_PAD_LEFT)]);
        }
    }

    public function down(): void
    {
        Schema::table('manifestacoes_interesse', function (Blueprint $table) {
            $table->dropUnique(['protocolo']);
            $table->dropColumn('protocolo');
        });
    }
};
