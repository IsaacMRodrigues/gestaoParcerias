<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Senha esquecida vira chamado de suporte, e senha definida por outra pessoa
 * é trocada no primeiro acesso.
 *
 * O "esqueci minha senha" do Breeze mandava um e-mail que o sistema não envia
 * (o envio está configurado para o log): a tela prometia um link que nunca
 * chegava. Agora o pedido abre um chamado de Acesso, sem login, e quem atende
 * define uma senha provisória.
 *
 * - users.deve_trocar_senha: a senha foi definida por outra pessoa (suporte,
 *   administrador, chefia, responsável legal da OSC) e é conhecida por ela.
 * - chamados.conta_id: a conta cuja senha o pedido quer trocar. Não é o autor:
 *   quem pede não está logado, e qualquer um pode digitar o e-mail de outro.
 * - chamados.contato: por onde a equipe confirma a identidade antes de passar
 *   a senha.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('deve_trocar_senha')->default(false)->after('password');
        });

        Schema::table('chamados', function (Blueprint $table) {
            $table->foreignId('conta_id')->nullable()->after('user_id')
                ->constrained('users')->nullOnDelete();
            $table->string('contato', 120)->nullable()->after('conta_id');
        });
    }

    public function down(): void
    {
        Schema::table('chamados', function (Blueprint $table) {
            $table->dropConstrainedForeignId('conta_id');
            $table->dropColumn('contato');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('deve_trocar_senha');
        });
    }
};
