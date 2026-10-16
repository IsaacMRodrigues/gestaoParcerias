<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * A prestação de contas deixa de ter o memorando de encaminhamento: sai a peça das prestações abertas.
 * As colunas que o preenchiam ficam (folhas, parcelas_recebidas, responsavel_*).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('pecas')->where('pecaable_type', \App\Models\PrestacaoContas::class)
            ->where('chave', 'oficio_encaminhamento')->delete();
    }

    public function down(): void
    {
    }
};
