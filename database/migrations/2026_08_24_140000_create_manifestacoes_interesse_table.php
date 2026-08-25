<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Manifestação de Interesse (MROSC, arts. 18–21 da Lei 13.019/2014).
 *
 * A OSC propõe uma parceria sem que exista chamamento aberto. A Secretaria da
 * área diz se há interesse público e orçamento; a SCP decide o encaminhamento —
 * dispensa ou inexigibilidade de chamamento — e é esse deferimento que faz
 * nascer o chamamento e a proposta que seguem pelo fluxo de sempre.
 *
 * Fica em tabela própria, e não como proposta sem chamamento, porque
 * `propostas.chamamento_id` é a espinha por onde o sistema inteiro descobre o
 * órgão dono (visibilidade, caixa de entrada, Celebração, minuta). Proposta sem
 * chamamento seria uma proposta órfã de órgão em todas essas telas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manifestacoes_interesse', function (Blueprint $table) {
            $table->id();
            $table->foreignId('osc_id')->constrained('oscs')->cascadeOnDelete();
            // Secretaria a que a OSC dirige a proposta: é quem dirá se há
            // interesse público na área.
            $table->foreignId('orgao_id')->constrained('orgaos');

            $table->string('titulo');
            $table->text('objeto');
            $table->text('justificativa');
            $table->text('publico_alvo')->nullable();
            $table->decimal('valor_solicitado', 15, 2)->default(0);
            $table->decimal('valor_proprio', 15, 2)->nullable();
            $table->date('data_inicio_prevista')->nullable();
            $table->date('data_fim_prevista')->nullable();

            // rascunho → submetida (SCP) → em_analise (UG) → analisada (SCP) →
            // deferida | indeferida
            $table->string('status', 20)->default('rascunho');
            // Setor com a vez, no mesmo espírito dos demais trâmites: alimenta a
            // caixa de entrada sem inventar um mapa de etapas para duas idas.
            $table->string('setor_atual', 20)->nullable();
            $table->timestamp('submetida_em')->nullable();

            // Manifestação técnica da Secretaria
            $table->boolean('parecer_favoravel')->nullable();
            $table->text('parecer_ug')->nullable();
            $table->foreignId('parecer_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('parecer_em')->nullable();

            // Decisão da SCP
            $table->string('decisao', 20)->nullable();   // dispensa | inexigibilidade | indeferida
            $table->text('decisao_motivo')->nullable();
            $table->foreignId('decidida_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decidida_em')->nullable();

            // O que o deferimento gerou — o elo com o fluxo de sempre
            $table->foreignId('chamamento_id')->nullable()->constrained('chamamentos')->nullOnDelete();
            $table->foreignId('proposta_id')->nullable()->constrained('propostas')->nullOnDelete();

            $table->timestamps();

            $table->index(['status', 'setor_atual']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manifestacoes_interesse');
    }
};
