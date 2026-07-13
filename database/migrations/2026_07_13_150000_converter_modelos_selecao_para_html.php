<?php

use App\Models\Peca;
use Illuminate\Database\Migrations\Migration;

/**
 * Converte modelos da Seleção/Documentação de texto puro para HTML com brasão
 * (mesmo padrão das peças do trâmite). Só atualiza peças NÃO assinadas cujo
 * conteúdo ainda não é HTML (sem logo/brasão).
 */
return new class extends Migration
{
    public function up(): void
    {
        Peca::where('tipo', 'modelo')
            ->whereNull('assinado_em')
            ->get()
            ->each(function (Peca $peca) {
                $modelo = Peca::MODELO[$peca->categoria][$peca->chave] ?? null;
                if ($modelo === null) {
                    return;
                }

                $conteudo = $peca->conteudo ?? '';
                $jaHtml = str_contains($conteudo, 'pmsgra.net/logo.png')
                    || str_contains($conteudo, '<p')
                    || str_contains($conteudo, '<table');

                // vazio OU ainda texto puro do seed antigo → aplica HTML com brasão
                if ($conteudo === '' || ! $jaHtml) {
                    $peca->update(['conteudo' => $modelo]);
                }
            });
    }

    public function down(): void
    {
        // Não reverte (poderia apagar edições).
    }
};
