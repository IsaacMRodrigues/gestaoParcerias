<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A peça da Seleção pode ser satisfeita por um documento do Planejamento.
 *
 * O Edital nasce, é revisado, assinado pela UG e publicado dentro do processo
 * de Planejamento. Ao chegar na Seleção, o checklist pedia tudo de novo — o
 * mesmo edital, a mesma portaria da comissão, o mesmo parecer jurídico, o
 * mesmo comprovante de publicação. Redigitar um documento assinado é criar um
 * segundo original: dois textos, duas assinaturas, dois códigos de validação
 * para o que é uma peça só do processo.
 *
 * Guardar a referência, e não uma cópia, mantém o documento único: a Seleção
 * mostra o do Planejamento, com a assinatura que ele já tem.
 *
 * `nullOnDelete`: se o processo for apagado, o item volta a ser preenchível à
 * mão em vez de a Seleção deixar de abrir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pecas', function (Blueprint $table) {
            $table->foreignId('origem_processo_peca_id')
                ->nullable()
                ->after('criado_por')
                ->constrained('processo_pecas')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pecas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('origem_processo_peca_id');
        });
    }
};
