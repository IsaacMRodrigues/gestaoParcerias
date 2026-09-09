<?php

/**
 * Processos parados esperando que um setor recebesse a própria remessa.
 *
 * No Planejamento há duas etapas seguidas da SCP — analisar o Ofício e o Termo
 * de Referência, e depois protocolar o Pedido de Parecer à SEPLAN. Como toda
 * movimentação nascia "enviada", ao concluir a primeira a SCP mandava o
 * processo para si mesma e a tela passava a exigir "Registre o recebimento"
 * antes de deixar continuar. O trâmite segue daqui para a frente (ver
 * TramitacaoController::chegadaNoProprioSetor), e o que já está registrado
 * precisa ser destravado.
 *
 * Dá o recebimento por feito no mesmo instante do envio, que é o que de fato
 * ocorreu: o processo não saiu da mesa de ninguém. Quem enviou consta como
 * quem recebeu — é a mesma pessoa, e inventar outro nome no histórico seria
 * pior do que repetir o dela.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('tramitacoes')
            ->whereColumn('de_setor', 'para_setor')
            ->whereNull('recebido_em')
            ->update([
                'status'       => 'recebido',
                'recebido_em'  => DB::raw('enviado_em'),
                'recebido_por' => DB::raw('enviado_por'),
            ]);
    }

    public function down(): void
    {
        // Sem volta: devolver o recebimento travaria de novo os processos.
    }
};
