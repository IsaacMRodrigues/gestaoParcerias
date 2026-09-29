<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * O Plano de Trabalho à risca do modelo da cliente (Docs. Desenvolvimento/
 * Planodetrabalho.docx, recebido em 29/09/2026), sem novidades nossas.
 *
 * O que o modelo pede e não guardávamos:
 * - objetivos específicos (item 4) e metodologia (item 5);
 * - o objetivo específico de cada meta (item 7, 1ª coluna);
 * - o valor estimado de cada atividade (item 10);
 * - contrapartida não financeira (item 8) e equipe (item 12);
 * - desembolso por meta e parcela (item 11), no lugar de ano e mês.
 *
 * E a natureza da despesa passa a ser a lista de 12 do item 9, no plano, na
 * execução e na prestação de contas. Das 7 antigas, 4 existem no modelo e
 * mantêm a chave; "Recursos Humanos / Folha" e "Encargos e Tributos" vão para
 * as equivalentes do modelo; "Outros", que não tem equivalente, fica só nos
 * registros antigos.
 *
 * Endereços de execução, contrapartida em dinheiro, outras fontes e atuação em
 * rede saem da tela e do documento, mas as colunas ficam: nada é apagado.
 */
return new class extends Migration
{
    private const NATUREZAS_REMAPEADAS = [
        'recursos_humanos' => 'contratacao_tempo_determinado',
        'encargos'         => 'encargos_patronais',
    ];

    public function up(): void
    {
        foreach (['propostas', 'manifestacoes_interesse'] as $tabela) {
            Schema::table($tabela, function (Blueprint $table) {
                $table->text('objetivos_especificos')->nullable()->after('objetivos');
                $table->text('metodologia')->nullable()->after('objetivos_especificos');
            });
        }

        Schema::table('metas', function (Blueprint $table) {
            $table->text('objetivo_especifico')->nullable()->after('numero');
        });

        Schema::table('etapas', function (Blueprint $table) {
            $table->decimal('valor', 15, 2)->nullable()->after('data_fim');
        });

        Schema::table('plano_desembolsos', function (Blueprint $table) {
            $table->foreignId('meta_id')->nullable()->after('manifestacao_id')->constrained('metas')->cascadeOnDelete();
            $table->unsignedSmallInteger('parcela')->nullable()->after('meta_id');
            $table->unsignedSmallInteger('ano')->nullable()->change();
            $table->unsignedTinyInteger('mes')->nullable()->change();
        });

        // As parcelas antigas eram por mês: viram 1ª, 2ª, 3ª… na ordem do
        // calendário. A meta só dá para saber quando o plano tem uma meta só;
        // nos demais, a OSC a indica ao revisar o plano.
        $grupos = DB::table('plano_desembolsos')->orderBy('ano')->orderBy('mes')->orderBy('id')->get()
            ->groupBy(fn ($d) => $d->proposta_id ? 'p' . $d->proposta_id : 'm' . $d->manifestacao_id);
        foreach ($grupos as $parcelas) {
            $primeira = $parcelas->first();
            $chave = $primeira->proposta_id ? 'proposta_id' : 'manifestacao_id';
            $metas = DB::table('metas')->where($chave, $primeira->{$chave})->pluck('id');

            foreach ($parcelas->values() as $n => $d) {
                DB::table('plano_desembolsos')->where('id', $d->id)->update([
                    'parcela' => $n + 1,
                    'meta_id' => $metas->count() === 1 ? $metas->first() : null,
                ]);
            }
        }

        Schema::create('plano_contrapartidas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposta_id')->nullable()->constrained('propostas')->cascadeOnDelete();
            $table->foreignId('manifestacao_id')->nullable()->constrained('manifestacoes_interesse')->cascadeOnDelete();
            $table->unsignedSmallInteger('numero');
            $table->string('descricao');
            $table->string('quantidade', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('plano_equipe', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposta_id')->nullable()->constrained('propostas')->cascadeOnDelete();
            $table->foreignId('manifestacao_id')->nullable()->constrained('manifestacoes_interesse')->cascadeOnDelete();
            $table->string('cargo_funcao');
            $table->string('formacao')->nullable();
            $table->string('carga_horaria_mensal', 50)->nullable();
            $table->string('vinculo', 20);
            $table->timestamps();
        });

        foreach (self::NATUREZAS_REMAPEADAS as $antiga => $nova) {
            DB::table('despesas')->where('natureza', $antiga)->update(['natureza' => $nova]);
            DB::table('plano_itens')->where('tipo_despesa', $antiga)->update(['tipo_despesa' => $nova]);
        }
    }

    public function down(): void
    {
        foreach (self::NATUREZAS_REMAPEADAS as $antiga => $nova) {
            DB::table('despesas')->where('natureza', $nova)->update(['natureza' => $antiga]);
            DB::table('plano_itens')->where('tipo_despesa', $nova)->update(['tipo_despesa' => $antiga]);
        }

        Schema::dropIfExists('plano_equipe');
        Schema::dropIfExists('plano_contrapartidas');

        // Parcela sem mês não cabe no esquema antigo: melhor parar do que
        // inventar uma data.
        if (DB::table('plano_desembolsos')->whereNull('ano')->orWhereNull('mes')->exists()) {
            throw new RuntimeException('Há parcelas de desembolso sem ano e mês (lançadas por meta e parcela); não dá para voltar ao esquema antigo sem perdê-las.');
        }

        Schema::table('plano_desembolsos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('meta_id');
            $table->dropColumn('parcela');
            $table->unsignedSmallInteger('ano')->nullable(false)->change();
            $table->unsignedTinyInteger('mes')->nullable(false)->change();
        });

        Schema::table('etapas', fn (Blueprint $table) => $table->dropColumn('valor'));
        Schema::table('metas', fn (Blueprint $table) => $table->dropColumn('objetivo_especifico'));

        foreach (['propostas', 'manifestacoes_interesse'] as $tabela) {
            Schema::table($tabela, fn (Blueprint $table) => $table->dropColumn(['objetivos_especificos', 'metodologia']));
        }
    }
};
