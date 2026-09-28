<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * O título do memorando do Planejamento deixa de citar convênios:
 * "MEMORANDO PARA SOLICITAÇÃO DE CONVÊNIOS/PARCERIAS" vira
 * "MEMORANDO PARA SOLICITAÇÃO DE PARCERIAS" (decisão da gestão, 28/09/2026).
 *
 * Como na troca de Ofício por Memorando, vale também para os documentos já
 * gravados, inclusive os assinados — decisão expressa. O original de cada
 * registro fica em `backup_titulo_memorando`, e o down() o restaura.
 * O nome do "Setor de Convênios e Parcerias" não é tocado.
 */
return new class extends Migration
{
    private const ALVOS = [
        ['pecas', 'conteudo'],
        ['processo_pecas', 'conteudo'],
    ];

    /** O título, com as letras acentuadas ou codificadas pelo editor. */
    private const TITULO = '/(SOLICITA(?:Ç|&Ccedil;)(?:Ã|&Atilde;)O DE )CONV(?:Ê|&Ecirc;)NIOS\s*\/\s*(PARCERIAS)/u';

    public function up(): void
    {
        Schema::create('backup_titulo_memorando', function (Blueprint $table) {
            $table->id();
            $table->string('tabela', 40);
            $table->unsignedBigInteger('registro_id');
            $table->string('coluna', 40);
            $table->longText('valor_original')->nullable();
            $table->timestamp('criado_em')->useCurrent();
        });

        foreach (self::ALVOS as [$tabela, $coluna]) {
            DB::table($tabela)
                ->where(fn ($q) => $q->where($coluna, 'like', '%NIOS/PARCERIAS%')->orWhere($coluna, 'like', '%NIOS / PARCERIAS%'))
                ->orderBy('id')
                ->each(function ($linha) use ($tabela, $coluna) {
                    $original = (string) $linha->{$coluna};
                    $novo     = preg_replace(self::TITULO, '$1$2', $original);

                    if ($novo === null || $novo === $original) {
                        return;
                    }

                    DB::table('backup_titulo_memorando')->insert([
                        'tabela' => $tabela, 'registro_id' => $linha->id, 'coluna' => $coluna, 'valor_original' => $original,
                    ]);
                    DB::table($tabela)->where('id', $linha->id)->update([$coluna => $novo]);
                });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('backup_titulo_memorando')) {
            return;
        }

        foreach (DB::table('backup_titulo_memorando')->orderByDesc('id')->get() as $b) {
            DB::table($b->tabela)->where('id', $b->registro_id)->update([$b->coluna => $b->valor_original]);
        }

        Schema::dropIfExists('backup_titulo_memorando');
    }
};
