<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Os textos da proposta e do plano de trabalho vão até 1000 caracteres: as descrições que eram
 * varchar(255) passam a texto. E o plano de aplicação dos recursos ganha o campo digitado do
 * primeiro formulário da Nova Proposta.
 */
return new class extends Migration
{
    /** tabela => [coluna => aceita nulo], como já era. */
    private const PARA_TEXTO = [
        'metas'                => ['descricao' => false, 'indicador' => true, 'meta_quantitativa' => true],
        'etapas'               => ['descricao' => false],
        'plano_itens'          => ['descricao' => false, 'atividades_vinculadas' => true],
        'plano_contrapartidas' => ['descricao' => false],
        'plano_equipe'         => ['cargo_funcao' => false, 'formacao' => true],
    ];

    public function up(): void
    {
        foreach (self::PARA_TEXTO as $tabela => $colunas) {
            Schema::table($tabela, function (Blueprint $table) use ($colunas) {
                foreach ($colunas as $coluna => $nula) {
                    $table->text($coluna)->nullable($nula)->change();
                }
            });
        }

        foreach (['manifestacoes_interesse', 'propostas'] as $tabela) {
            Schema::table($tabela, fn (Blueprint $table) => $table->text('plano_aplicacao')->nullable()->after('metodologia'));
        }
    }

    public function down(): void
    {
        foreach (['manifestacoes_interesse', 'propostas'] as $tabela) {
            Schema::table($tabela, fn (Blueprint $table) => $table->dropColumn('plano_aplicacao'));
        }
    }
};
