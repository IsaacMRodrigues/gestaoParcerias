<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * O Termo de Parceria assinado em sequência (decisão da gestão, 01/10/2026):
 * a SCP emite, sem assinar; assinam a OSC, o Responsável da UG, o Gestor da
 * Parceria (que a SCP escolhe ao encaminhar) e, por último, o Gabinete — e
 * entre uma assinatura e outra o Termo volta à SCP, que o encaminha.
 *
 * - peca_assinaturas: uma linha por assinatura de documento que tem várias,
 *   cada uma com o seu código de validação;
 * - propostas.celebracao_gestor_id: o Gestor escolhido pela SCP;
 * - a Celebração passa de 15 para 21 etapas: as seis novas entram depois da
 *   assinatura da OSC (antiga etapa 10), e as seguintes andam seis casas.
 *
 * O Termo já assinado do jeito antigo (SCP pelo Município e OSC na
 * contra-assinatura) continua valendo como está.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peca_assinaturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peca_id')->constrained('pecas')->cascadeOnDelete();
            $table->string('papel', 20);
            $table->unsignedTinyInteger('ordem');
            $table->foreignId('assinado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('assinante_nome')->nullable();
            $table->string('assinante_cargo')->nullable();
            $table->timestamp('assinado_em');
            $table->string('codigo_validacao', 20)->unique();
            $table->timestamps();
            $table->unique(['peca_id', 'papel']);
        });

        Schema::table('propostas', function (Blueprint $table) {
            $table->foreignId('celebracao_gestor_id')->nullable()->after('celebracao_setor')->constrained('users')->nullOnDelete();
        });

        DB::table('propostas')->where('celebracao_etapa', '>=', 10)->increment('celebracao_etapa', 6);
        DB::table('pecas')->where('pecaable_type', 'App\\Models\\Proposta')->where('categoria', 'celebracao')
            ->whereNotNull('etapa')->where('etapa', '>=', 10)->increment('etapa', 6);
    }

    public function down(): void
    {
        DB::table('propostas')->whereBetween('celebracao_etapa', [10, 15])->update(['celebracao_etapa' => 9, 'celebracao_setor' => 'osc']);
        DB::table('propostas')->where('celebracao_etapa', '>=', 16)->decrement('celebracao_etapa', 6);
        DB::table('pecas')->where('pecaable_type', 'App\\Models\\Proposta')->where('categoria', 'celebracao')
            ->whereNotNull('etapa')->where('etapa', '>=', 16)->decrement('etapa', 6);

        Schema::table('propostas', fn (Blueprint $table) => $table->dropConstrainedForeignId('celebracao_gestor_id'));
        Schema::dropIfExists('peca_assinaturas');
    }
};
