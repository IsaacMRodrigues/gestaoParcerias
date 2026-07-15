<?php

use App\Models\Peca;
use App\Models\Processo;
use Illuminate\Database\Migrations\Migration;

/**
 * Unifica a Seleção 2.2 no Chamamento gerado (não mais no Processo).
 *
 * A ponte anterior ancorava o checklist da dispensa no próprio Processo
 * (`pecasSelecao`), mas todo processo concluído agora gera um Chamamento que já
 * carrega a Seleção — o que criava DOIS checklists para a mesma parceria. Aqui
 * removemos as peças de Seleção ancoradas no Processo (duplicatas), preservando
 * qualquer uma eventualmente assinada por segurança.
 */
return new class extends Migration
{
    public function up(): void
    {
        Peca::where('pecaable_type', Processo::class)
            ->whereNull('assinado_em')
            ->delete();
    }

    public function down(): void
    {
        // Sem reversão: as peças de Seleção passam a viver no Chamamento.
    }
};
