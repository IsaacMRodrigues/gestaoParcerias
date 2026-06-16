<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('oscs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('tipo')->nullable();
            $table->string('cnpj', 18)->unique();
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('cep', 9)->nullable();
            $table->string('logradouro')->nullable();
            $table->string('numero', 20)->nullable();
            $table->string('complemento')->nullable();
            $table->string('bairro')->nullable();
            $table->string('cidade')->nullable();
            $table->string('estado', 2)->nullable();
            // Responsável legal
            $table->string('resp_nome')->nullable();
            $table->string('resp_cpf', 14)->nullable();
            $table->string('resp_email')->nullable();
            $table->string('resp_phone', 20)->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oscs');
    }
};
