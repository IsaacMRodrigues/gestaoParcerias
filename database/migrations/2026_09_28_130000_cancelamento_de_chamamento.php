<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cancelar o chamamento sem excluí-lo, podendo reabrir (decisão da gestão,
 * 28/09/2026).
 *
 * - chamamentos.status_antes_cancelar: o status a que a reabertura devolve.
 * - chamamento_cancelamentos: o histórico — cada cancelamento e cada
 *   reabertura, com o motivo (obrigatório) e quem fez. Um chamamento pode ser
 *   cancelado e reaberto mais de uma vez, e cada vez fica registrada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chamamentos', function (Blueprint $table) {
            $table->string('status_antes_cancelar', 20)->nullable()->after('status');
        });

        Schema::create('chamamento_cancelamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chamamento_id')->constrained()->cascadeOnDelete();
            $table->string('acao', 20); // cancelado | reaberto
            $table->text('motivo');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('autor_nome')->nullable(); // o nome de então, como nas assinaturas
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chamamento_cancelamentos');

        Schema::table('chamamentos', function (Blueprint $table) {
            $table->dropColumn('status_antes_cancelar');
        });
    }
};
