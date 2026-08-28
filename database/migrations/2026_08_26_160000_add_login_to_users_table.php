<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nome de usuário para entrar, ao lado do e-mail.
 *
 * O sistema autenticava só por e-mail, e a conta principal de administração
 * precisa de um identificador curto ("admin_parcerias") que não é endereço de
 * ninguém. A saída óbvia — gravar isso na coluna `email` — quebraria o que
 * depende dela ser um e-mail de verdade: recuperação de senha, notificações e
 * a própria validação do cadastro.
 *
 * Nulo para todo mundo: quem tem e-mail continua entrando por ele, e nada muda
 * para as contas existentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('login', 50)->nullable()->unique()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['login']);
            $table->dropColumn('login');
        });
    }
};
