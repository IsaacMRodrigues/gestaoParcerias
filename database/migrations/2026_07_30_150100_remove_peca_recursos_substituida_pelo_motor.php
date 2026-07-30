<?php

use App\Models\Peca;
use Illuminate\Database\Migrations\Migration;

/**
 * A peça única "Recursos" do checklist de Seleção foi substituída pelo motor de
 * recursos (tabela `recursos`): cada OSC protocola o seu pelo portal e recebe
 * resposta própria da Unidade Gestora.
 *
 * Remove a peça apenas quando nada foi enviado nela — se alguém já anexou um
 * arquivo, a peça é mantida para não perder o documento.
 */
return new class extends Migration
{
    public function up(): void
    {
        Peca::where('categoria', 'chamamento_publico')
            ->where('chave', 'recursos')
            ->whereNull('arquivo_path')
            ->delete();

        // Realinha a ordem das peças restantes ao template atualizado.
        foreach (Peca::TEMPLATES['chamamento_publico'] as $ordem => $item) {
            Peca::where('categoria', 'chamamento_publico')
                ->where('chave', $item['chave'])
                ->update(['ordem' => $ordem]);
        }
    }

    public function down(): void
    {
        // Sem reversão: a peça foi substituída pelo motor de recursos.
    }
};
