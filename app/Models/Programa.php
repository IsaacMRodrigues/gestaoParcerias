<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Agrupamento dos chamamentos por Secretaria e ano (ver doOrgao); não tem tela própria. */
class Programa extends Model
{
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
}
