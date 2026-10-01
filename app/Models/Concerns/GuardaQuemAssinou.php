<?php

namespace App\Models\Concerns;

/**
 * Quem assinou, como estava no dia em que assinou.
 *
 * O carimbo lia o cadastro do usuário no momento da exibição: bastava a pessoa
 * editar o nome no perfil, mudar de setor ou ganhar outro papel para que todos
 * os documentos que ela já tinha assinado passassem a dizer outra coisa.
 * Assinatura é ato com data certa — nome e qualificação ficam gravados na
 * própria linha, no ato.
 *
 * A leitura do usuário vivo sobrevive apenas como recurso para alguma linha
 * antiga sem o registro; toda assinatura nova nasce com os dois campos.
 */
trait GuardaQuemAssinou
{
    public function assinanteNome(): ?string
    {
        // Assinado em sequência (o Termo, 01/10/2026): todas as partes, na ordem.
        if ($this->assinado_em === null && method_exists($this, 'temAssinaturasEmSequencia') && $this->temAssinaturasEmSequencia()) {
            return $this->assinaturasPartes->pluck('assinante_nome')->filter()->implode(', ') ?: null;
        }

        return $this->assinante_nome ?: $this->assinante?->name;
    }

    /** Quando o documento ficou assinado — no assinado em sequência, a última parte. */
    public function dataDaAssinatura(): ?\Illuminate\Support\Carbon
    {
        if ($this->assinado_em === null && method_exists($this, 'temAssinaturasEmSequencia') && $this->temAssinaturasEmSequencia()) {
            return $this->assinaturasPartes->max('assinado_em');
        }

        return $this->assinado_em;
    }

    public function assinanteCargo(): ?string
    {
        return $this->assinante_cargo ?: $this->assinante?->cargoParaAssinatura();
    }

    public function contraAssinanteNome(): ?string
    {
        return $this->contra_assinante_nome ?: $this->contraAssinante?->name;
    }

    public function contraAssinanteCargo(): ?string
    {
        return $this->contra_assinante_cargo ?: $this->contraAssinante?->cargoParaAssinatura();
    }
}
