<?php

namespace App\Support;

use App\Models\Peca;
use App\Models\ProcessoPeca;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * Devolução por documento, em todos os trâmites: só os documentos marcados reabrem, com o motivo,
 * e o trâmite volta à etapa do mais antigo. Sem marcar, volta à etapa anterior ou à escolhida.
 * Serve a Peca e a ProcessoPeca.
 */
class Devolucao
{
    /** Documentos que podem ser devolvidos: de etapas já vencidas e prontos. */
    public static function candidatas(iterable $pecas, int $etapaAtual): Collection
    {
        return collect($pecas)
            ->filter(function ($p) use ($etapaAtual) {
                $etapa = self::etapaDoDocumento($p);

                return $etapa !== null && $etapa < $etapaAtual && self::pronto($p) && !$p->devolvida();
            })
            ->sortBy(fn ($p) => [self::etapaDoDocumento($p), $p->ordem ?? 0, $p->id])
            ->values();
    }

    /** A etapa em que o documento é feito — é para lá que ele volta. */
    public static function etapaDoDocumento(Peca|ProcessoPeca $p): ?int
    {
        if ($p instanceof ProcessoPeca) {
            return ProcessoPeca::ETAPA[$p->tipo] ?? null;
        }

        return $p->emTramite() && !$p->vemDoPlanejamento() ? $p->selecaoEtapa() : null;
    }

    public static function rotulo(Peca|ProcessoPeca $p): string
    {
        return $p instanceof ProcessoPeca ? (ProcessoPeca::TIPOS[$p->tipo] ?? $p->tipo) : $p->rotulo;
    }

    private static function pronto(Peca|ProcessoPeca $p): bool
    {
        if ($p->assinado() || ($p instanceof Peca && $p->semAssinatura() && $p->redigida())
            || ($p instanceof Peca && $p->temAssinaturasEmSequencia() && $p->assinaturasPartes->isNotEmpty())) {
            return true;
        }

        return $p instanceof ProcessoPeca ? $p->temAnexo() : ($p->tipo === 'arquivo' && $p->temArquivo());
    }

    /** Valida os documentos marcados no formulário e devolve os escolhidos. */
    public static function escolhidas(Request $request, Collection $candidatas): Collection
    {
        $ids = $request->validate([
            'documentos'   => ['nullable', 'array'],
            'documentos.*' => ['integer', Rule::in($candidatas->pluck('id')->all())],
        ], [
            'documentos.*.in' => 'Só se devolve documento já feito numa etapa anterior.',
        ])['documentos'] ?? [];

        return $candidatas->whereIn('id', $ids)->values();
    }

    /** A etapa para onde o trâmite volta: a do documento mais antigo entre os marcados. */
    public static function etapaDestino(Collection $escolhidas, int $padrao): int
    {
        return $escolhidas->isEmpty()
            ? $padrao
            : (int) $escolhidas->map(fn ($p) => self::etapaDoDocumento($p))->min();
    }

    /** Reabre os documentos marcados, com o motivo, e devolve o texto para o histórico. */
    public static function reabrir(Collection $escolhidas, string $motivo, ?User $por): string
    {
        foreach ($escolhidas as $peca) {
            $peca->reabrirPorDevolucao($motivo, $por?->setorNoTramite(), $por?->name);
        }

        return $escolhidas->isEmpty()
            ? $motivo
            : 'Documentos a corrigir: ' . $escolhidas->map(fn ($p) => self::rotulo($p))->implode('; ') . '. ' . $motivo;
    }
}
