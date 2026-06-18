<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Código da Unidade Gestora (4 dígitos, ex.: 0206 = Saúde).
     * Usado para compor o número do processo: UG.Sequencial.Ano.Esfera.
     */
    public function up(): void
    {
        Schema::table('orgaos', function (Blueprint $table) {
            $table->string('codigo', 4)->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('orgaos', function (Blueprint $table) {
            $table->dropUnique(['codigo']);
            $table->dropColumn('codigo');
        });
    }
};
