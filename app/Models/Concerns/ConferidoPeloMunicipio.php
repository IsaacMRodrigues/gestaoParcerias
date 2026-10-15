<?php

namespace App\Models\Concerns;

use App\Models\PrestacaoContas;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * O que a OSC lança no painel da prestação de contas e a Prefeitura confere: a conferir, conferido
 * ou devolvido com motivo. A OSC corrige o que está a conferir ou devolvido, e o lançamento volta à
 * conferência. Entrou numa prestação já enviada, a OSC não o altera e a Prefeitura só o confere (devolver,
 * só a prestação inteira).
 *
 * Quem usa declara dataDoLancamento() e tem as colunas situacao, conferido_por, conferido_em,
 * devolucao_motivo e lancado_por.
 */
trait ConferidoPeloMunicipio
{
    public const SITUACOES = [
        'pendente'  => 'A conferir',
        'conferido' => 'Conferido',
        'devolvido' => 'Devolvido',
    ];

    abstract public function dataDoLancamento(): ?\Carbon\CarbonInterface;

    public function conferente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'conferido_por');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lancado_por');
    }

    public function scopeSituacao(Builder $q, string $situacao): Builder
    {
        return $q->where('situacao', $situacao);
    }

    public function conferido(): bool
    {
        return $this->situacao === 'conferido';
    }

    public function devolvido(): bool
    {
        return $this->situacao === 'devolvido';
    }

    public function aConferir(): bool
    {
        return $this->situacao === 'pendente';
    }

    public function rotuloDaSituacao(): string
    {
        return self::SITUACOES[$this->situacao] ?? $this->situacao;
    }

    private ?\Illuminate\Support\Collection $periodosTravados = null;

    /** Períodos das prestações que já saíram da OSC (ou encerraram): [início, fim] em Y-m-d. */
    public static function periodosTravadosDe(?int $instrumentoId): \Illuminate\Support\Collection
    {
        return PrestacaoContas::where('instrumento_id', $instrumentoId)
            ->where(fn ($q) => $q->where('etapa', '>', 0)->orWhereNotNull('concluida_em'))
            ->get(['periodo_inicio', 'periodo_fim'])
            ->map(fn ($pc) => [$pc->periodo_inicio->toDateString(), $pc->periodo_fim->toDateString()]);
    }

    /** Para listas: os períodos lidos uma vez, em vez de uma consulta por lançamento. */
    public function usarPeriodosTravados(\Illuminate\Support\Collection $periodos): static
    {
        $this->periodosTravados = $periodos;

        return $this;
    }

    /** Já está numa prestação de contas que saiu da OSC (ou encerrou): fica como foi enviado. */
    public function travadoPorPrestacao(): bool
    {
        $data = $this->dataDoLancamento()?->toDateString();
        if ($data === null) {
            return false;
        }

        $this->periodosTravados ??= self::periodosTravadosDe($this->instrumento_id);

        return $this->periodosTravados->contains(fn ($p) => $p[0] <= $data && $data <= $p[1]);
    }

    /** A OSC só mexe no que ainda não foi conferido e não foi enviado numa prestação. */
    public function podeSerAlteradoPelaOsc(): bool
    {
        return !$this->conferido() && !$this->travadoPorPrestacao();
    }

    public function conferir(User $user): void
    {
        $this->forceFill(['situacao' => 'conferido', 'conferido_por' => $user->id, 'conferido_em' => now(),
            'devolucao_motivo' => null])->save();
    }

    public function devolver(User $user, string $motivo): void
    {
        $this->forceFill(['situacao' => 'devolvido', 'conferido_por' => $user->id, 'conferido_em' => now(),
            'devolucao_motivo' => $motivo])->save();
    }

    /** A OSC corrigiu: volta à conferência, guardado o motivo da devolução até a nova análise. */
    public function voltarAConferencia(): void
    {
        $this->forceFill(['situacao' => 'pendente', 'conferido_por' => null, 'conferido_em' => null])->save();
    }
}
