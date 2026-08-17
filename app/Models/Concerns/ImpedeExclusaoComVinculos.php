<?php

namespace App\Models\Concerns;

/**
 * Explica, em português, por que um registro não pode ser excluído.
 *
 * O banco já protege esses vínculos com ON DELETE RESTRICT — e é bom que
 * proteja: apagar um chamamento levaria junto as propostas que as OSCs
 * enviaram. O problema é que o controller chamava delete() sem perguntar nada,
 * e o usuário recebia uma tela de erro 500 com SQL na cara, sem entender o que
 * havia feito de errado nem o que precisa fazer antes.
 *
 * Cada model declara aqui o que o segura. A checagem acontece ANTES do delete,
 * então a mensagem diz o motivo e a quantidade ("3 propostas"), não só que
 * falhou.
 */
trait ImpedeExclusaoComVinculos
{
    /**
     * Relações que impedem a exclusão, no formato
     * ['nomeDaRelacao' => ['singular', 'plural']].
     *
     * Só entram aqui as que o banco realmente barra (RESTRICT / NO ACTION);
     * o que tem CASCADE some junto com o pai e não é impedimento.
     */
    protected function vinculosBloqueantes(): array
    {
        return [];
    }

    /**
     * Lista dos impedimentos já contados: ['3 propostas', '1 instrumento'].
     * Vazia significa que dá para excluir.
     */
    public function motivosParaNaoExcluir(): array
    {
        $motivos = [];

        foreach ($this->vinculosBloqueantes() as $relacao => [$singular, $plural]) {
            $quantos = $this->{$relacao}()->count();

            if ($quantos > 0) {
                $motivos[] = $quantos.' '.($quantos === 1 ? $singular : $plural);
            }
        }

        return $motivos;
    }

    /** Frase pronta para o usuário, ou null quando a exclusão está liberada. */
    public function motivoParaNaoExcluir(): ?string
    {
        $motivos = $this->motivosParaNaoExcluir();

        if (!$motivos) {
            return null;
        }

        // "3 propostas e 1 instrumento" — vírgulas até o penúltimo, "e" no fim.
        $ultimo = array_pop($motivos);
        $lista  = $motivos ? implode(', ', $motivos).' e '.$ultimo : $ultimo;

        return trim($this->fraseDeBloqueio().": há {$lista}. ".$this->sugestaoParaNaoExcluir());
    }

    /**
     * Abertura da mensagem, por extenso.
     *
     * Vem inteira do model, e não montada a partir de um rótulo, porque o
     * português exige concordância: "Esta OSC não pode ser excluída" e "Este
     * chamamento não pode ser excluído" não saem da mesma fôrma.
     */
    protected function fraseDeBloqueio(): string
    {
        return 'Este registro não pode ser excluído';
    }

    /**
     * O que fazer em vez de excluir. Nem sempre é "apague os filhos primeiro":
     * para usuário, por exemplo, o certo é desativar, porque o histórico do
     * processo precisa continuar rastreável até quem assinou.
     */
    protected function sugestaoParaNaoExcluir(): string
    {
        return 'Remova ou transfira esses registros antes.';
    }
}
