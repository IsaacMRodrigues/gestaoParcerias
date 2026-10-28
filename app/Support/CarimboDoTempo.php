<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;

/** Data e hora de um ato, com a fonte que as deu. Sem fonte, é o relógio do próprio servidor. */
final class CarimboDoTempo
{
    public function __construct(
        public readonly CarbonImmutable $instante,
        public readonly ?string $fonte = null,
        public readonly ?int $desvioMs = null,
    ) {
    }

    public function oficial(): bool
    {
        return $this->fonte !== null;
    }

    /** Para as colunas de data do sistema (fuso da aplicação, sem fração de segundo). */
    public function paraGravar(): Carbon
    {
        return Carbon::instance($this->instante)->setTimezone(config('app.timezone'));
    }

    /** Como fica na trilha: UTC, com microssegundos — o texto exato que entra no hash. */
    public function emUtc(): string
    {
        return $this->instante->utc()->format('Y-m-d H:i:s.u');
    }
}
