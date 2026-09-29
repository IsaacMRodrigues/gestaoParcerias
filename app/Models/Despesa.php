<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Despesa extends Model
{
    /**
     * Naturezas de despesa (controle por natureza — 4.4): as 12 do item 9 do
     * modelo de Plano de Trabalho da cliente, na ordem e com o texto dele. É a
     * mesma lista no plano de aplicação, na execução e na prestação de contas,
     * para que o aprovado e o gasto sejam comparáveis.
     */
    public const NATUREZAS = [
        'auxilio_alimentacao'           => 'Auxílio-Alimentação',
        'auxilio_transporte'            => 'Auxílio-Transporte',
        'contratacao_tempo_determinado' => 'Contratação por Tempo Determinado (pessoal)',
        'diarias'                       => 'Diárias – Civil (alimentação, pousada e locomoção urbana)',
        'encargos_patronais'            => 'Encargos patronais e trabalhistas',
        'equipamento'                   => 'Equipamentos e Material Permanente',
        'material_consumo'              => 'Material de Consumo',
        'obras'                         => 'Obras e Instalações',
        'obrigacoes_tributarias'        => 'Obrigações Tributárias e Contributivas',
        'servicos_pf'                   => 'Outros Serviços de Terceiros – Pessoa Física',
        'servicos_pj'                   => 'Outros Serviços de Terceiros – Pessoa Jurídica',
        'passagens'                     => 'Passagens e Despesas com Locomoção',
    ];

    /**
     * Da lista anterior, só "Outros" ficou sem equivalente no modelo: não se
     * escolhe mais, mas o registro antigo segue com o seu rótulo.
     */
    public const NATUREZAS_ANTIGAS = [
        'outros' => 'Outros (lista antiga)',
    ];

    public static function rotuloNatureza(?string $natureza): string
    {
        return self::NATUREZAS[$natureza] ?? self::NATUREZAS_ANTIGAS[$natureza] ?? (string) $natureza;
    }

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
        return self::rotuloNatureza($this->natureza);
    }

    public function temNotaFiscal(): bool
    {
        return !is_null($this->nota_fiscal_path);
    }
}
