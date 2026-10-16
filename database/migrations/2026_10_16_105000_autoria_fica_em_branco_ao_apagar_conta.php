<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * As colunas de autoria do módulo de processos ainda impediam apagar a conta: passam a ficar em
 * branco, como as demais do sistema.
 */
return new class extends Migration
{
    private const COLUNAS = [
        'documentos'           => ['uploaded_by'],
        'processos'            => ['created_by'],
        'tramitacoes'          => ['enviado_por', 'recebido_por'],
        'pecas'                => ['assinado_por'],
        'processo_pecas'       => ['assinado_por'],
        'processo_peca_anexos' => ['enviado_por'],
    ];

    public function up(): void
    {
        foreach (self::COLUNAS as $tabela => $colunas) {
            Schema::table($tabela, function (Blueprint $table) use ($colunas) {
                foreach ($colunas as $coluna) {
                    $table->dropForeign([$coluna]);
                }
            });
            Schema::table($tabela, function (Blueprint $table) use ($colunas) {
                foreach ($colunas as $coluna) {
                    $table->unsignedBigInteger($coluna)->nullable()->change();
                    $table->foreign($coluna)->references('id')->on('users')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::COLUNAS as $tabela => $colunas) {
            Schema::table($tabela, function (Blueprint $table) use ($colunas) {
                foreach ($colunas as $coluna) {
                    $table->dropForeign([$coluna]);
                    $table->foreign($coluna)->references('id')->on('users');
                }
            });
        }
    }
};
