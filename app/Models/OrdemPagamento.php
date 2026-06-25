<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class OrdemPagamento extends Model
{
    protected $table = 'ordens_pagamento';

    protected $fillable = [
        'instrumento_id', 'numero', 'favorecido', 'valor', 'data_emissao',
        'conteudo', 'assinado_por', 'assinado_em', 'codigo_validacao',
        'dados_bancarios_path', 'dados_bancarios_nome',
    ];

    protected function casts(): array
    {
        return [
            'data_emissao' => 'date',
            'assinado_em'  => 'datetime',
            'valor'        => 'decimal:2',
        ];
    }

    /** Texto-modelo (modelo padrão) — editável no editor rico; substituir os "XXXX". */
    public const MODELO = <<<'HTML'
<table style="border:none;border-collapse:collapse;width:100%"><tbody><tr>
<td style="border:none;width:110px;vertical-align:middle"><img src="https://pmsgra.net/logo.png" width="90"></td>
<td style="border:none;text-align:center;vertical-align:middle"><strong>PREFEITURA MUNICIPAL DE SÃO GONÇALO DO RIO ABAIXO</strong><br>AV. CONTORNO OESTE, 1.657, CIDADE UNIVERSITÁRIA<br>CEP 35935-000 – ESTADO DE MINAS GERAIS</td>
</tr></tbody></table>
<p><br></p>
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

    /**
     * Conteúdo inicial já "puxando" os dados do instrumento/OSC para o modelo.
     */
    public static function conteudoInicial(Instrumento $instrumento, int $numero, ?string $responsavel = null): string
    {
        return \App\Support\Modelo::preencher(self::MODELO, [
            'op_numero'        => $numero,
            'instrumento'      => $instrumento->numero,
            'favorecido'       => $instrumento->proposta?->osc?->name,
            'responsavel_nome' => $responsavel,
            'data'             => now()->format('d/m/Y'),
            'ano'              => now()->year,
        ]);
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
