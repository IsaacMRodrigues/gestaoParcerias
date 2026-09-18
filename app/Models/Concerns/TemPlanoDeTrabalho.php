<?php

namespace App\Models\Concerns;

use App\Models\Despesa;
use App\Models\Meta;
use App\Models\PlanoDesembolso;
use App\Models\PlanoEndereco;
use App\Models\PlanoItem;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * O Plano de Trabalho, que vive em dois lugares.
 *
 * A OSC monta o mesmo plano na manifestação de interesse e na proposta de um
 * chamamento público. Como no deferimento a manifestação *vira* proposta —
 * levando as mesmas linhas, sem cópia —, cada tabela filha tem as duas chaves e
 * quem as usa diz qual é a sua.
 */
trait TemPlanoDeTrabalho
{
    /** 'proposta_id' ou 'manifestacao_id' — a coluna que liga as listas a este dono. */
    abstract public function chavePlano(): string;

    public function planoItens(): HasMany
    {
        return $this->hasMany(PlanoItem::class, $this->chavePlano())->orderBy('numero');
    }

    public function desembolsos(): HasMany
    {
        return $this->hasMany(PlanoDesembolso::class, $this->chavePlano())
            ->orderBy('ano')->orderBy('mes');
    }

    public function enderecosExecucao(): HasMany
    {
        return $this->hasMany(PlanoEndereco::class, $this->chavePlano())->orderBy('id');
    }

    // ------------------------------------------------------------- somatórios

    public function totalPlanoAplicacao(): float
    {
        return (float) $this->planoItens->sum(fn (PlanoItem $i) => $i->total());
    }

    public function totalDesembolso(): float
    {
        return (float) $this->desembolsos->sum('valor');
    }

    public function totalDasMetas(): float
    {
        return (float) $this->metas->sum('valor');
    }

    /** O que cada natureza de despesa recebeu no plano — o "aprovado" da prestação. */
    public function planoPorNatureza(): array
    {
        $por = [];
        foreach ($this->planoItens as $item) {
            $por[$item->tipo_despesa] = ($por[$item->tipo_despesa] ?? 0) + $item->total();
        }

        return $por;
    }

    /** II — Valor total da proposta: município, contrapartida da OSC e outras fontes. */
    public function quadroDeFontes(): array
    {
        $fontes = [
            'PMSGRA'            => (float) $this->valor_solicitado,
            'OSC — Contrapartida' => (float) $this->valor_proprio,
            'Outras fontes'     => (float) $this->valor_outras_fontes,
        ];
        $total = array_sum($fontes);

        $linhas = [];
        foreach ($fontes as $nome => $valor) {
            $linhas[] = [
                'nome'       => $nome,
                'valor'      => $valor,
                'percentual' => $total > 0 ? $valor / $total * 100 : 0.0,
            ];
        }

        return ['linhas' => $linhas, 'total' => $total];
    }

    public function valorTotalDoPlano(): float
    {
        return (float) $this->valor_solicitado
            + (float) $this->valor_proprio
            + (float) $this->valor_outras_fontes;
    }

    /**
     * Incoerências que a própria OSC deve resolver antes de apresentar o plano.
     *
     * Não bloqueiam: avisam. Quem confere de verdade é a análise técnica, e um
     * plano legítimo pode ter arredondamento de centavos — daí a tolerância.
     * Bloquear aqui devolveria à OSC um erro que ela não sabe corrigir sozinha.
     */
    public function divergenciasDoPlano(): array
    {
        $avisos = [];
        $tol    = 0.01;

        $aplicacao = $this->totalPlanoAplicacao();
        $total     = $this->valorTotalDoPlano();

        if ($this->planoItens->isNotEmpty() && abs($aplicacao - $total) > $tol) {
            $avisos[] = sprintf(
                'O plano de aplicação soma R$ %s, e o valor total da proposta é R$ %s.',
                number_format($aplicacao, 2, ',', '.'), number_format($total, 2, ',', '.')
            );
        }

        $desembolso = $this->totalDesembolso();
        if ($this->desembolsos->isNotEmpty() && abs($desembolso - (float) $this->valor_solicitado) > $tol) {
            $avisos[] = sprintf(
                'O cronograma de desembolso soma R$ %s, e o valor solicitado ao município é R$ %s.',
                number_format($desembolso, 2, ',', '.'),
                number_format((float) $this->valor_solicitado, 2, ',', '.')
            );
        }

        $metas = $this->totalDasMetas();
        if ($metas > 0 && abs($metas - $total) > $tol) {
            $avisos[] = sprintf(
                'A soma dos valores das metas é R$ %s, e o valor total da proposta é R$ %s.',
                number_format($metas, 2, ',', '.'), number_format($total, 2, ',', '.')
            );
        }

        return $avisos;
    }

    /** Naturezas de despesa aceitas no plano de aplicação. */
    public static function tiposDeDespesa(): array
    {
        return Despesa::NATUREZAS;
    }

    /** Próximo número de uma lista numerada (metas e itens do plano). */
    public function proximoNumero(string $relacao): int
    {
        return (int) $this->{$relacao}()->max('numero') + 1;
    }

    /**
     * O plano está completo o bastante para ser apresentado?
     *
     * Metas dizem o que será feito; o plano de aplicação, com quanto; o
     * desembolso, quando o dinheiro precisa entrar. Sem os três não há o que
     * analisar — e é sobre eles que os pareceres financeiro e jurídico se
     * pronunciam.
     */
    public function pendenciasDoPlano(): array
    {
        $faltam = [];

        if ($this->metas()->count() === 0) {
            $faltam[] = 'plano de trabalho (ao menos uma meta)';
        }

        if ($this->planoItens()->count() === 0) {
            $faltam[] = 'plano de aplicação dos recursos (ao menos um item)';
        }

        if ($this->desembolsos()->count() === 0) {
            $faltam[] = 'cronograma de desembolso (ao menos uma parcela)';
        }

        return $faltam;
    }

    /** Leva o plano inteiro para outro dono — é o que o deferimento faz. */
    public function transferirPlanoPara(string $coluna, int $id): void
    {
        foreach (['metas', 'planoItens', 'desembolsos', 'enderecosExecucao'] as $relacao) {
            $this->{$relacao}()->update([$coluna => $id]);
        }
    }

    /** Meta nova, numerada na sequência. */
    public function criarMeta(array $dados): Meta
    {
        return $this->metas()->create($dados + ['numero' => $this->proximoNumero('metas')]);
    }
}
