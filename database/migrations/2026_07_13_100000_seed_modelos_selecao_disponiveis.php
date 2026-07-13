<?php

use App\Models\Peca;
use Illuminate\Database\Migrations\Migration;

/**
 * Semeia textos-modelo nas peças da Seleção/Documentação que ainda estavam
 * em branco, a partir dos arquivos oficiais disponíveis (IV, VI/XI, VII e
 * esqueleto de Edital). Categorias: chamamento_publico, dispensa_inexigibilidade
 * e aditivo. Conservador: nunca sobrescreve conteúdo já preenchido ou assinado.
 */
return new class extends Migration
{
    public function up(): void
    {
        $categorias = array_keys(Peca::MODELO);

        Peca::whereIn('categoria', $categorias)
            ->where('tipo', 'modelo')
            ->where(fn ($q) => $q->whereNull('conteudo')->orWhere('conteudo', ''))
            ->whereNull('assinado_em')
            ->get()
            ->each(function (Peca $peca) {
                $modelo = Peca::MODELO[$peca->categoria][$peca->chave] ?? null;
                if ($modelo !== null) {
                    $peca->update(['conteudo' => $modelo]);
                }
            });
    }

    public function down(): void
    {
        // Não reverte o conteúdo (poderia apagar edições do usuário).
    }
};
