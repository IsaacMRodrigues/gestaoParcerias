<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fluxo de aprovação de cadastro:
 *  - approval_status: pendente | aprovado | recusado (default aprovado, p/ não travar os já existentes)
 *  - approved_at / approved_by: quem e quando aprovou
 *  - created_by: a UG que criou o subusuário (null p/ auto-cadastro e criação pelo admin)
 *  - solicitacao_obs: justificativa informada no cadastro
 *  - rejeitado_motivo: motivo da recusa
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('approval_status')->default('aprovado')->after('status');
            $table->timestamp('approved_at')->nullable()->after('approval_status');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->after('approved_by')->constrained('users')->nullOnDelete();
            $table->text('solicitacao_obs')->nullable()->after('created_by');
            $table->text('rejeitado_motivo')->nullable()->after('solicitacao_obs');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['approval_status', 'approved_at', 'solicitacao_obs', 'rejeitado_motivo']);
        });
    }
};
