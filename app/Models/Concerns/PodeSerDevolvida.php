<?php

namespace App\Models\Concerns;

/**
 * Documento que pode ser devolvido para correção: reabre (o texto perde a assinatura, o arquivo
 * pede novo envio) e guarda o motivo até alguém corrigi-lo.
 */
trait PodeSerDevolvida
{
    public function devolvida(): bool
    {
        return $this->devolvida_em !== null;
    }

    public function reabrirPorDevolucao(string $motivo, ?string $setor, ?string $nome): void
    {
        $campos = [
            'assinado_por' => null, 'assinado_em' => null, 'assinante_nome' => null, 'assinante_cargo' => null,
            // Assinado de novo, o documento ganha outro código: o antigo
            // validava um texto que estava errado.
            'codigo_validacao' => null,
            'devolvida_em' => now(), 'devolucao_motivo' => $motivo,
            'devolvida_por_nome' => $nome, 'devolvida_pelo_setor' => $setor,
        ];

        if (array_key_exists('contra_assinado_em', $this->getAttributes())) {
            $campos += [
                'contra_assinado_por' => null, 'contra_assinado_em' => null, 'contra_assinante_nome' => null,
                'contra_assinante_cargo' => null, 'codigo_validacao_contra' => null,
            ];
        }

        $this->forceFill($campos)->save();

        // Assinado em sequência: reabrir recomeça a sequência.
        if (method_exists($this, 'assinaturasPartes')) {
            $this->assinaturasPartes()->delete();
            $this->unsetRelation('assinaturasPartes');
        }
    }

    /** Corrigido: sai a marca de devolvido. */
    public function limparDevolucao(): void
    {
        if ($this->devolvida()) {
            $this->forceFill(['devolvida_em' => null, 'devolucao_motivo' => null,
                'devolvida_por_nome' => null, 'devolvida_pelo_setor' => null])->save();
        }
    }
}
