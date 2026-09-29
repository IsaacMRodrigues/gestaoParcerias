<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Etapa própria para o prazo de recurso na Seleção (decisão da gestão,
 * 29/09/2026). Entra depois da publicação do Resultado Provisório (índice 2);
 * as etapas seguintes andam uma casa. A data final do prazo varia de edital
 * para edital e fica no chamamento — a SCP a informa ao publicar.
 *
 * Chamamento que já estava na antiga etapa 2 (a UG analisando recursos) vai
 * para a 3, que é a mesma coisa: o prazo dele já corria antes desta etapa
 * existir. O anexo avulso criado numa etapa acompanha a sua etapa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chamamentos', function (Blueprint $table) {
            $table->date('prazo_recurso_ate')->nullable()->after('data_resultado');
        });

        DB::table('chamamentos')->where('selecao_etapa', '>=', 2)->increment('selecao_etapa');
        DB::table('pecas')->where('pecaable_type', 'App\\Models\\Chamamento')
            ->whereNotNull('etapa')->where('etapa', '>=', 2)->increment('etapa');
    }

    public function down(): void
    {
        // A etapa de prazo (2) volta a ser a antiga 2, que abrangia o prazo.
        DB::table('chamamentos')->where('selecao_etapa', '>=', 3)->decrement('selecao_etapa');
        DB::table('pecas')->where('pecaable_type', 'App\\Models\\Chamamento')
            ->whereNotNull('etapa')->where('etapa', '>=', 3)->decrement('etapa');

        Schema::table('chamamentos', function (Blueprint $table) {
            $table->dropColumn('prazo_recurso_ate');
        });
    }
};
