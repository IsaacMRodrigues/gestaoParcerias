<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * O documento do Planejamento nasce com o texto-modelo, então "tem conteúdo" não dizia se alguém o preencheu:
 * passa a guardar quando foi salvo por alguém. Os já assinados ou alterados depois de criados contam como editados.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('processo_pecas', function (Blueprint $table) {
            $table->timestamp('editado_em')->nullable()->after('conteudo');
        });

        DB::table('processo_pecas')->whereNotNull('assinado_em')->update(['editado_em' => DB::raw('assinado_em')]);
        DB::table('processo_pecas')->whereNull('editado_em')->whereNotNull('conteudo')
            ->whereColumn('updated_at', '>', 'created_at')->update(['editado_em' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('processo_pecas', function (Blueprint $table) {
            $table->dropColumn('editado_em');
        });
    }
};
