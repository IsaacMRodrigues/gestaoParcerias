<?php

namespace App\Models;

use App\Models\Concerns\ImpedeExclusaoComVinculos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Programa extends Model
{
    use ImpedeExclusaoComVinculos;

    public const TIPOS = [
        'termo_fomento'      => 'Termo de Fomento',
        'termo_colaboracao'  => 'Termo de Colaboração',
        'acordo_cooperacao'  => 'Acordo de Cooperação',
    ];

    public const STATUS = [
        'ativo'     => 'Ativo',
        'encerrado' => 'Encerrado',
        'suspenso'  => 'Suspenso',
    ];

    protected $fillable = [
        'orgao_id', 'name', 'sigla', 'tipo', 'objetivo',
        'valor_total', 'data_inicio', 'data_fim', 'status',
    ];

    protected function casts(): array
    {
        return [
            'data_inicio'  => 'date',
            'data_fim'     => 'date',
            'valor_total'  => 'decimal:2',
        ];
    }

    /** O programa da Secretaria no ano, criado na primeira vez: agrupa os chamamentos dela. */
    public static function doOrgao(Orgao $orgao, ?int $ano = null): self
    {
        $ano ??= now()->year;

        return self::firstOrCreate(
            ['orgao_id' => $orgao->id, 'sigla' => 'PGP-' . ($orgao->codigo ?: $orgao->id) . '-' . $ano],
            ['name' => 'Parcerias ' . ($orgao->sigla ?: $orgao->name) . ' ' . $ano, 'tipo' => 'termo_colaboracao', 'status' => 'ativo'],
        );
    }

    public function orgao(): BelongsTo
    {
        return $this->belongsTo(Orgao::class);
    }

    public function chamamentos(): HasMany
    {
        return $this->hasMany(Chamamento::class);
    }

    protected function vinculosBloqueantes(): array
    {
        return [
            'chamamentos' => ['chamamento', 'chamamentos'],
        ];
    }

    protected function fraseDeBloqueio(): string
    {
        return 'Este programa não pode ser excluído';
    }
}
