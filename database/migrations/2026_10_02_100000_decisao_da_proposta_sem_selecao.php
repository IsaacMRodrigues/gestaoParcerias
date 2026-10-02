<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Decisão da UG sobre a proposta de dispensa ou inexigibilidade, que não passa pela Seleção:
 * quem decidiu, quando e, na reprovação, o motivo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('propostas', function (Blueprint $table) {
            $table->foreignId('decidida_por')->nullable()->after('submitted_at')->constrained('users')->nullOnDelete();
            $table->timestamp('decidida_em')->nullable()->after('decidida_por');
            $table->text('decisao_motivo')->nullable()->after('decidida_em');
        });
    }

    public function down(): void
    {
        Schema::table('propostas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('decidida_por');
            $table->dropColumn(['decidida_em', 'decisao_motivo']);
        });
    }
};
