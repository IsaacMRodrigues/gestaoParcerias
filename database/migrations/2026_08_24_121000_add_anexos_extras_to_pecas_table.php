<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Anexos avulsos no checklist.
 *
 * O checklist é fechado: cada peça vem do template, e setor/etapa são lidos de
 * mapas indexados pela chave. Quando a etapa pede um documento que o template
 * não previu (mais de um comprovante, um ofício complementar), não havia onde
 * pôr — o servidor anexava por fora do sistema ou sobrescrevia outro campo.
 *
 * Estas colunas permitem a peça criada à mão: `extra` a distingue das do
 * template (que a sincronização governa), e `setor`/`etapa` guardam na linha o
 * que as peças de template leem dos mapas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pecas', function (Blueprint $table) {
            $table->boolean('extra')->default(false)->after('obrigatorio');
            // Nulos nas peças de template: para elas, quem manda continuam sendo
            // os mapas de Peca — dois lugares com a mesma verdade dariam briga.
            $table->string('setor', 30)->nullable()->after('extra');
            $table->unsignedTinyInteger('etapa')->nullable()->after('setor');
            $table->foreignId('criado_por')->nullable()->after('etapa')
                  ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pecas', function (Blueprint $table) {
            $table->dropForeign(['criado_por']);
            $table->dropColumn(['extra', 'setor', 'etapa', 'criado_por']);
        });
    }
};
