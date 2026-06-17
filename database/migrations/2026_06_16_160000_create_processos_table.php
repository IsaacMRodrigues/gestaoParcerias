<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('processos', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();           // ex.: 0001/2026
            $table->foreignId('orgao_id')->constrained()->cascadeOnDelete(); // Unidade Gestora
            $table->foreignId('created_by')->constrained('users');
            $table->string('status')->default('em_planejamento');
            $table->string('setor_atual')->default('ug');  // onde o processo está agora
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processos');
    }
};
