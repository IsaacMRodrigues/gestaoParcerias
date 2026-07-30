<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recursos administrativos contra o resultado provisório do Chamamento Público
 * (Fluxo Seleção): a OSC protocola eletronicamente pelo PGP — como promete o
 * próprio modelo do Resultado Provisório — e a Unidade Gestora emite a resposta.
 *
 * Substitui a peça única "Recursos" do checklist, que não dava conta de vários
 * recursos de OSCs diferentes, cada um com a sua resposta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recursos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chamamento_id')->constrained('chamamentos')->cascadeOnDelete();
            $table->foreignId('osc_id')->constrained('oscs')->cascadeOnDelete();
            $table->foreignId('proposta_id')->nullable()->constrained('propostas')->nullOnDelete();

            // Peça recursal enviada pela OSC (PDF único, conforme o edital)
            $table->text('fundamentacao')->nullable();
            $table->string('arquivo_path')->nullable();
            $table->string('arquivo_nome')->nullable();
            $table->unsignedBigInteger('tamanho')->nullable();
            $table->string('mime_type')->nullable();
            $table->foreignId('protocolado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('protocolado_em')->nullable();

            // Resposta da Unidade Gestora
            $table->string('resultado', 20)->nullable(); // provido | parcial | improvido
            $table->longText('resposta')->nullable();
            $table->foreignId('respondido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('respondido_em')->nullable();
            $table->string('codigo_validacao')->nullable();

            $table->timestamps();

            $table->unique(['chamamento_id', 'osc_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recursos');
    }
};
