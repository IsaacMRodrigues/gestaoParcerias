<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Painel de lançamentos da prestação de contas: a OSC lança extratos, evidências, folha e bens ao
 * longo da execução (as despesas seguem na tabela delas), a Prefeitura confere cada um, e a
 * prestação se monta com o que foi lançado no período.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lancamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instrumento_id')->constrained('instrumentos')->cascadeOnDelete();
            $table->string('tipo', 30);
            $table->date('competencia');
            $table->text('descricao')->nullable();
            $table->foreignId('meta_id')->nullable()->constrained('metas')->nullOnDelete();
            $table->decimal('quantidade', 12, 2)->nullable();
            $table->decimal('valor', 15, 2)->nullable();
            $table->json('dados')->nullable();
            $table->string('arquivo_path')->nullable();
            $table->string('arquivo_nome')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('tamanho')->nullable();
            $table->string('situacao', 20)->default('pendente');
            $table->foreignId('conferido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('conferido_em')->nullable();
            $table->text('devolucao_motivo')->nullable();
            $table->foreignId('lancado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['instrumento_id', 'tipo', 'competencia']);
        });

        Schema::table('despesas', function (Blueprint $table) {
            $table->string('situacao', 20)->default('pendente')->after('nota_fiscal_nome');
            $table->foreignId('conferido_por')->nullable()->after('situacao')->constrained('users')->nullOnDelete();
            $table->timestamp('conferido_em')->nullable()->after('conferido_por');
            $table->text('devolucao_motivo')->nullable()->after('conferido_em');
            $table->foreignId('lancado_por')->nullable()->after('devolucao_motivo')->constrained('users')->nullOnDelete();
        });

        // As despesas de antes foram lançadas pela própria Prefeitura: já valem como conferidas.
        DB::table('despesas')->update(['situacao' => 'conferido', 'conferido_em' => DB::raw('updated_at')]);

        Schema::table('prestacao_bens', function (Blueprint $table) {
            $table->foreignId('lancamento_id')->nullable()->after('prestacao_id')->constrained('lancamentos')->nullOnDelete();
        });

        Schema::table('pecas', function (Blueprint $table) {
            $table->boolean('dos_lancamentos')->default(false)->after('osc_arquivo_id');
        });
    }

    public function down(): void
    {
        Schema::table('pecas', fn (Blueprint $table) => $table->dropColumn('dos_lancamentos'));
        Schema::table('prestacao_bens', fn (Blueprint $table) => $table->dropConstrainedForeignId('lancamento_id'));
        Schema::table('despesas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('conferido_por');
            $table->dropConstrainedForeignId('lancado_por');
            $table->dropColumn(['situacao', 'conferido_em', 'devolucao_motivo']);
        });
        Schema::dropIfExists('lancamentos');
    }
};
