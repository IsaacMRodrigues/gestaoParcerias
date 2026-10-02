<?php

namespace App\Models\Concerns;

/**
 * Explica, antes do delete, por que um registro não pode ser excluído ("3 propostas"),
 * em vez de deixar o RESTRICT do banco virar erro 500.
 */
trait ImpedeExclusaoComVinculos
{
    /**
     * Relações que impedem a exclusão: ['relacao' => ['singular', 'plural']]. Só as que o banco
     * barra (RESTRICT / NO ACTION).
     */
    protected function vinculosBloqueantes(): array
    {
        return [];
    }

    /** Impedimentos já contados (['3 propostas', '1 instrumento']); vazio = pode excluir. */
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

    /** Abertura da mensagem, inteira no model por causa da concordância ("Esta OSC… excluída"). */
    protected function fraseDeBloqueio(): string
    {
        return 'Este registro não pode ser excluído';
    }

    /** O que fazer em vez de excluir (para usuário, desativar). */
    protected function sugestaoParaNaoExcluir(): string
    {
        return 'Remova ou transfira esses registros antes.';
    }
}
