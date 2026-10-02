<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcessoPeca extends Model
{
    use \App\Models\Concerns\PodeSerDevolvida;
    use \App\Models\Concerns\GuardaQuemAssinou;

    /** Peças do Planejamento que nascem fechadas à OSC (ver Peca::INTERNAS). */
    public const INTERNAS = [
        'oficio', 'abertura', 'pedido_parecer',
        'solicitacao_parecer_juridico', 'portaria_comissao',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $peca) {
            if ($peca->visivel_osc === null) {
                $peca->visivel_osc = !in_array($peca->tipo, self::INTERNAS, true);
            }
        });
    }

    public const TIPOS = [
        'oficio'             => 'Memorando',
        'termo_referencia'   => 'Termo de Referência',
        'pedido_parecer'     => 'Pedido de Parecer Financeiro',
        'parecer_financeiro' => 'Parecer Financeiro',
        'abertura'           => 'Termo de Abertura de Processo',
        'edital'             => 'Edital',
        'portaria_comissao'  => 'Portaria da Comissão de Seleção',
        'solicitacao_parecer_juridico' => 'Solicitação de Parecer Jurídico',
        'parecer_juridico'   => 'Parecer Jurídico',
        'comprovante_publicacao' => 'Comprovante de Publicação (Diário Oficial e site)',
        // Rota Dispensa/Inexigibilidade (no lugar do Edital + Jurídico)
        'justificativa_dispensa' => 'Justificativa de Dispensa/Inexigibilidade',
        'parecer_cnas'           => 'Parecer Técnico (CNAS)',
    ];

    /** Peças que são arquivo (já vêm assinadas de fora): preenchidas quando têm ao menos um anexo. */
    public const ARQUIVO = ['portaria_comissao', 'comprovante_publicacao'];

    /** Peças de texto que também aceitam anexos (o Edital e os seus anexos). */
    public const COM_ANEXOS = ['edital'];

    /** Peças opcionais, que não bloqueiam o avanço (o Parecer CNAS só vale para o SUAS). */
    public const OPCIONAIS = ['parecer_cnas'];

    /** Setor que preenche cada peça e em que etapa. */
    public const SETOR_RESPONSAVEL = [
        'oficio'             => 'ug',
        'termo_referencia'   => 'ug',
        'pedido_parecer'     => 'scp',  // a SCP protocola o pedido de parecer à SEPLAN
        'parecer_financeiro' => 'seplan',
        'abertura'           => 'ug',
        'edital'             => 'scp',
        'portaria_comissao'  => 'ug',   // a UG anexa a portaria ao revisar o edital
        'solicitacao_parecer_juridico' => 'scp',  // a SCP protocola o pedido de parecer à Procuradoria
        'parecer_juridico'   => 'pj',
        'comprovante_publicacao' => 'scp',  // a SCP anexa o comprovante ao publicar
        'justificativa_dispensa' => 'ug',   // a UG emite e assina a justificativa
        'parecer_cnas'           => 'ug',   // opcional — só nas parcerias do SUAS
    ];

    public const ETAPA = [
        'oficio'             => 0,
        'termo_referencia'   => 0,
        'pedido_parecer'     => 2,
        'parecer_financeiro' => 3,
        'abertura'           => 4,
        'edital'             => 5,
        'portaria_comissao'  => 6,
        'solicitacao_parecer_juridico' => 7,
        'parecer_juridico'   => 8,
        'comprovante_publicacao' => 9,
        // Rota Dispensa: ambas na etapa 5 (a justificativa substitui o edital)
        'justificativa_dispensa' => 5,
        'parecer_cnas'           => 5,
    ];

    /** Quem assina, quando difere de quem preenche (o Edital: a SCP elabora, a UG assina). */
    public const ASSINATURA = [
        'edital' => ['setor' => 'ug', 'etapa' => 6],
    ];

    /** Cabeçalho com brasão (logo público da prefeitura). */
    private const CABECALHO = <<<'HTML'
<table style="border:none;border-collapse:collapse;width:100%"><tbody><tr>
<td style="border:none;width:110px;vertical-align:middle"><img src="https://pmsgra.net/logo.png" width="90"></td>
<td style="border:none;text-align:center;vertical-align:middle"><strong>PREFEITURA MUNICIPAL DE SÃO GONÇALO DO RIO ABAIXO</strong><br>AV. CONTORNO OESTE, 1.657, CIDADE UNIVERSITÁRIA<br>CEP 35935-000 – ESTADO DE MINAS GERAIS</td>
</tr></tbody></table>
<p><br></p>
HTML;

    /** Texto-modelo HTML de cada peça (substituir os "XXXX"). */
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
<p style="text-align:center">{{responsavel_nome}}<br>Secretaria Municipal de {{unidade_gestora}}</p>
HTML,
        'oficio' => self::CABECALHO . <<<'HTML'
<p style="text-align:center"><strong>MEMORANDO PARA SOLICITAÇÃO DE PARCERIAS</strong></p>
<p><br></p>
<p style="text-align:right">{{cidade}}, {{data}}.</p>
<p>Memorando nº XXX/XXXX</p>
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
<p style="text-align:center">{{responsavel_nome}}<br>Secretária Municipal de {{unidade_gestora}}</p>
HTML,
        'pedido_parecer' => self::CABECALHO . <<<'HTML'
<p style="text-align:center"><strong>PEDIDO DE PARECER FINANCEIRO</strong></p>
<p><br></p>
<p>Solicito parecer financeiro do seguinte <strong>processo</strong>:</p>
<p><strong>Dotação</strong>: XXXXXX. &nbsp; <strong>Ficha</strong>: XXXXX &nbsp; <strong>Fonte</strong>: XXXXXXX</p>
<p><strong>Objeto do instrumento</strong>: XXXXXXXXX.</p>
<p><strong>Instrumento</strong>: XXXXXXXXXXX</p>
<p><strong>Parceiro</strong>: XXXXXXXXX</p>
<p><strong>Valor total</strong>: R$ XXXXX (XXXXX)</p>
<p><strong>Prazo</strong>: XX meses</p>
HTML,
        'parecer_financeiro' => self::CABECALHO . <<<'HTML'
<p><strong>Nº</strong> XXX/{{ano}}</p>
<p><strong>ORIGEM:</strong> Planejamento</p>
<p><strong>ASSUNTO:</strong> Dotação orçamentária e impacto financeiro</p>
<p><strong>DATA:</strong> XX/XX/XXXX</p>
<p><br></p>
<p>A Secretaria Municipal de Planejamento, após análise, informa à Secretaria Municipal de administração que há previsão orçamentária e financeira na Lei Orçamentária Anual, para <strong>"XXXXXXX"</strong>.</p>
<p>Previsão da Despesa:</p>
<table><thead><tr><th>Ano</th><th>Secretaria Municipal</th><th>Dotação</th><th>Recurso</th><th>Ficha</th><th>Desdobrada</th><th>Valor</th></tr></thead><tbody><tr><td>XXX</td><td>XXX</td><td>XXXXX</td><td>XXX</td><td>XXX</td><td>XXXXX</td><td>XXXXX</td></tr></tbody></table>
<table><thead><tr><th>Valor da Receita</th><th>Despesa Prevista</th><th>Impacto</th><th>Valor Total</th></tr></thead><tbody><tr><td>XXXXX</td><td>XXXXX</td><td>XXX</td><td>XXXXX</td></tr></tbody></table>
<p>A estimativa do Impacto Orçamentário Financeiro para realização da despesa prevista no Exercício {{ano}} é de XXX% das receitas orçadas na Lei Orçamentária Anual nº XXXXX.</p>
<p>Sendo só no momento, me coloco à disposição para quaisquer eventuais esclarecimentos.</p>
<p><br></p>
<p style="text-align:center">XXXXXXXXXX<br>Secretário Municipal de Planejamento</p>
HTML,
        'abertura' => self::CABECALHO . <<<'HTML'
<p style="text-align:center"><strong>TERMO DE ABERTURA DE PROCESSO</strong></p>
<p><br></p>
<p>Processo nº: {{numero_processo}}</p>
<p style="text-align:right">Data de abertura: XX/XX/XXXX</p>
<p><br></p>
<p>Objeto: XXXXX.</p>
<p><br></p>
<p>Aos XX de XXXXX de 20XX, eu, {{responsavel_nome}}, secretária da unidade gestora: {{unidade_gestora}}, <strong>ABRI</strong> o processo de <strong>XXXX referente ao XXXXXX</strong>, atendendo o disposto na Lei nº. 13.019/2014, art. 23.</p>
<p><br></p>
<p style="text-align:center">{{responsavel_nome}}<br>Secretária de {{unidade_gestora}}<br>Unidade Gestora</p>
HTML,
        'edital' => <<<'HTML'
<p style="text-align:center"><strong>EDITAL DE CHAMAMENTO PÚBLICO Nº XXX/{{ano}}</strong></p>
<p><br></p>
<p>(Cole ou edite aqui o conteúdo do edital.)</p>
HTML,
        'solicitacao_parecer_juridico' => self::CABECALHO . <<<'HTML'
<p style="text-align:right">{{cidade}}, {{data}}.</p>
<p><br></p>
<p><strong>A/C Procuradoria Jurídica Municipal</strong></p>
<p><br></p>
<p>Venho por meio deste solicitar parecer jurídico acerca da possibilidade de XXXXXXXXXXXX, referente ao processo de {{numero_processo}}, conforme estabelece a Lei Federal nº 13.019/2014. Também envolve a análise da minuta do termo, que segue em anexo.</p>
<p>Sendo o que temos para o momento, desde já agradecemos.</p>
<p><br></p>
<p style="text-align:center">{{responsavel_nome}}<br>Secretaria Municipal de {{unidade_gestora}}<br>Unidade Gestora</p>
HTML,
        'parecer_juridico' => self::CABECALHO . <<<'HTML'
<p style="text-align:center"><strong>PARECER JURÍDICO Nº XXX/{{ano}}</strong></p>
<p><strong>PROCESSO:</strong> {{numero_processo}}</p>
<p><strong>INTERESSADO:</strong> Secretaria Municipal de {{unidade_gestora}} — Unidade Gestora</p>
<p><strong>ASSUNTO:</strong> Análise jurídica da regularidade do procedimento e da minuta do termo (Lei Federal nº 13.019/2014).</p>
<p><br></p>
<p><strong>I — RELATÓRIO</strong></p>
<p>XXXXXXXXXXXX</p>
<p><strong>II — FUNDAMENTAÇÃO</strong></p>
<p>XXXXXXXXXXXX</p>
<p><strong>III — CONCLUSÃO</strong></p>
<p>Ante o exposto, esta Procuradoria opina pela XXXXXXXX (regularidade jurídica) do feito, podendo o processo prosseguir para a publicação.</p>
<p><br></p>
<p style="text-align:center">XXXXXXXXXX<br>Procurador(a) do Município<br>Procuradoria Jurídica</p>
HTML,
        'justificativa_dispensa' => self::CABECALHO . <<<'HTML'
<p style="text-align:center"><strong>JUSTIFICATIVA PARA INEXIGIBILIDADE OU DISPENSA</strong><br>(art. 32, da Lei nº 13.019/2014)</p>
<p style="text-align:right">{{cidade}}, {{data}}.</p>
<p><br></p>
<p><strong>ÓRGÃO RESPONSÁVEL:</strong> Secretaria Municipal de {{unidade_gestora}}</p>
<p><strong>OSC:</strong> XXXXXXXXXX</p>
<p><strong>DOTAÇÃO ORÇAMENTÁRIA:</strong> XXXXXXXXXX &nbsp; Ficha XXXX &nbsp; Fonte XXXX</p>
<p><strong>DURAÇÃO:</strong> XX meses</p>
<p><strong>OBJETO DA PARCERIA:</strong> XXXXXXXXXX.</p>
<p><br></p>
<p><strong>1. DESCRIÇÃO DA REALIDADE OBJETO DA PARCERIA:</strong></p>
<p>XXXXXXXXXX é uma entidade sem fins lucrativos, que desenvolve atividades/projetos no município de São Gonçalo do Rio Abaixo. Por isso, XXXXXXXXXX.</p>
<p><strong>2. JUSTIFICATIVA</strong></p>
<p>Considerando que a Lei Federal 13.019/2014 estabeleceu o regime jurídico das parcerias voluntárias, com ou sem transferência de recursos financeiros, entre a Administração Pública e Organizações da Sociedade Civil, em regime de mútua cooperação, para a consecução de finalidades de interesse público.</p>
<p>Considerando que a referida lei passou a ser aplicada aos Municípios a partir de 1º de janeiro de 2017, estabelecendo diversos critérios para a formalização de parcerias, dentre eles a regra geral de Chamamento Público.</p>
<p>Considerando a expedição, aos 03/03/2020, do Decreto Municipal 048/2020 que alterou o Decreto 184/2017, que regulamenta a Lei nº 13.019/2014 no âmbito do município de São Gonçalo do Rio Abaixo.</p>
<p>Considerando que o artigo 30, inciso VI, da Lei nº 13.019/2014 prevê a dispensa do procedimento de Chamamento Público "no caso de atividades voltadas ou vinculadas a serviços de educação, saúde e assistência social, desde que executadas por organizações da sociedade civil previamente credenciadas pelo órgão gestor da respectiva política".</p>
<p>Considerando que a OSC atende aos critérios do art. 2º, I, da Lei 13.019/2014, por ser organização da sociedade civil, sem fins lucrativos, de relevância pública e social, com Estatuto que prevê a destinação do patrimônio a instituição de mesma natureza ou ao Poder Público em caso de dissolução, e que mantém escrituração contábil de acordo com as Normas Brasileiras de Contabilidade.</p>
<p>Considerando que a entidade apresentou todos os documentos exigidos na Lei nº 13.019/2014, cumprindo os requisitos mínimos para a formalização do Termo de Parceria.</p>
<p>Diante do exposto, entendemos haver justificativa válida, idônea e de interesse público para a celebração de Termo de XXXXXX por XXXXXX de Chamamento Público, conforme art. 30, VI, da Lei Federal nº 13.019/2014.</p>
<p><br></p>
<p style="text-align:center">{{responsavel_nome}}<br>Secretária Municipal de {{unidade_gestora}}<br>Unidade Gestora</p>
HTML,
        'parecer_cnas' => self::CABECALHO . <<<'HTML'
<p style="text-align:center"><strong>PARECER TÉCNICO</strong><br>(Art. 3º, §2º, II da Resolução nº 21/2016 - CNAS)</p>
<p><br></p>
<p><strong>ÓRGÃO RESPONSÁVEL:</strong> Secretaria Municipal de {{unidade_gestora}}</p>
<p><strong>OSC:</strong> XXXXXXXXXX</p>
<p><strong>OBJETO DA PARCERIA:</strong> Firmar termo de parceria para a OSC executar XXXXXXXXXX no município de São Gonçalo do Rio Abaixo.</p>
<p><br></p>
<p>O presente parecer foi elaborado observando o disposto na Resolução nº 21/2016 - CNAS, que regulamenta e trata dos requisitos para a dispensa de chamamento público de OSC.</p>
<p>A OSC oferece um serviço nos moldes da Política Nacional de Assistência Social e da Tipificação Nacional de Serviços Socioassistenciais (Resolução CNAS nº 109/2009), enquadrando-se na proteção social XXXXXX, por meio dos serviços de XXXXXXXXXX.</p>
<p>Destaca-se ainda que a OSC é credenciada na Secretaria Municipal de Trabalho e Desenvolvimento Social e no Conselho Municipal de Assistência Social.</p>
<p>Cabe salientar que o Município de São Gonçalo do Rio Abaixo não possui outros serviços socioassistenciais voltados a XXXXXXXXXX.</p>
<p>Diante do exposto, conclui-se que as atividades exercidas pela OSC não podem ser interrompidas, tendo em vista que a descontinuidade da oferta pela entidade apresenta dano mais gravoso à integridade do usuário.</p>
<p><br></p>
<p style="text-align:center">XXXXXXXXXX<br>Assistente Social<br>Secretaria Municipal de {{unidade_gestora}}</p>
HTML,
    ];

    protected $fillable = [
        'processo_id', 'tipo', 'visivel_osc', 'conteudo', 'assinado_por', 'assinado_em', 'assinante_nome', 'assinante_cargo', 'codigo_validacao',
    ];

    /** Conteúdo inicial já com os dados conhecidos do processo (número, UG, data). */
    public static function conteudoInicial(string $tipo, Processo $processo): ?string
    {
        return \App\Support\Modelo::preencher(self::MODELO[$tipo] ?? null, [
            'numero_processo' => $processo->numero,
            'unidade_gestora' => $processo->orgao?->name,
            'responsavel_nome'=> $processo->criador?->name,
            'cidade'          => 'São Gonçalo do Rio Abaixo',
            'data'            => now()->format('d/m/Y'),
            'ano'             => now()->year,
        ]);
    }

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
        return ['assinado_em' => 'datetime', 'visivel_osc' => 'boolean', 'devolvida_em' => 'datetime'];
    }

    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class);
    }

    public function anexos(): HasMany
    {
        return $this->hasMany(ProcessoPecaAnexo::class, 'processo_peca_id')->latest();
    }

    public function assinante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assinado_por');
    }

    public function assinado(): bool
    {
        return !is_null($this->assinado_em);
    }

    /* ---- interface do dossiê da OSC: ver Proposta::dossieParaOsc() ---- */

    public function rotuloDoDossie(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    /** A peça do Planejamento é sempre texto; os anexos vivem à parte. */
    public function ehArquivoNoDossie(): bool
    {
        return false;
    }

    public function temArquivo(): bool
    {
        return false;
    }

    public function chaveDoDossie(): string
    {
        return 'processo:' . $this->id;
    }

    public function origemNoDossie(): string
    {
        return 'processo';
    }

    /** A peça é do tipo ARQUIVO (sem editor/assinatura — só upload)? */
    public function ehArquivo(): bool
    {
        return in_array($this->tipo, self::ARQUIVO, true);
    }

    /** A peça aceita anexos (ARQUIVO ou peça de texto que também aceita, como o Edital)? */
    public function aceitaAnexos(): bool
    {
        return $this->ehArquivo() || in_array($this->tipo, self::COM_ANEXOS, true);
    }

    /** Tem ao menos um anexo? (peça ARQUIVO considera-se preenchida quando sim). */
    public function temAnexo(): bool
    {
        return $this->anexos()->exists();
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

    /** Pode editar o conteúdo? Setor responsável, na etapa de edição, peça não assinada. */
    public function podeEditarConteudo(Processo $processo, ?User $user): bool
    {
        return $user
            && !$this->ehArquivo()                  // peça ARQUIVO não tem texto — ver podeAnexar()
            && $this->emAndamento($processo)
            && !$processo->aguardandoRecebimento()  // precisa registrar o recebimento antes
            && !$this->assinado()
            && $user->setor === $this->setorResponsavel()
            && $processo->setor_atual === $this->setorResponsavel()
            && $processo->etapa === $this->etapaDesignada();
    }

    /** Por que não pode editar agora (a etapa, onde o processo está e com quem); null se pode. */
    public function motivoNaoPodeEditar(Processo $processo, ?User $user): ?string
    {
        if ($this->podeEditarConteudo($processo, $user)) {
            return null;
        }

        $rotulo = fn (?string $s) => Processo::SETORES[$s] ?? (string) $s;
        $meuSetor = $rotulo($this->setorResponsavel());

        if (!$user) {
            return 'Entre no sistema para editar este documento.';
        }

        if (!$this->emAndamento($processo)) {
            return 'O processo está '.mb_strtolower(Processo::STATUS[$processo->status] ?? $processo->status)
                .' e não aceita mais edição.';
        }

        if ($processo->aguardandoRecebimento()) {
            return 'O processo chegou ao seu setor mas ainda não foi recebido. '
                .'Registre o recebimento na tela do processo para liberar a edição.';
        }

        if ($this->assinado()) {
            return 'Este documento já foi assinado e não pode mais ser alterado.';
        }

        if ($user->setor !== $this->setorResponsavel()) {
            return "Este documento é preenchido pelo setor {$meuSetor}, e o seu é "
                .$user->setorLabel().'.';
        }

        // Daqui para baixo o usuário É do setor responsável — só não chegou a vez.
        $etapaDoc  = $this->etapaDesignada() + 1;   // as etapas são exibidas a partir de 1
        $etapaAtual = $processo->etapa + 1;
        $total     = $processo->totalEtapas();
        $acao      = $processo->etapaInfo($this->etapaDesignada())['acao'] ?? null;
        $ondeEsta  = "O processo está na etapa {$etapaAtual}/{$total}, com o setor "
            .$rotulo($processo->setor_atual).'.';

        if ($processo->etapa < $this->etapaDesignada()) {
            return "Ainda não é a vez deste documento: ele é preenchido na etapa {$etapaDoc}"
                .($acao ? " ({$acao})" : '').". {$ondeEsta}";
        }

        if ($processo->etapa > $this->etapaDesignada()) {
            return "A etapa deste documento (etapa {$etapaDoc}) já passou. {$ondeEsta}";
        }

        // Mesma etapa, setor certo, mas o processo está com outro setor.
        return $ondeEsta." Ele volta para o setor {$meuSetor} para este documento ser preenchido.";
    }

    /** Pode anexar? Peça de arquivo ou que aceita anexos, pelo setor responsável, na etapa dela. */
    public function podeAnexar(Processo $processo, ?User $user): bool
    {
        return $user
            && $this->aceitaAnexos()
            && $this->emAndamento($processo)
            && !$processo->aguardandoRecebimento()  // precisa registrar o recebimento antes
            && !$this->assinado()
            && $user->setor === $this->setorResponsavel()
            && $processo->setor_atual === $this->setorResponsavel()
            && $processo->etapa === $this->etapaDesignada();
    }

    /** Pode assinar? Setor de assinatura, na etapa de assinatura, com conteúdo preenchido. */
    public function podeAssinar(Processo $processo, ?User $user): bool
    {
        return $user
            && $this->emAndamento($processo)
            && !$processo->aguardandoRecebimento()  // precisa registrar o recebimento antes
            && !$this->assinado()
            && !empty($this->conteudo)
            && $user->setor === $this->setorAssinatura()
            && $processo->setor_atual === $this->setorAssinatura()
            && $processo->etapa === $this->etapaAssinatura()
            // Assinatura reservada a um perfil — o Parecer Financeiro é do
            // Responsável pela SEPLAN. Ver Peca::ASSINATURA_RESERVADA.
            && (($perfil = Peca::perfilQueAssina($this->tipo)) === null || $user->hasRole($perfil));
    }
}
