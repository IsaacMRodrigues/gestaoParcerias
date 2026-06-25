<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 4.4 Execução — repasses recebidos pela OSC durante a vigência do instrumento.
     */
    public function up(): void
    {
        Schema::create('repasses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instrumento_id')->constrained('instrumentos')->cascadeOnDelete();
            $table->unsignedInteger('parcela')->nullable();
            $table->date('data_repasse');
            $table->decimal('valor', 15, 2);
            $table->string('documento')->nullable();   // nº da ordem bancária / referência
            $table->string('observacao')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repasses');
    }
};
