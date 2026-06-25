<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 4.4 Execução — despesas da OSC com os recursos da parceria (com nota fiscal).
     */
    public function up(): void
    {
        Schema::create('despesas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instrumento_id')->constrained('instrumentos')->cascadeOnDelete();
            $table->date('data_despesa');
            $table->decimal('valor', 15, 2);
            $table->string('natureza');                 // ver Despesa::NATUREZAS
            $table->string('fornecedor')->nullable();
            $table->string('doc_fornecedor', 18)->nullable();  // CNPJ/CPF
            $table->string('descricao')->nullable();
            $table->string('nota_fiscal_numero')->nullable();
            $table->string('nota_fiscal_path')->nullable();
            $table->string('nota_fiscal_nome')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('despesas');
    }
};
