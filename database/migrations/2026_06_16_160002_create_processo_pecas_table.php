<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('processo_pecas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('processo_id')->constrained()->cascadeOnDelete();
            $table->string('tipo');                 // oficio, parecer_financeiro, abertura
            $table->longText('conteudo')->nullable();
            $table->foreignId('assinado_por')->nullable()->constrained('users');
            $table->timestamp('assinado_em')->nullable();
            $table->timestamps();

            $table->unique(['processo_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processo_pecas');
    }
};
