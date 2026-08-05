<?php

use App\Models\Peca;
use Illuminate\Database\Migrations\Migration;

/**
 * O texto-modelo do Relatório da Comissão de Seleção havia sido condensado
 * demais e perdeu as referências ao **controle interno e ao TCE/MG** presentes
 * no documento original (alíneas "c" e "e"), além do parágrafo de fecho sobre
 * rastreabilidade e risco de glosa.
 *
 * Re-semeia o texto nas peças que ainda não foram assinadas — peça assinada é
 * documento definitivo e não pode ser alterada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Peca::where('categoria', 'chamamento_publico')
            ->where('chave', 'relatorio_comissao')
            ->whereNull('assinado_em')
            ->update(['conteudo' => Peca::MODELO['chamamento_publico']['relatorio_comissao']]);
    }

    public function down(): void
    {
        // Sem reversão: apenas restaura trechos do modelo oficial.
    }
};
