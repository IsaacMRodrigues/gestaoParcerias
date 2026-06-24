<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('processo_pecas', function (Blueprint $table) {
            $table->string('codigo_validacao', 20)->nullable()->unique()->after('assinado_em');
        });
    }

    public function down(): void
    {
        Schema::table('processo_pecas', function (Blueprint $table) {
            $table->dropColumn('codigo_validacao');
        });
    }
};
