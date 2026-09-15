<?php

/**
 * A SCP passa a ter acesso à Execução.
 *
 * O perfil Analista Técnico do SCP tinha só planejamento e chamamentos, e o
 * item Execução do menu aparecia com cadeado para quem conduz a parceria do
 * edital ao empenho — e continua nela depois: emite a Ordem de Pagamento e
 * analisa as alterações. Acesso completo, por decisão da gestão: ver, lançar,
 * editar e apagar repasses e despesas.
 *
 * O RolesSeeder é quem guarda a matriz perfil → permissões; rodá-lo aqui
 * aplica a mudança a bancos que já existem, sem esperar um db:seed manual.
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
        // Sem volta automática: retirar o acesso é decisão, não desfazimento.
    }
};
