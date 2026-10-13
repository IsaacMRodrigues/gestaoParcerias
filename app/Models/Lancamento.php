<?php

namespace App\Models;

use App\Models\Concerns\ConferidoPeloMunicipio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Um lançamento do painel da prestação de contas (fora as despesas, que têm tabela própria):
 * extrato do mês, evidência da execução de uma meta, folha do mês ou bem adquirido.
 */
class Lancamento extends Model
{
    use ConferidoPeloMunicipio;

    protected $table = 'lancamentos';

    /** Tipo => [rótulo, peça da prestação que o arquivo compõe]. */
    public const TIPOS = [
        'extrato_conta'     => ['rotulo' => 'Extrato da conta corrente',            'peca' => 'extrato_conta'],
        'extrato_aplicacao' => ['rotulo' => 'Extrato da conta de aplicação',        'peca' => 'extrato_aplicacao'],
        'evidencia'         => ['rotulo' => 'Evidência da execução do objeto',      'peca' => 'docs_execucao'],
        'folha'             => ['rotulo' => 'Folha de pagamento',                   'peca' => 'outros_documentos'],
        'bem'               => ['rotulo' => 'Bem permanente adquirido',             'peca' => 'outros_documentos'],
    ];

    /** Campos da folha do mês, somados no resumo da folha da prestação (Anexo 16). */
    public const CAMPOS_DA_FOLHA = [
        'folha_funcionarios' => 'Nº de funcionários',
        'folha_salarios'     => 'Salários',
        'folha_vantagens'    => 'Vantagens',
        'folha_adicionais'   => 'Adicionais',
        'folha_inss'         => 'INSS',
        'folha_irrf'         => 'IRRF',
        'folha_plano_saude'  => 'Plano de saúde',
        'folha_fgts'         => 'FGTS',
        'folha_ferias'       => 'Férias',
        'folha_rescisao'     => 'Rescisão',
    ];

    protected $fillable = [
        'instrumento_id', 'tipo', 'competencia', 'descricao', 'meta_id', 'quantidade', 'valor', 'dados',
        'arquivo_path', 'arquivo_nome', 'mime_type', 'tamanho', 'lancado_por',
    ];

    protected function casts(): array
    {
        return [
            'competencia'  => 'date',
            'conferido_em' => 'datetime',
            'quantidade'   => 'decimal:2',
            'valor'        => 'decimal:2',
            'dados'        => 'array',
        ];
    }

    public function instrumento(): BelongsTo
    {
        return $this->belongsTo(Instrumento::class);
    }

    public function meta(): BelongsTo
    {
        return $this->belongsTo(Meta::class);
    }

    public function dataDoLancamento(): ?\Carbon\CarbonInterface
    {
        return $this->competencia;
    }

    public function rotuloDoTipo(): string
    {
        return self::TIPOS[$this->tipo]['rotulo'] ?? $this->tipo;
    }

    /** Extrato e folha são do mês; evidência e bem, do dia. */
    public function porMes(): bool
    {
        return in_array($this->tipo, ['extrato_conta', 'extrato_aplicacao', 'folha'], true);
    }

    public function referencia(): string
    {
        return $this->porMes()
            ? ucfirst($this->competencia->locale('pt_BR')->translatedFormat('F/Y'))
            : $this->competencia->format('d/m/Y');
    }

    public function temArquivo(): bool
    {
        return $this->arquivo_path !== null;
    }

    public function totalDoBem(): float
    {
        return (float) $this->quantidade * (float) $this->valor;
    }
}
