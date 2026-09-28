<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "Ofício" passa a se chamar "Memorando" em todo o sistema (decisão da gestão,
 * 28/09/2026) — também nos documentos já gravados, inclusive os assinados.
 *
 * Os modelos mudaram no código; aqui muda o que já tinha sido copiado deles
 * para cada documento. Por decisão expressa, entram os assinados: a página
 * de validação passa a mostrar "Memorando" num texto que foi assinado com
 * "Ofício". Para que isso tenha volta, o texto original de cada registro
 * alterado fica guardado em `backup_oficio_memorando`, e o down() o restaura.
 *
 * O editor grava parte dos textos com a letra codificada ("Of&iacute;cio"):
 * as duas formas são trocadas. Identificadores internos (`oficio`,
 * `oficio_pedido`…) não aparecem na tela e ficam como estão.
 */
return new class extends Migration
{
    private const ALVOS = [
        ['pecas', 'rotulo'],
        ['pecas', 'conteudo'],
        ['processo_pecas', 'conteudo'],
        ['ordens_pagamento', 'conteudo'],
    ];

    private const TROCAS = [
        'OFÍCIOS' => 'MEMORANDOS', 'Ofícios' => 'Memorandos', 'ofícios' => 'memorandos',
        'OFÍCIO'  => 'MEMORANDO',  'Ofício'  => 'Memorando',  'ofício'  => 'memorando',
        'OF&Iacute;CIOS' => 'MEMORANDOS', 'Of&iacute;cios' => 'Memorandos', 'of&iacute;cios' => 'memorandos',
        'OF&Iacute;CIO'  => 'MEMORANDO',  'Of&iacute;cio'  => 'Memorando',  'of&iacute;cio'  => 'memorando',
        'OF&iacute;CIO'  => 'MEMORANDO',
    ];

    public function up(): void
    {
        Schema::create('backup_oficio_memorando', function (Blueprint $table) {
            $table->id();
            $table->string('tabela', 40);
            $table->unsignedBigInteger('registro_id');
            $table->string('coluna', 40);
            $table->longText('valor_original')->nullable();
            $table->timestamp('criado_em')->useCurrent();
        });

        foreach (self::ALVOS as [$tabela, $coluna]) {
            DB::table($tabela)
                ->where(function ($q) use ($coluna) {
                    foreach (array_keys(self::TROCAS) as $termo) {
                        $q->orWhere($coluna, 'like', '%' . $termo . '%');
                    }
                })
                ->orderBy('id')
                ->each(function ($linha) use ($tabela, $coluna) {
                    $original = $linha->{$coluna};
                    $novo     = strtr((string) $original, self::TROCAS);

                    if ($novo === $original) {
                        return; // o LIKE do banco ignora maiúsculas; a troca, não
                    }

                    DB::table('backup_oficio_memorando')->insert([
                        'tabela' => $tabela, 'registro_id' => $linha->id, 'coluna' => $coluna, 'valor_original' => $original,
                    ]);
                    DB::table($tabela)->where('id', $linha->id)->update([$coluna => $novo]);
                });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('backup_oficio_memorando')) {
            return;
        }

        foreach (DB::table('backup_oficio_memorando')->orderByDesc('id')->get() as $b) {
            DB::table($b->tabela)->where('id', $b->registro_id)->update([$b->coluna => $b->valor_original]);
        }

        Schema::dropIfExists('backup_oficio_memorando');
    }
};
