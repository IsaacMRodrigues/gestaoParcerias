<?php

use App\Models\Proposta;
use Illuminate\Database\Migrations\Migration;

/**
 * O instrumento passa a nascer na conclusão da Celebração. As que já terminaram sem ele ganham
 * o seu agora, com os mesmos dados (ver Proposta::criarInstrumento).
 */
return new class extends Migration
{
    public function up(): void
    {
        Proposta::whereNotNull('celebracao_concluida_em')->whereDoesntHave('instrumento')
            ->get()->each(fn (Proposta $p) => $p->criarInstrumento());
    }

    public function down(): void
    {
        // Os instrumentos criados aqui são dados da parceria: não se apagam na volta.
    }
};
