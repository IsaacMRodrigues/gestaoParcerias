<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Despesa extends Model
{
    /** Naturezas de despesa (controle por natureza — 4.4). */
    public const NATUREZAS = [
        'material_consumo' => 'Material de Consumo',
        'servicos_pf'      => 'Serviços de Terceiros — Pessoa Física',
        'servicos_pj'      => 'Serviços de Terceiros — Pessoa Jurídica',
        'recursos_humanos' => 'Recursos Humanos / Folha',
        'encargos'         => 'Encargos e Tributos',
        'equipamento'      => 'Equipamento e Material Permanente',
        'outros'           => 'Outros',
    ];

    protected $fillable = [
        'instrumento_id', 'data_despesa', 'valor', 'natureza',
        'fornecedor', 'doc_fornecedor', 'descricao',
        'nota_fiscal_numero', 'nota_fiscal_path', 'nota_fiscal_nome',
    ];

    protected function casts(): array
    {
        return [
            'data_despesa' => 'date',
            'valor'        => 'decimal:2',
        ];
    }

    public function instrumento(): BelongsTo
    {
        return $this->belongsTo(Instrumento::class);
    }

    public function naturezaLabel(): string
    {
        return self::NATUREZAS[$this->natureza] ?? $this->natureza;
    }

    public function temNotaFiscal(): bool
    {
        return !is_null($this->nota_fiscal_path);
    }
}
