<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessoPeca extends Model
{
    public const TIPOS = [
        'oficio'             => 'Ofício',
        'termo_referencia'   => 'Termo de Referência',
        'pedido_parecer'     => 'Pedido de Parecer Financeiro',
        'parecer_financeiro' => 'Parecer Financeiro',
        'abertura'           => 'Termo de Abertura de Processo',
        'edital'             => 'Edital',
    ];

    /**
     * Setor que PREENCHE cada peça e em qual etapa do fluxo.
     */
    public const SETOR_RESPONSAVEL = [
        'oficio'             => 'ug',
        'termo_referencia'   => 'ug',
        'pedido_parecer'     => 'ug',   // a UG solicita o parecer à SEPLAN
        'parecer_financeiro' => 'seplan',
        'abertura'           => 'ug',
        'edital'             => 'scp',
    ];

    public const ETAPA = [
        'oficio'             => 0,
        'termo_referencia'   => 0,
        'pedido_parecer'     => 2,
        'parecer_financeiro' => 3,
        'abertura'           => 4,
        'edital'             => 5,
    ];

    /**
     * Quem ASSINA (quando difere de quem preenche). Ex.: o Edital é elaborado
     * pela SCP (etapa 5) mas assinado pela UG (etapa 6).
     */
    public const ASSINATURA = [
        'edital' => ['setor' => 'ug', 'etapa' => 6],
    ];

    /**
     * Texto-modelo pré-preenchido em HTML (modelo padrão — substituir os "XXXX").
     * Editável pelo editor rico (Quill).
     */
    /** Cabeçalho com brasão (logo público da prefeitura). */
    private const CABECALHO = <<<'HTML'
<table style="border:none;border-collapse:collapse;width:100%"><tbody><tr>
<td style="border:none;width:110px;vertical-align:middle"><img src="https://pmsgra.net/logo.png" width="90"></td>
<td style="border:none;text-align:center;vertical-align:middle"><strong>PREFEITURA MUNICIPAL DE SÃO GONÇALO DO RIO ABAIXO</strong><br>AV. CONTORNO OESTE, 1.657, CIDADE UNIVERSITÁRIA<br>CEP 35935-000 – ESTADO DE MINAS GERAIS</td>
</tr></tbody></table>
<p><br></p>
HTML;

    public const MODELO = [
        'termo_referencia' => self::CABECALHO . <<<'HTML'
<p style="text-align:center"><strong>TERMO DE REFERÊNCIA</strong></p>
<p><strong>DESCRIÇÃO DA REALIDADE OBJETO DA PARCERIA:</strong><br>XXXXX</p>
<p><strong>JUSTIFICATIVA:</strong><br>XXXXX</p>
<p><strong>OBJETO DA PARCERIA:</strong><br>XXXXX.</p>
<p><strong>OBJETIVOS ESPECÍFICOS:</strong></p>
<ul><li>XXXX</li><li>XXXX</li><li>XXXX</li></ul>
<p><strong>ESTIMATIVA DE ORÇAMENTO:</strong><br>O orçamento estimado para execução da parceria é R$ XXXXXX (XXXXXX).</p>
<p>Dotação: XXXXXXXXXXXX &nbsp; Ficha: XXXXXX &nbsp; Fonte: XXXXXXX</p>
<p><strong>PRAZO DE EXECUÇÃO DO PROJETO:</strong><br>O prazo de execução do projeto é de XX meses.</p>
<p><br></p>
<p>Ficamos à disposição para maiores esclarecimentos.<br>Sendo o que temos para o momento, pede-se <strong>deferimento</strong>.</p>
<p><br></p>
<p style="text-align:center">XXXXXX<br>Secretaria Municipal de XXXXXXXX</p>
HTML,
        'oficio' => self::CABECALHO . <<<'HTML'
<p style="text-align:center"><strong>OFÍCIO PARA SOLICITAÇÃO DE CONVÊNIOS/PARCERIAS</strong></p>
<p><br></p>
<p style="text-align:right">São Gonçalo do Rio Abaixo, XX/XX/XXXX.</p>
<p>Ofício nº XXX/XXXX</p>
<p><br></p>
<p>Sr(a). XXXXXXXXX<br>Secretaria de Planejamento</p>
<p><br></p>
<p>Prezado(a) Senhor(a),</p>
<p>Encaminhamos a documentação pertinente e solicitamos a instauração do procedimento administrativo necessário à celebração de parceria, nos termos da Lei Federal nº 13.019, de 31 de julho de 2014, e do Decreto Municipal nº 048/2020, mediante Chamamento Público ou, quando cabível, por Dispensa ou Inexigibilidade de Chamamento Público, conforme os fundamentos fáticos e jurídicos constantes dos autos.</p>
<p>A parceria proposta será executada com recursos oriundos do XXXXXXXXXXXX, referente a dotação orçamentária nº XXXXXXXXXXXX, e tem por finalidade XXXXXXX, em consonância com as diretrizes da política pública setorial e com as competências desta Unidade Gestora.</p>
<p>Diante do exposto, requer-se a análise da documentação apresentada e o prosseguimento dos atos administrativos necessários à formalização da parceria, observadas as disposições da Lei Federal nº 13.019/2014, do Decreto Municipal nº 048/2020 e demais normas aplicáveis.</p>
<p><br></p>
<p>Atenciosamente,</p>
<p><br></p>
<p style="text-align:center">XXXXXXXX<br>Secretária Municipal de XXXXXXXX</p>
HTML,
        'pedido_parecer' => <<<'HTML'
<p>Solicito parecer financeiro do seguinte <strong>processo</strong>:</p>
<p><strong>Dotação</strong>: XXXXXX. &nbsp; <strong>Ficha</strong>: XXXXX &nbsp; <strong>Fonte</strong>: XXXXXXX</p>
<p><strong>Objeto do instrumento</strong>: XXXXXXXXX.</p>
<p><strong>Instrumento</strong>: XXXXXXXXXXX</p>
<p><strong>Parceiro</strong>: XXXXXXXXX</p>
<p><strong>Valor total</strong>: R$ XXXXX (XXXXX)</p>
<p><strong>Prazo</strong>: XX meses</p>
HTML,
        'parecer_financeiro' => self::CABECALHO . <<<'HTML'
<p><strong>Nº</strong> XXX/XXXX</p>
<p><strong>ORIGEM:</strong> Planejamento</p>
<p><strong>ASSUNTO:</strong> Dotação orçamentária e impacto financeiro</p>
<p><strong>DATA:</strong> XX/XX/XXXX</p>
<p><br></p>
<p>A Secretaria Municipal de Planejamento, após análise, informa à Secretaria Municipal de administração que há previsão orçamentária e financeira na Lei Orçamentária Anual, para <strong>"XXXXXXX"</strong>.</p>
<p>Previsão da Despesa:</p>
<table><thead><tr><th>Ano</th><th>Secretaria Municipal</th><th>Dotação</th><th>Recurso</th><th>Ficha</th><th>Desdobrada</th><th>Valor</th></tr></thead><tbody><tr><td>XXX</td><td>XXX</td><td>XXXXX</td><td>XXX</td><td>XXX</td><td>XXXXX</td><td>XXXXX</td></tr></tbody></table>
<table><thead><tr><th>Valor da Receita</th><th>Despesa Prevista</th><th>Impacto</th><th>Valor Total</th></tr></thead><tbody><tr><td>XXXXX</td><td>XXXXX</td><td>XXX</td><td>XXXXX</td></tr></tbody></table>
<p>A estimativa do Impacto Orçamentário Financeiro para realização da despesa prevista no Exercício XXX é de XXX% das receitas orçadas na Lei Orçamentária Anual nº XXXXX.</p>
<p>Sendo só no momento, me coloco à disposição para quaisquer eventuais esclarecimentos.</p>
<p><br></p>
<p style="text-align:center">XXXXXXXXXX<br>Secretário Municipal de Planejamento</p>
HTML,
        'abertura' => self::CABECALHO . <<<'HTML'
<p style="text-align:center"><strong>TERMO DE ABERTURA DE PROCESSO</strong></p>
<p><br></p>
<p>Processo nº: XXXXX</p>
<p style="text-align:right">Data de abertura: XX/XX/XXXX</p>
<p><br></p>
<p>Objeto: XXXXX.</p>
<p><br></p>
<p>Aos XX de XXXXX de 20XX, eu, XXXXXXXX, secretária da unidade gestora: XXXXX, <strong>ABRI</strong> o processo de <strong>XXXX referente ao XXXXXX</strong>, atendendo o disposto na Lei nº. 13.019/2014, art. 23.</p>
<p><br></p>
<p style="text-align:center">XXXXX<br>Secretária de XXXXXX<br>Unidade Gestora</p>
HTML,
        'edital' => <<<'HTML'
<p style="text-align:center"><strong>EDITAL DE CHAMAMENTO PÚBLICO Nº XXX/XXXX</strong></p>
<p><br></p>
<p>(Cole ou edite aqui o conteúdo do edital.)</p>
HTML,
    ];

    protected $fillable = [
        'processo_id', 'tipo', 'conteudo', 'assinado_por', 'assinado_em', 'codigo_validacao',
    ];

    /** Gera um código de validação único (ex.: A1B2-C3D4-E5). */
    public static function gerarCodigoValidacao(): string
    {
        do {
            $codigo = strtoupper(
                \Illuminate\Support\Str::random(4) . '-' .
                \Illuminate\Support\Str::random(4) . '-' .
                \Illuminate\Support\Str::random(2)
            );
        } while (static::where('codigo_validacao', $codigo)->exists());

        return $codigo;
    }

    protected function casts(): array
    {
        return ['assinado_em' => 'datetime'];
    }

    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class);
    }

    public function assinante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assinado_por');
    }

    public function assinado(): bool
    {
        return !is_null($this->assinado_em);
    }

    public function setorResponsavel(): string
    {
        return self::SETOR_RESPONSAVEL[$this->tipo] ?? 'ug';
    }

    public function etapaDesignada(): int
    {
        return self::ETAPA[$this->tipo] ?? 0;
    }

    public function setorAssinatura(): string
    {
        return self::ASSINATURA[$this->tipo]['setor'] ?? $this->setorResponsavel();
    }

    public function etapaAssinatura(): int
    {
        return self::ASSINATURA[$this->tipo]['etapa'] ?? $this->etapaDesignada();
    }

    private function emAndamento(Processo $processo): bool
    {
        return in_array($processo->status, ['em_planejamento', 'em_tramite']);
    }

    /**
     * Pode editar o CONTEÚDO (setor responsável, na etapa de edição, peça não assinada).
     */
    public function podeEditarConteudo(Processo $processo, ?User $user): bool
    {
        return $user
            && $this->emAndamento($processo)
            && !$processo->aguardandoRecebimento()  // precisa registrar o recebimento antes
            && !$this->assinado()
            && $user->setor === $this->setorResponsavel()
            && $processo->setor_atual === $this->setorResponsavel()
            && $processo->etapa === $this->etapaDesignada();
    }

    /**
     * Pode ASSINAR (setor de assinatura, na etapa de assinatura, com conteúdo preenchido).
     */
    public function podeAssinar(Processo $processo, ?User $user): bool
    {
        return $user
            && $this->emAndamento($processo)
            && !$processo->aguardandoRecebimento()  // precisa registrar o recebimento antes
            && !$this->assinado()
            && !empty($this->conteudo)
            && $user->setor === $this->setorAssinatura()
            && $processo->setor_atual === $this->setorAssinatura()
            && $processo->etapa === $this->etapaAssinatura();
    }
}
