<?php

/**
 * Quem participa do fluxo da prestação de contas passa a enxergá-lo.
 *
 * A permissão `prestacao_contas` existia desde o começo, mas só em quem
 * presta contas por ofício — o Analista de Prestação de Contas Prévia, o
 * Contador, o administrador e a auditoria. O fluxo do módulo 3.4, porém,
 * atravessa a SCP (análise prévia) e termina na Unidade Gestora, com o Gestor
 * da Parceria e a Comissão de Monitoramento: sem a permissão, esses três
 * abriam o menu e viam cadeado justamente no item que precisam decidir.
 */

use Database\Seeders\RolesSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        (new RolesSeeder)->run();
    }

    public function down(): void
    {
        // Sem volta automática: retirar acesso é decisão, não desfazimento.
    }
};
