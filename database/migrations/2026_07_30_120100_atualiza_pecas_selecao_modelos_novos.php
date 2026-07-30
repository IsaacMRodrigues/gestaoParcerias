<?php

use App\Models\Peca;
use Illuminate\Database\Migrations\Migration;

/**
 * Alinha as peças de Seleção já criadas ao template atualizado (Fluxo Seleção
 * do cliente): entram o Relatório da Comissão e a Ata, e o Resultado provisório,
 * o Resultado definitivo e o Termo de Adjudicação e Homologação deixam de ser
 * "arquivo" para virar "modelo padrão" assinável, com o texto oficial semeado.
 *
 * `Peca::sincronizar()` usa firstOrCreate, portanto não atualiza rótulo, tipo,
 * obrigatoriedade nem ordem das peças que já existem — daí esta migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        $categoria = 'chamamento_publico';
        $template  = Peca::TEMPLATES[$categoria];
        $modelos   = Peca::MODELO[$categoria] ?? [];

        foreach ($template as $ordem => $item) {
            $chave = $item['chave'];

            Peca::where('categoria', $categoria)->where('chave', $chave)->get()
                ->each(function (Peca $peca) use ($item, $ordem, $modelos, $chave) {
                    $peca->rotulo      = $item['rotulo'];
                    $peca->obrigatorio = $item['obrigatorio'] ?? true;
                    $peca->ordem       = $ordem;

                    // Só converte arquivo → modelo se nada foi enviado ainda:
                    // uma peça com arquivo anexado permanece como arquivo para
                    // não deixar o upload órfão.
                    if ($item['tipo'] === 'modelo' && $peca->tipo === 'arquivo' && $peca->temArquivo()) {
                        $peca->save();
                        return;
                    }

                    $peca->tipo = $item['tipo'];

                    // Semeia o texto-modelo quando a peça está sem conteúdo.
                    if ($item['tipo'] === 'modelo' && empty($peca->conteudo) && isset($modelos[$chave])) {
                        $peca->conteudo = $modelos[$chave];
                    }

                    $peca->save();
                });
        }

        // Cria as peças novas (Relatório da Comissão e Ata) nos chamamentos já
        // existentes — nos novos elas nascem pelo `sincronizar()`.
        \App\Models\Chamamento::where('tipo', 'chamamento_publico')
            ->get()
            ->each(fn ($chamamento) => Peca::sincronizar($chamamento, $categoria));

        // Aprovação do Plano de Trabalho (Celebração) ganhou texto-modelo.
        Peca::where('categoria', 'dispensa_inexigibilidade')
            ->where('chave', 'aprovacao_plano')
            ->where(fn ($q) => $q->whereNull('conteudo')->orWhere('conteudo', ''))
            ->update(['conteudo' => Peca::MODELO['dispensa_inexigibilidade']['aprovacao_plano']]);
    }

    public function down(): void
    {
        // Sem reversão: o conteúdo semeado e os rótulos são dados de trabalho.
    }
};
