<?php

use App\Models\Peca;
use App\Models\ProcessoPeca;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Quais documentos do processo a OSC enxerga (módulo 3.3).
 *
 * A cliente pediu que, ao abrir a sua inscrição, a organização visse "todo o
 * processo: proposta, plano de trabalho, documentos da habilitação, pareceres
 * (orçamentário e jurídico) assinados, termo assinado, publicação", e sugeriu
 * uma tela em que a SCP escolhesse o que aparece.
 *
 * Até aqui a régua estava cravada no código: a página do chamamento mostrava
 * três tipos de documento e ponto. Agora cada peça carrega a sua marca, com um
 * padrão que já serve — aberto para o que decide e para o que é publicado,
 * fechado para a instrução interna (portarias, protocolos, empenho) — e a SCP
 * muda o que quiser na tela de curadoria.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pecas', function (Blueprint $table) {
            $table->boolean('visivel_osc')->default(true)->after('obrigatorio');
        });

        Schema::table('processo_pecas', function (Blueprint $table) {
            $table->boolean('visivel_osc')->default(true)->after('tipo');
        });

        // O padrão da coluna é "visível"; a instrução interna nasce fechada.
        DB::table('pecas')->whereIn('chave', Peca::INTERNAS)->update(['visivel_osc' => false]);
        DB::table('processo_pecas')->whereIn('tipo', ProcessoPeca::INTERNAS)->update(['visivel_osc' => false]);
    }

    public function down(): void
    {
        foreach (['pecas', 'processo_pecas'] as $tabela) {
            Schema::table($tabela, function (Blueprint $table) {
                $table->dropColumn('visivel_osc');
            });
        }
    }
};
