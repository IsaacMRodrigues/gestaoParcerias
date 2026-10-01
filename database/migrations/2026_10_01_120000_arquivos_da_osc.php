<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "Arquivos da OSC" (pedido da gestão, 30/09/2026, com o portal do DF como
 * modelo): a OSC anexa uma vez as certidões negativas, o estatuto, a ata de
 * eleição e as declarações, e eles valem para todas as parcerias. Cada envio
 * é uma versão nova; as anteriores ficam no histórico. A análise da Prefeitura
 * é por parceria (osc_arquivo_analises), sobre a versão que ela viu.
 *
 * Sai da Celebração o que passou para cá: as certidões e as seis declarações.
 * As peças ainda não entregues são apagadas; as entregues ficam como
 * histórico, sem ser obrigatórias.
 */
return new class extends Migration
{
    private const SAEM_DA_CELEBRACAO = [
        'certidoes_habilitacao', 'decl_art7', 'decl_art33', 'decl_art34', 'decl_art39', 'decl_art45', 'decl_autenticidade',
    ];

    public function up(): void
    {
        Schema::create('osc_arquivos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('osc_id')->constrained('oscs')->cascadeOnDelete();
            $table->string('tipo', 40);
            $table->unsignedSmallInteger('versao');
            $table->string('arquivo_path');
            $table->string('arquivo_nome');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('tamanho')->nullable();
            $table->date('validade')->nullable();
            $table->timestamp('aviso_vencimento_em')->nullable();
            $table->foreignId('enviado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['osc_id', 'tipo', 'versao']);
        });

        Schema::create('osc_arquivo_analises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposta_id')->constrained('propostas')->cascadeOnDelete();
            $table->foreignId('osc_arquivo_id')->constrained('osc_arquivos')->cascadeOnDelete();
            $table->string('situacao', 20);
            $table->text('motivo')->nullable();
            $table->foreignId('analisado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('analisado_em');
            $table->timestamps();
            $table->unique(['proposta_id', 'osc_arquivo_id']);
        });

        $pecas = DB::table('pecas')->where('categoria', 'celebracao')->whereIn('chave', self::SAEM_DA_CELEBRACAO);
        (clone $pecas)->whereNull('assinado_em')->whereNull('arquivo_path')->delete();
        (clone $pecas)->update(['obrigatorio' => false]);
    }

    public function down(): void
    {
        DB::table('pecas')->where('categoria', 'celebracao')->whereIn('chave', self::SAEM_DA_CELEBRACAO)->update(['obrigatorio' => true]);
        Schema::dropIfExists('osc_arquivo_analises');
        Schema::dropIfExists('osc_arquivos');
    }
};
