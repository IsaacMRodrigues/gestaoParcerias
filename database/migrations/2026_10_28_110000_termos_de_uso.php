<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Termos de Uso: cada aceite fica registrado (termos_aceites) e a conta guarda a versão que aceitou. As contas que já
 * existem começam sem aceite e aceitam no próximo acesso.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('termos_aceites', function (Blueprint $table) {
            $table->id();
            // Sem chave estrangeira: o registro do aceite fica mesmo se a conta sair (nome e e-mail vão junto).
            $table->unsignedBigInteger('user_id')->index();
            $table->string('nome');
            $table->string('email');
            $table->string('versao', 20);
            $table->char('hash_texto', 64);
            $table->string('origem', 20);
            $table->string('ip', 45)->nullable();
            $table->text('navegador')->nullable();
            $table->dateTime('aceito_em');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('termos_versao', 20)->nullable();
            $table->timestamp('termos_aceitos_em')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['termos_versao', 'termos_aceitos_em']);
        });
        Schema::dropIfExists('termos_aceites');
    }
};
