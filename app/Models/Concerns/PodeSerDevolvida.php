<?php

namespace App\Models\Concerns;

/**
 * Documento que pode ser devolvido para correção (decisão da gestão,
 * 30/09/2026).
 *
 * Devolvido, ele reabre: o texto perde a assinatura (e a contra-assinatura,
 * se houver), e o arquivo, que fica guardado, passa a pedir um novo envio. O
 * motivo fica no documento até alguém corrigi-lo — salvar o texto, enviar o
 * arquivo novo ou assinar de novo limpa a marca.
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
