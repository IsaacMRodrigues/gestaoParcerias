<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Comprovante de publicação da Celebração vira dois campos.
 *
 * Diário Oficial e site do Município são veículos distintos, exigidos em
 * separado — num campo só cabia um arquivo, e anexar o segundo apagava o
 * primeiro. A peça existente vira a do Diário Oficial (preservando o que já foi
 * anexado); a do site nasce vazia na próxima abertura da tela, pela
 * sincronização do checklist.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('pecas')
            ->where('categoria', 'celebracao')
            ->where('chave', 'comprovante_publicacao')
            ->update([
                'chave'  => 'comprovante_publicacao_doe',
                'rotulo' => 'Comprovante de publicação no Diário Oficial',
            ]);
    }

    public function down(): void
    {
        // O comprovante do site não tem para onde voltar: some junto com a
        // separação, e o do Diário Oficial reassume o campo único.
        DB::table('pecas')
            ->where('categoria', 'celebracao')
            ->where('chave', 'comprovante_publicacao_site')
            ->delete();

        DB::table('pecas')
            ->where('categoria', 'celebracao')
            ->where('chave', 'comprovante_publicacao_doe')
            ->update([
                'chave'  => 'comprovante_publicacao',
                'rotulo' => 'Comprovante de publicação (Diário Oficial e site)',
            ]);
    }
};
