<?php

/**
 * Os treze perfis da equipe da OSC passam a existir como papéis.
 *
 * O módulo 1 pede, no cadastro de membros, "Perfil (com várias opções e
 * podendo marcar mais de 01)", e a tela de referência que acompanha o
 * documento traz a lista do convenente — de Cadastrador de Proposta a
 * Ordenador de Despesa. O sistema tinha só "Membro da OSC": qualquer
 * integrante era descrito da mesma forma, e o rodapé do que ele assinava
 * dizia apenas isso.
 *
 * Nenhum deles concede permissão. Do lado da OSC o perfil declara o que a
 * pessoa é e vira papel de assinatura; o que ela pode fazer continua nas
 * funções `osc_*`, marcadas uma a uma pelo responsável legal.
 */

use Database\Seeders\RolesSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // O seeder só cria o que falta e ressincroniza o que existe: chamá-lo
        // aqui traz os perfis novos sem mexer nos papéis já atribuídos.
        (new RolesSeeder)->run();
    }

    public function down(): void
    {
        // Sem volta: apagar um papel apagaria também a designação de quem o
        // tem, e é ela que explica a assinatura já dada num documento.
    }
};
