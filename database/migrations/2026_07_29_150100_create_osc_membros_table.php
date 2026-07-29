<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Membros da OSC (Módulo 1.2): diretoria/quadro da organização.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('osc_membros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('osc_id')->constrained('oscs')->cascadeOnDelete();
            $table->string('nome');
            $table->string('cpf', 14)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('cargo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('osc_membros');
    }
};
