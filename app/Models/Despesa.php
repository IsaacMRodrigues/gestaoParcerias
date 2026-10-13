<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Despesa da parceria: a OSC lança no painel da prestação de contas, com a nota, e a Prefeitura confere. */
class Despesa extends Model
{
    use \App\Models\Concerns\ConferidoPeloMunicipio;

    /**
     * As 12 naturezas de despesa do modelo de Plano de Trabalho, as mesmas no plano, na execução e
     * na prestação de contas.
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

    /** Da lista anterior: não se escolhe mais, mas o registro antigo mantém o rótulo. */
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
        'nota_fiscal_numero', 'nota_fiscal_path', 'nota_fiscal_nome', 'lancado_por',
    ];

    protected function casts(): array
    {
        return [
            'data_despesa' => 'date',
            'valor'        => 'decimal:2',
            'conferido_em' => 'datetime',
        ];
    }

    public function instrumento(): BelongsTo
    {
        return $this->belongsTo(Instrumento::class);
    }

    public function dataDoLancamento(): ?\Carbon\CarbonInterface
    {
        return $this->data_despesa;
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
