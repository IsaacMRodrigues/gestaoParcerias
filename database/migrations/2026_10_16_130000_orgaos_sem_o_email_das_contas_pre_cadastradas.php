<?php

use App\Support\ContasPreCadastradas;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * O e-mail das Secretarias tinha vindo das contas pré-cadastradas, que foram apagadas: sai junto.
 * E-mail digitado à mão no órgão, diferente desses, fica.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('orgaos')->whereIn('email', ContasPreCadastradas::EMAILS)->update(['email' => null]);
    }

    public function down(): void
    {
    }
};
