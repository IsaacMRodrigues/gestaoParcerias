<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * O Plano de Trabalho do jeito que o modelo 3.1 pede.
 *
 * Até aqui o plano era título, objeto, valores, datas e uma lista de metas. O
 * modelo da cliente pede bem mais, e a falta não era cosmética: o Parecer
 * Financeiro e o Jurídico afirmam ter analisado o **plano de aplicação** e o
 * **cronograma de desembolso**, e a prestação de contas compara o executado com
 * o que foi *aprovado* por natureza de despesa — três coisas que o sistema não
 * guardava em lugar nenhum.
 *
 * As três listas novas (aplicação, desembolso, endereços) repetem o padrão das
 * metas: nascem na manifestação ou na proposta e, no deferimento, a mesma linha
 * passa para a proposta criada. Por isso as duas chaves, uma nula de cada vez.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('propostas', function (Blueprint $table) {
            $table->text('descricao_realidade')->nullable()->after('objeto');
            $table->text('publico_alvo')->nullable()->after('descricao_realidade');
            $table->text('objetivos')->nullable()->after('justificativa');
            $table->decimal('valor_outras_fontes', 15, 2)->default(0)->after('valor_proprio');
            $table->unsignedSmallInteger('vigencia_dias')->nullable()->after('data_fim_prevista');
            // Atuação em rede (art. 35-A da Lei 13.019/2014): a OSC celebrante
            // executa com uma não celebrante, e o termo entre elas é o que
            // autoriza. Os campos só aparecem quando a resposta é sim.
            $table->boolean('atuacao_rede')->default(false);
            $table->string('rede_cnpj', 18)->nullable();
            $table->string('rede_razao_social')->nullable();
            $table->string('rede_municipio')->nullable();
            $table->date('rede_data_termo')->nullable();
        });

        Schema::table('manifestacoes_interesse', function (Blueprint $table) {
            $table->text('descricao_realidade')->nullable()->after('objeto');
            $table->text('objetivos')->nullable()->after('justificativa');
            $table->decimal('valor_outras_fontes', 15, 2)->default(0)->after('valor_proprio');
            $table->unsignedSmallInteger('vigencia_dias')->nullable()->after('data_fim_prevista');
            $table->boolean('atuacao_rede')->default(false);
            $table->string('rede_cnpj', 18)->nullable();
            $table->string('rede_razao_social')->nullable();
            $table->string('rede_municipio')->nullable();
            $table->date('rede_data_termo')->nullable();
        });

        // A tabela de metas do modelo tem nove colunas; tínhamos cinco.
        Schema::table('metas', function (Blueprint $table) {
            $table->text('atividades')->nullable()->after('descricao');
            $table->text('meios_verificacao')->nullable()->after('indicador');
            $table->text('resultados_esperados')->nullable()->after('meios_verificacao');
            $table->decimal('valor', 15, 2)->default(0)->after('resultados_esperados');
        });

        Schema::create('plano_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposta_id')->nullable()->constrained('propostas')->cascadeOnDelete();
            $table->foreignId('manifestacao_id')->nullable()->constrained('manifestacoes_interesse')->cascadeOnDelete();
            $table->unsignedSmallInteger('numero');
            $table->string('descricao');
            // Mesma lista de Despesa::NATUREZAS, de propósito: é ela que torna
            // comparável o aprovado aqui e o executado na prestação de contas.
            $table->string('tipo_despesa');
            $table->string('unidade', 30)->nullable();
            $table->decimal('quantidade', 12, 2)->default(1);
            $table->decimal('valor_unitario', 15, 2)->default(0);
            $table->string('atividades_vinculadas')->nullable();
            $table->timestamps();
        });

        Schema::create('plano_desembolsos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposta_id')->nullable()->constrained('propostas')->cascadeOnDelete();
            $table->foreignId('manifestacao_id')->nullable()->constrained('manifestacoes_interesse')->cascadeOnDelete();
            $table->unsignedSmallInteger('ano');
            $table->unsignedTinyInteger('mes');
            $table->decimal('valor', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('plano_enderecos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposta_id')->nullable()->constrained('propostas')->cascadeOnDelete();
            $table->foreignId('manifestacao_id')->nullable()->constrained('manifestacoes_interesse')->cascadeOnDelete();
            $table->string('descricao')->nullable();
            $table->string('endereco');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plano_enderecos');
        Schema::dropIfExists('plano_desembolsos');
        Schema::dropIfExists('plano_itens');

        Schema::table('metas', function (Blueprint $table) {
            $table->dropColumn(['atividades', 'meios_verificacao', 'resultados_esperados', 'valor']);
        });

        foreach (['propostas', 'manifestacoes_interesse'] as $tabela) {
            Schema::table($tabela, function (Blueprint $table) use ($tabela) {
                $colunas = [
                    'descricao_realidade', 'objetivos', 'valor_outras_fontes', 'vigencia_dias',
                    'atuacao_rede', 'rede_cnpj', 'rede_razao_social', 'rede_municipio', 'rede_data_termo',
                ];
                if ($tabela === 'propostas') {
                    $colunas[] = 'publico_alvo';
                }
                $table->dropColumn($colunas);
            });
        }
    }
};
