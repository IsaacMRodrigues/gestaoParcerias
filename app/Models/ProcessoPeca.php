<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessoPeca extends Model
{
    public const TIPOS = [
        'oficio'             => 'Ofício',
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
        'pedido_parecer'     => 'scp',
        'parecer_financeiro' => 'seplan',
        'abertura'           => 'ug',
        'edital'             => 'scp',
    ];

    public const ETAPA = [
        'oficio'             => 0,
        'pedido_parecer'     => 1,
        'parecer_financeiro' => 2,
        'abertura'           => 3,
        'edital'             => 4,
    ];

    /**
     * Quem ASSINA (quando difere de quem preenche). Ex.: o Edital é elaborado
     * pela SCP (etapa 4) mas assinado pela UG (etapa 5).
     */
    public const ASSINATURA = [
        'edital' => ['setor' => 'ug', 'etapa' => 5],
    ];

    /**
     * Texto-modelo pré-preenchido em HTML (modelo padrão — substituir os "XXXX").
     * Editável pelo editor rico (Quill).
     */
    public const MODELO = [
        'oficio' => <<<'HTML'
<p class="ql-align-center"><strong>PREFEITURA MUNICIPAL DE SÃO GONÇALO DO RIO ABAIXO</strong></p>
<p class="ql-align-center">Av. Contorno Oeste, 1.657, Cidade Universitária — CEP 35935-000 — Estado de Minas Gerais</p>
<p><br></p>
<p class="ql-align-center"><strong>OFÍCIO PARA SOLICITAÇÃO DE CONVÊNIOS/PARCERIAS</strong></p>
<p><br></p>
<p class="ql-align-right">São Gonçalo do Rio Abaixo, XX/XX/XXXX.</p>
<p>Ofício nº XXX/XXXX</p>
<p><br></p>
<p>Sr(a). XXXXXXXXX<br>Secretaria de Planejamento</p>
<p><br></p>
<p>Prezado(a) Senhor(a),</p>
<p>Encaminhamos a documentação pertinente e solicitamos a instauração do procedimento administrativo necessário à celebração de parceria, nos termos da Lei Federal nº 13.019, de 31 de julho de 2014, mediante Chamamento Público ou, quando cabível, por Dispensa ou Inexigibilidade de Chamamento Público, conforme os fundamentos fáticos e jurídicos constantes dos autos.</p>
<p>A parceria proposta será executada com recursos oriundos do XXXXXXXXXXXX, à conta da Dotação Orçamentária nº XXXXXXXXXXXX, e tem por finalidade XXXXXXX, em consonância com as diretrizes da política pública setorial e com as competências desta Unidade Gestora.</p>
<p>Diante do exposto, requer-se a análise da documentação apresentada e o prosseguimento dos atos administrativos necessários à formalização da parceria, observadas as disposições da Lei Federal nº 13.019/2014, do Decreto Municipal regulamentador e demais normas aplicáveis.</p>
<p><br></p>
<p>Atenciosamente,</p>
<p><br></p>
<p class="ql-align-center">XXXXXXXX<br>Secretária Municipal de XXXXXXXX</p>
HTML,
        'pedido_parecer' => <<<'HTML'
<p>Solicito parecer financeiro do seguinte processo:</p>
<p><strong>Dotação:</strong> XXXXX<br><strong>Ficha:</strong> XXXXX<br><strong>Fonte:</strong> XXXXX</p>
<p><strong>Objeto do instrumento:</strong> XXXXX<br><strong>Instrumento:</strong> XXXXX<br><strong>Parceiro:</strong> XXXXX</p>
<p><strong>Valor total:</strong> R$ XXXXX (XXXXX)<br><strong>Prazo:</strong> XX meses</p>
HTML,
        'parecer_financeiro' => <<<'HTML'
<p class="ql-align-center"><strong>PARECER FINANCEIRO</strong></p>
<p>Ofício nº XXX/XXXX &nbsp;&nbsp;&nbsp; Data: XX/XX/XXXX</p>
<p>A Secretaria Municipal de Planejamento, após análise, informa:</p>
<p><strong>Valor solicitado:</strong> R$ XXXXX<br><strong>Valor da Receita (exercício):</strong> R$ XXXXX<br><strong>Percentual em relação às receitas orçamentárias:</strong> XX%</p>
<p><strong>Parecer:</strong> XXXXX</p>
<p><br></p>
<p class="ql-align-center">_____________________________<br>Secretaria Municipal de Planejamento</p>
HTML,
        'abertura' => <<<'HTML'
<p class="ql-align-center"><strong>TERMO DE ABERTURA DE PROCESSO</strong></p>
<p><strong>Processo nº:</strong> XXXXX<br><strong>Data de abertura:</strong> XX/XX/XXXX<br><strong>Objeto:</strong> XXXXX</p>
<p>Aos XX de XXXXX de 20XX, eu, [nome], secretário(a) da unidade gestora XXXXX, ABRI o processo referente a XXXXX, atendendo o disposto na Lei nº 13.019/2014, art. 23.</p>
<p><br></p>
<p class="ql-align-center">_____________________________<br>Secretário(a) — Unidade Gestora</p>
HTML,
        'edital' => <<<'HTML'
<p class="ql-align-center"><strong>EDITAL DE CHAMAMENTO PÚBLICO Nº XXX/XXXX</strong></p>
<p><br></p>
<p>(Cole ou edite aqui o conteúdo do edital.)</p>
HTML,
    ];

    protected $fillable = [
        'processo_id', 'tipo', 'conteudo', 'assinado_por', 'assinado_em',
    ];

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
            && !$this->assinado()
            && !empty($this->conteudo)
            && $user->setor === $this->setorAssinatura()
            && $processo->setor_atual === $this->setorAssinatura()
            && $processo->etapa === $this->etapaAssinatura();
    }
}
