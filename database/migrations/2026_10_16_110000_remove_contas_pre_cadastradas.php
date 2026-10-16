<?php

use App\Support\ContasPreCadastradas;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

/**
 * Saem as contas que o sistema criava prontas (menos o administrador e a SCP): cada servidor,
 * secretário inclusive, faz o próprio cadastro e a SCP aprova. Ver ContasPreCadastradas.
 * Não se desfaz: a conta apagada não volta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Log::info('Contas pré-cadastradas removidas', ContasPreCadastradas::remover());
    }

    public function down(): void
    {
    }
};
