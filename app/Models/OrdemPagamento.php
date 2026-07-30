<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class OrdemPagamento extends Model
{
    protected $table = 'ordens_pagamento';

    protected $fillable = [
        'instrumento_id', 'numero', 'tipo', 'favorecido', 'valor', 'data_emissao',
        'conteudo', 'assinado_por', 'assinado_em', 'codigo_validacao',
        'dados_bancarios_path', 'dados_bancarios_nome',
    ];

    /**
     * GLOBAL  — pede o empenho global do exercício (uma vez, no início);
     * PARCIAL — pede o subempenho de cada parcela (várias na vigência).
     */
    public const TIPOS = [
        'global'  => 'Global (empenho do exercício)',
        'parcial' => 'Parcial (subempenho da parcela)',
    ];

    protected function casts(): array
    {
        return [
            'data_emissao' => 'date',
            'assinado_em'  => 'datetime',
            'valor'        => 'decimal:2',
        ];
    }

    /** Cabeçalho com brasão (mesmo padrão das demais peças). */
    private const CABECALHO = <<<'HTML'
<table style="border:none;border-collapse:collapse;width:100%"><tbody><tr>
<td style="border:none;width:110px;vertical-align:middle"><img src="https://pmsgra.net/logo.png" width="90"></td>
<td style="border:none;text-align:center;vertical-align:middle"><strong>PREFEITURA MUNICIPAL DE SÃO GONÇALO DO RIO ABAIXO</strong><br>AV. CONTORNO OESTE, 1.657, CIDADE UNIVERSITÁRIA<br>CEP 35935-000 – ESTADO DE MINAS GERAIS</td>
</tr></tbody></table>
<p><br></p>
HTML;

    /** Texto-modelo (modelo padrão) — editável no editor rico; substituir os "XXXX". */
    public const MODELO = self::CABECALHO . <<<'HTML'
<p style="text-align:center"><strong>ORDEM DE PAGAMENTO Nº {{op_numero}}/{{ano}}</strong></p>
<p><strong>Instrumento (parceria):</strong> {{instrumento}}</p>
<p><strong>Favorecido (OSC):</strong> {{favorecido}}</p>
<p><strong>Valor:</strong> R$ XXXXX (XXXXX)</p>
<p><strong>Data:</strong> {{data}}</p>
<p><br></p>
<p>Autoriza-se o pagamento acima discriminado, referente à execução da parceria celebrada nos termos da
Lei Federal nº 13.019/2014 e do Decreto Municipal nº 048/2020, mediante crédito em conta corrente
específica de titularidade da Organização da Sociedade Civil, conforme dados bancários anexados.</p>
<p><br></p>
<p style="text-align:center">{{responsavel_nome}}<br>Ordenador de Despesa — Unidade Gestora</p>
HTML;

    /** Ordem de Pagamento GLOBAL — solicita o empenho global do exercício. */
    public const MODELO_GLOBAL = self::CABECALHO . <<<'HTML'
<p><strong>Ofício n.:</strong> {{op_numero}}/{{ano}}/SEPLAN/SCP</p>
<p><strong>A/C do Setor de Contabilidade</strong></p>
<p>Venho, por meio deste, solicitar a emissão do <strong>empenho global</strong> para pagamentos referentes ao exercício de {{ano}}, no valor total de R$ XXXXX (XXXXXX), referente à parceria com a {{favorecido}}, Termo de XXXX nº. {{instrumento}}, para a seguinte dotação orçamentária:</p>
<p><strong>Dotação - Ano {{ano}}:</strong> XXXXX &nbsp; <strong>Ficha</strong> XXXX &nbsp; <strong>Fonte</strong> XXXXX</p>
<p style="text-align:right">São Gonçalo do Rio Abaixo, {{data}}.</p>
<p style="text-align:center">{{responsavel_nome}}<br>Gestor de Parceria - Port. XXXXX</p>
<p style="text-align:center">XXXXX<br>Secretaria Municipal de XXXXXX</p>
HTML;

    /** Ordem de Pagamento PARCIAL — solicita o subempenho de cada parcela. */
    public const MODELO_PARCIAL = self::CABECALHO . <<<'HTML'
<p><strong>Ofício n.:</strong> {{op_numero}}/{{ano}}/SEPLAN/SCP</p>
<p><strong>A/C do Setor de Contabilidade</strong></p>
<p>Venho por meio deste solicitar a emissão do <strong>subempenho</strong>, para pagamento referente a XXª parcela, no valor de R$ XXXXX (XXXXXXX) a ser pago até o dia XX/XX/XXXX, à {{favorecido}}, agência XXXX, operação XXX, Banco XXXXX na conta:</p>
<p>- XXXXX: R$ XXXXX (XXXXXX);<br>- XXXXX: R$ XXXX (XXXXXXXX).</p>
<p><strong>Planilha de Monitoramento / Empenho Global n°.</strong> XXXXXXXX</p>
<p><strong>Dotação Ano {{ano}}:</strong> XXXXXX &nbsp; <strong>Ficha</strong> XXX &nbsp; <strong>Fonte</strong> XXXXX</p>
<table><thead><tr><th>Valor total previsto</th><th>Valor repassado no período</th><th>Valor total repassado</th></tr></thead><tbody><tr><td>R$ XXXXX</td><td>R$ XXXX</td><td>R$ XXXXX</td></tr></tbody></table>
<p><strong>Obs.:</strong> XXXXXXXX</p>
<p>Certifico que a OSC mencionada encontra-se em situação XXXXXX no que diz respeito à prestação de contas anterior, considerando que a próxima prestação está prevista para XX/XX/XXXX, conforme estipulado na cláusula nona do Termo de XXXX Nº. {{instrumento}}.</p>
<p style="text-align:right">São Gonçalo do Rio Abaixo, {{data}}.</p>
<p style="text-align:center">{{responsavel_nome}}<br>Gestor de Parceria - Port. XXXX/XXX</p>
<p style="text-align:center">XXXXX<br>Secretaria Municipal de XXXXX</p>
HTML;

    /**
     * Conteúdo inicial já "puxando" os dados do instrumento/OSC para o modelo
     * correspondente ao tipo (global ou parcial).
     */
    public static function conteudoInicial(
        Instrumento $instrumento,
        int $numero,
        ?string $responsavel = null,
        string $tipo = 'parcial',
    ): string {
        $modelo = $tipo === 'global' ? self::MODELO_GLOBAL : self::MODELO_PARCIAL;

        return \App\Support\Modelo::preencher($modelo, [
            'op_numero'        => $numero,
            'instrumento'      => $instrumento->numero,
            'favorecido'       => $instrumento->proposta?->osc?->name,
            'responsavel_nome' => $responsavel,
            'data'             => now()->format('d/m/Y'),
            'ano'              => now()->year,
        ]);
    }

    public function tipoLabel(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    public function ehGlobal(): bool
    {
        return $this->tipo === 'global';
    }

    public function instrumento(): BelongsTo
    {
        return $this->belongsTo(Instrumento::class);
    }

    public function assinante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assinado_por');
    }

    public function assinado(): bool
    {
        return !is_null($this->assinado_em);
    }

    public function temDadosBancarios(): bool
    {
        return !is_null($this->dados_bancarios_path);
    }

    /** Gera um código de validação único (ex.: A1B2-C3D4-E5). */
    public static function gerarCodigoValidacao(): string
    {
        do {
            $codigo = strtoupper(Str::random(4) . '-' . Str::random(4) . '-' . Str::random(2));
        } while (static::where('codigo_validacao', $codigo)->exists());

        return $codigo;
    }
}
