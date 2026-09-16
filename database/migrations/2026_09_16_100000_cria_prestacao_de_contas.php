<?php

/**
 * Prestação de contas (módulo 3.4).
 *
 * A OSC elabora e envia; a SCP faz a análise prévia; a Unidade Gestora, com o
 * Gestor da Parceria e a Comissão de Monitoramento, analisa e aprova.
 *
 * O que a cliente pediu, e que decide o formato destas tabelas: "estes modelos
 * devem ser para eles preencherem como campo" e "nas planilhas, coloquem
 * fórmula". Por isso o relatório financeiro não é texto — é dado. O total, o
 * saldo e o líquido são calculados a partir do que foi lançado, e o documento
 * que vai à assinatura nasce desses números.
 *
 * O que já existe não se digita de novo: os repasses e as despesas do período
 * vêm da Execução (tabelas `repasses` e `despesas`), e as metas do Relatório de
 * Execução do Objeto vêm do Plano de Trabalho.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prestacoes_contas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instrumento_id')->constrained('instrumentos')->cascadeOnDelete();

            // Parcial nº 1, 2, 3… ou final. É a divisão que o Anexo I e o REO
            // pedem logo no cabeçalho ("PRESTAÇÃO DE CONTAS: Parcial nº / Final").
            $table->enum('tipo', ['parcial', 'final'])->default('parcial');
            $table->unsignedSmallInteger('numero')->nullable();
            $table->date('periodo_inicio');
            $table->date('periodo_fim');
            $table->unsignedSmallInteger('parcelas_recebidas')->nullable();

            // Ofício de encaminhamento (Anexo I)
            $table->unsignedSmallInteger('folhas')->nullable();
            $table->string('responsavel_nome')->nullable();
            $table->string('responsavel_email')->nullable();
            $table->string('responsavel_telefone', 30)->nullable();

            // Relatório de Execução do Objeto (Anexo III)
            $table->text('objetivo_geral')->nullable();
            $table->text('objetivos_especificos')->nullable();
            $table->text('conclusao')->nullable();

            // Conciliação bancária (Anexo IV). O que o extrato mostra e o
            // sistema não tem como saber: saldo do período anterior, créditos
            // de outras origens, tarifas e devolução aos cofres públicos.
            $table->string('banco', 60)->nullable();
            $table->string('agencia', 20)->nullable();
            $table->string('conta_corrente', 30)->nullable();
            foreach (['saldo_anterior', 'outros_creditos', 'recursos_proprios',
                      'despesas_bancarias', 'valor_ressarcido'] as $campo) {
                $table->decimal($campo, 15, 2)->default(0);
            }

            // Resumo da folha de pagamento (Anexo 16) — o único modelo entregue
            // sem nenhuma fórmula: os totais eram todos digitados.
            $table->unsignedSmallInteger('folha_funcionarios')->default(0);
            foreach (['folha_salarios', 'folha_vantagens', 'folha_adicionais',
                      'folha_inss', 'folha_irrf', 'folha_plano_saude',
                      'folha_fgts', 'folha_ferias', 'folha_rescisao'] as $campo) {
                $table->decimal($campo, 15, 2)->default(0);
            }

            // Trâmite, na mesma forma dos demais fluxos (ver Peca).
            $table->unsignedTinyInteger('etapa')->default(0);
            $table->string('setor', 20)->default('osc');
            $table->timestamp('iniciada_em')->nullable();
            $table->timestamp('concluida_em')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['instrumento_id', 'periodo_fim']);
        });

        // Metas do REO: a quantidade prevista vem do Plano de Trabalho; a
        // atendida e a justificativa são da OSC.
        Schema::create('prestacao_metas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prestacao_id')->constrained('prestacoes_contas')->cascadeOnDelete();
            $table->foreignId('meta_id')->nullable()->constrained('metas')->nullOnDelete();
            $table->string('descricao');
            $table->string('quantidade_prevista', 60)->nullable();
            $table->string('quantidade_atendida', 60)->nullable();
            $table->boolean('cumpriu')->default(false);
            $table->text('justificativa')->nullable();
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->timestamps();
        });

        // Relatório de metas financeiras (Anexo VI): por natureza, o valor
        // aprovado no Plano de Trabalho e as glosas de cada mês do semestre.
        Schema::create('prestacao_glosas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prestacao_id')->constrained('prestacoes_contas')->cascadeOnDelete();
            $table->string('natureza', 30);
            $table->decimal('valor_aprovado', 15, 2)->default(0);
            foreach (range(1, 6) as $mes) {
                $table->decimal("glosa_mes_{$mes}", 15, 2)->default(0);
            }
            $table->timestamps();
            $table->unique(['prestacao_id', 'natureza']);
        });

        // Relação de bens móveis (Anexo VII).
        Schema::create('prestacao_bens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prestacao_id')->constrained('prestacoes_contas')->cascadeOnDelete();
            $table->string('especificacao');
            $table->decimal('quantidade', 12, 2)->default(1);
            $table->decimal('valor_unitario', 15, 2)->default(0);
            $table->string('documento', 60)->nullable();
            $table->timestamps();
        });

        Schema::create('prestacao_tramitacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prestacao_id')->constrained('prestacoes_contas')->cascadeOnDelete();
            $table->string('de_setor', 20);
            $table->string('para_setor', 20);
            $table->foreignId('enviado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('enviado_em');
            $table->text('parecer')->nullable();
            $table->string('status', 20)->default('enviado');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestacao_tramitacoes');
        Schema::dropIfExists('prestacao_bens');
        Schema::dropIfExists('prestacao_glosas');
        Schema::dropIfExists('prestacao_metas');
        Schema::dropIfExists('prestacoes_contas');
    }
};
