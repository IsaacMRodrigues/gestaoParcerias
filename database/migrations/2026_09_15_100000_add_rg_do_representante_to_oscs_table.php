<?php

/**
 * RG do representante legal, com o órgão que o expediu.
 *
 * Seis das oito declarações da habilitação abrem igual: "Eu, [nome],
 * portador(a) da carteira de identidade nº ___ expedida pela ___, inscrito(a)
 * no CPF sob o nº ___, na qualidade de representante legal da [OSC]...". O
 * cadastro já guardava tudo isso menos o RG e o órgão expedidor — e é por
 * faltarem esses dois que a declaração não saía pronta para assinar.
 *
 * Nulos: o módulo 1 não lista o RG entre os dados do representante, e as
 * organizações já cadastradas não o têm. Onde faltar, a declaração mostra o
 * espaço a completar, como os demais modelos fazem com dado desconhecido.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oscs', function (Blueprint $table) {
            $table->string('resp_rg', 20)->nullable()->after('resp_cpf');
            $table->string('resp_rg_orgao', 30)->nullable()->after('resp_rg');
        });
    }

    public function down(): void
    {
        Schema::table('oscs', function (Blueprint $table) {
            $table->dropColumn(['resp_rg', 'resp_rg_orgao']);
        });
    }
};
