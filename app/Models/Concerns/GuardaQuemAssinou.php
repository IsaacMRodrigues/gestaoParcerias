<?php

namespace App\Models\Concerns;

/**
 * Quem assinou, como estava no dia: nome e qualificação gravados no ato. O cadastro atual
 * só para linha antiga sem registro.
 */
trait GuardaQuemAssinou
{
    public function assinanteNome(): ?string
    {
        // Assinado em sequência (o Termo): todas as partes, na ordem.
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
