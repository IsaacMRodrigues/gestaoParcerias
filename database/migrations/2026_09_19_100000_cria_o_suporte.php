<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Painel de suporte: dúvidas, sugestões e problemas do sistema.
 *
 * Aberto a quem está logado — servidor ou organização —, e só a quem está
 * logado: é o próprio acesso ao sistema que filtra quem pode escrever, sem
 * formulário público a ser varrido por robô.
 *
 * Um chamado é uma conversa: o assunto e a categoria ficam na linha de cima, e
 * o texto — o de abertura e cada resposta — vive nas mensagens. Uma única
 * caixa, vista pela TI e pela SCP: a categoria ajuda a triar, mas nenhuma
 * pergunta fica esperando alguém reparar que caiu na fila errada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chamados', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 20)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Quem abriu, como estava no dia — mesma razão do carimbo de
            // assinatura: conta apagada ou renomeada não reescreve o histórico.
            $table->string('autor_nome');
            $table->string('autor_vinculo')->nullable();

            $table->string('categoria', 20);
            $table->string('assunto');
            $table->string('status', 20)->default('aberto');

            // A tela de onde o chamado foi aberto. Metade do suporte é
            // descobrir onde a pessoa estava quando aquilo aconteceu.
            $table->string('origem_url')->nullable();

            $table->timestamp('respondido_em')->nullable();
            $table->timestamp('resolvido_em')->nullable();
            $table->foreignId('resolvido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('chamado_mensagens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chamado_id')->constrained('chamados')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('autor_nome');
            $table->string('autor_vinculo')->nullable();
            $table->text('mensagem');

            // Nota interna: a equipe combina entre si sem abrir outro canal.
            // Quem abriu o chamado nunca a vê.
            $table->boolean('interna')->default(false);

            $table->string('arquivo_path')->nullable();
            $table->string('arquivo_nome')->nullable();
            $table->string('arquivo_mime')->nullable();
            $table->unsignedBigInteger('arquivo_tamanho')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chamado_mensagens');
        Schema::dropIfExists('chamados');
    }
};
