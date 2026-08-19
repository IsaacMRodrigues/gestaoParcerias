<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Vínculo usuário → OSC.
 *
 * Até aqui a ligação existia só no sentido inverso (oscs.user_id), o que
 * amarrava uma OSC a exatamente UMA conta: cadastrar um segundo usuário
 * significaria reapontar oscs.user_id e, com isso, desvincular o primeiro.
 *
 * Mas OSC é organização, e organização tem equipe — quem escreve o projeto,
 * quem cuida da documentação, quem responde legalmente. Esta coluna passa a
 * carregar "de qual OSC este usuário faz parte"; oscs.user_id permanece, com o
 * sentido estrito de responsável legal (quem submete e assina pela entidade).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // nullOnDelete: excluída a OSC, a conta sobrevive órfã em vez de
            // sumir junto — usuário é rastro (assinou, enviou, tramitou).
            $table->foreignId('osc_id')->nullable()->after('orgao_id')
                  ->constrained('oscs')->nullOnDelete();
        });

        // Backfill: quem já é responsável legal passa a ter também o vínculo
        // direto, senão perderia o acesso ao portal no instante do deploy.
        foreach (DB::table('oscs')->whereNotNull('user_id')->get(['id', 'user_id']) as $osc) {
            DB::table('users')->where('id', $osc->user_id)->update(['osc_id' => $osc->id]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['osc_id']);
            $table->dropColumn('osc_id');
        });
    }
};
