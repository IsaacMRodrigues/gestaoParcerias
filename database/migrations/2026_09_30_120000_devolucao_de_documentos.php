<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Devolução por documento (decisão da gestão, 30/09/2026): quem devolve o
 * trâmite marca o que está errado, e só isso reabre — com o motivo escrito no
 * próprio documento, à vista de quem vai corrigir. Vale para as peças do
 * motor genérico (Seleção, Celebração, Alteração, Prestação de contas) e para
 * as do Planejamento.
 *
 * Nome e setor de quem devolveu vão em texto, como o carimbo da assinatura:
 * não mudam se a pessoa trocar de setor, e não prendem a conta dela.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['pecas', 'processo_pecas'] as $tabela) {
            Schema::table($tabela, function (Blueprint $table) {
                $table->timestamp('devolvida_em')->nullable();
                $table->text('devolucao_motivo')->nullable();
                $table->string('devolvida_por_nome')->nullable();
                $table->string('devolvida_pelo_setor', 20)->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['pecas', 'processo_pecas'] as $tabela) {
            Schema::table($tabela, fn (Blueprint $table) => $table->dropColumn(
                ['devolvida_em', 'devolucao_motivo', 'devolvida_por_nome', 'devolvida_pelo_setor']
            ));
        }
    }
};
