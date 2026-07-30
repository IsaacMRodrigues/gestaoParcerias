<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Assinatura das partes" (Fluxo Celebração): o Termo é assinado pelo Município
 * e **contra-assinado pela OSC**. A peça passa a guardar a segunda assinatura,
 * com o seu próprio código de validação.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pecas', function (Blueprint $table) {
            $table->foreignId('contra_assinado_por')->nullable()->after('codigo_validacao')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('contra_assinado_em')->nullable()->after('contra_assinado_por');
            $table->string('codigo_validacao_contra')->nullable()->after('contra_assinado_em');
        });
    }

    public function down(): void
    {
        Schema::table('pecas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contra_assinado_por');
            $table->dropColumn(['contra_assinado_em', 'codigo_validacao_contra']);
        });
    }
};
