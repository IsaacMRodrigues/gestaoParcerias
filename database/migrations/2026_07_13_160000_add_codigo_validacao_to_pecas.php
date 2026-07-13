<?php

use App\Models\Peca;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Código de validação + QR para peças da Seleção (igual às peças do trâmite).
 * Backfill em peças já assinadas sem código.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pecas', function (Blueprint $table) {
            $table->string('codigo_validacao', 20)->nullable()->unique()->after('assinado_em');
        });

        Peca::whereNotNull('assinado_em')
            ->where(fn ($q) => $q->whereNull('codigo_validacao')->orWhere('codigo_validacao', ''))
            ->each(function (Peca $peca) {
                do {
                    $codigo = strtoupper(Str::random(4) . '-' . Str::random(4) . '-' . Str::random(2));
                } while (Peca::where('codigo_validacao', $codigo)->exists());

                $peca->update(['codigo_validacao' => $codigo]);
            });
    }

    public function down(): void
    {
        Schema::table('pecas', function (Blueprint $table) {
            $table->dropColumn('codigo_validacao');
        });
    }
};
