<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Compõe o número do processo no formato UG.Sequencial.Ano.Esfera
     * (ex.: 0206.0133.2026.01).
     *  - sequencial: contador contínuo e global (nunca reinicia)
     *  - esfera: esfera do concedente (01=Município, 02=Estado, 03=União, 04=Outros)
     */
    public function up(): void
    {
        Schema::table('processos', function (Blueprint $table) {
            $table->unsignedInteger('sequencial')->nullable()->unique()->after('numero');
            $table->string('esfera', 2)->default('01')->after('sequencial');
        });
    }

    public function down(): void
    {
        Schema::table('processos', function (Blueprint $table) {
            $table->dropUnique(['sequencial']);
            $table->dropColumn(['sequencial', 'esfera']);
        });
    }
};
