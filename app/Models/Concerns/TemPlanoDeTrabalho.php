<?php

namespace App\Models\Concerns;

use App\Models\Despesa;
use App\Models\Meta;
use App\Models\PlanoContrapartida;
use App\Models\PlanoDesembolso;
use App\Models\PlanoEndereco;
use App\Models\PlanoEquipe;
use App\Models\PlanoItem;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * O Plano de Trabalho, na manifestação e na proposta: no deferimento as mesmas linhas passam
 * à proposta, por isso cada tabela filha tem as duas chaves.
 */
trait TemPlanoDeTrabalho
{
    /** 'proposta_id' ou 'manifestacao_id' — a coluna que liga as listas a este dono. */
    abstract public function chavePlano(): string;

    public function planoItens(): HasMany
    {
        return $this->hasMany(PlanoItem::class, $this->chavePlano())->orderBy('numero');
    }

    /** Item 11: por meta e parcela. As parcelas antigas, mensais, vêm na ordem do calendário. */
    public function desembolsos(): HasMany
    {
        return $this->hasMany(PlanoDesembolso::class, $this->chavePlano())
            ->orderBy('parcela')->orderBy('ano')->orderBy('mes');
    }

    /** Item 8: contrapartida não financeira. */
    public function contrapartidas(): HasMany
    {
        return $this->hasMany(PlanoContrapartida::class, $this->chavePlano())->orderBy('numero');
    }

    /** Item 12: equipe a serviço da parceria. */
    public function equipe(): HasMany
    {
        return $this->hasMany(PlanoEquipe::class, $this->chavePlano())->orderBy('id');
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

    /** Total do cronograma de execução físico-financeiro (item 10). */
    public function totalDasMetas(): float
    {
        return (float) $this->metas->sum(fn (Meta $m) => $m->valorEstimado());
    }

    /** Item 9: o valor de cada natureza, das 12 do modelo, somado do plano de aplicação. */
    public function naturezasDaDespesa(): array
    {
        $por = $this->planoPorNatureza();

        return collect(Despesa::NATUREZAS)->mapWithKeys(fn ($rotulo, $chave) => [$chave => (float) ($por[$chave] ?? 0)])->all();
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

    /** O valor do plano é o valor pleiteado (item 2 do modelo). */
    public function valorTotalDoPlano(): float
    {
        return (float) $this->valor_solicitado;
    }

    /** Incoerências que a OSC deve resolver; só avisam (com tolerância de centavos), não bloqueiam. */
    public function divergenciasDoPlano(): array
    {
        $avisos = [];
        $tol    = 0.01;

        $aplicacao = $this->totalPlanoAplicacao();
        $total     = $this->valorTotalDoPlano();

        if ($this->planoItens->isNotEmpty() && abs($aplicacao - $total) > $tol) {
            $avisos[] = sprintf(
                'O plano de aplicação soma R$ %s, e o valor pleiteado é R$ %s.',
                number_format($aplicacao, 2, ',', '.'), number_format($total, 2, ',', '.')
            );
        }

        $desembolso = $this->totalDesembolso();
        if ($this->desembolsos->isNotEmpty() && abs($desembolso - (float) $this->valor_solicitado) > $tol) {
            $avisos[] = sprintf(
                'O cronograma de desembolso soma R$ %s, e o valor pleiteado é R$ %s.',
                number_format($desembolso, 2, ',', '.'),
                number_format((float) $this->valor_solicitado, 2, ',', '.')
            );
        }

        $metas = $this->totalDasMetas();
        if ($metas > 0 && abs($metas - $total) > $tol) {
            $avisos[] = sprintf(
                'O cronograma de execução físico-financeiro soma R$ %s, e o valor pleiteado é R$ %s.',
                number_format($metas, 2, ',', '.'), number_format($total, 2, ',', '.')
            );
        }

        return $avisos;
    }

    /** Próximo número de uma lista numerada (metas e itens do plano). */
    public function proximoNumero(string $relacao): int
    {
        return (int) $this->{$relacao}()->max('numero') + 1;
    }

    /** O plano está completo para ser apresentado? Metas, plano de aplicação e desembolso. */
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
        foreach (['metas', 'planoItens', 'desembolsos', 'enderecosExecucao', 'contrapartidas', 'equipe'] as $relacao) {
            $this->{$relacao}()->update([$coluna => $id]);
        }
    }

    /** Meta nova, numerada na sequência. */
    public function criarMeta(array $dados): Meta
    {
        return $this->metas()->create($dados + ['numero' => $this->proximoNumero('metas')]);
    }
}
