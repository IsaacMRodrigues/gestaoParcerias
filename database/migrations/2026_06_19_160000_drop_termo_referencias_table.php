<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * O Termo de Referência virou uma peça (ProcessoPeca tipo 'termo_referencia'),
     * editável no editor rico como os demais documentos modelo.
     */
    public function up(): void
    {
        Schema::dropIfExists('termo_referencias');
    }

    public function down(): void
    {
        // tabela legada — não recriada (estrutura antiga descontinuada)
    }
};
