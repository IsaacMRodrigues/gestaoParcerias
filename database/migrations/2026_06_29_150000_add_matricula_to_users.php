<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Matrícula funcional do servidor (única). Nullable para não afetar os
 * usuários já existentes; obrigatória nos formulários que a exigem.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('matricula', 50)->nullable()->unique()->after('cpf');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['matricula']);
            $table->dropColumn('matricula');
        });
    }
};
