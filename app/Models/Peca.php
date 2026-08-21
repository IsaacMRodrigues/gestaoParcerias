<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Peca extends Model
{
    protected $table = 'pecas';

    protected $fillable = [
        'pecaable_type', 'pecaable_id', 'categoria', 'chave', 'rotulo',
        'tipo', 'obrigatorio', 'ordem',
        'conteudo', 'arquivo_path', 'arquivo_nome', 'tamanho', 'mime_type',
        'assinado_por', 'assinado_em', 'codigo_validacao',
        'contra_assinado_por', 'contra_assinado_em', 'codigo_validacao_contra',
    ];

    protected function casts(): array
    {
        return [
            'obrigatorio'        => 'boolean',
            'assinado_em'        => 'datetime',
            'contra_assinado_em' => 'datetime',
        ];
    }

    /**
     * Checklists documentais por categoria (Módulo Unidade Gestora 2.2 e 2.3).
     * tipo: 'modelo' = texto + assinatura digital | 'arquivo' = upload.
     */
    public const TEMPLATES = [
        // 2.2.1 Chamamento Público
        'chamamento_publico' => [
            ['chave' => 'edital',                    'rotulo' => 'Edital (modelo padrão)',                 'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'anexos',                    'rotulo' => 'Anexos',                                  'tipo' => 'arquivo', 'obrigatorio' => false],
            ['chave' => 'comissao_selecao',          'rotulo' => 'Comissão de Seleção',                     'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'parecer_juridico',          'rotulo' => 'Parecer jurídico (modelo padrão)',        'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'pub_extrato_edital',        'rotulo' => 'Publicação do extrato do edital',         'tipo' => 'arquivo', 'obrigatorio' => true],
            // Julgamento das propostas pela Comissão de Seleção (UG)
            ['chave' => 'relatorio_comissao',        'rotulo' => 'Relatório da Comissão de Seleção (modelo padrão)', 'tipo' => 'modelo', 'obrigatorio' => true],
            ['chave' => 'ata_comissao',              'rotulo' => 'Ata da Comissão de Seleção (modelo padrão)',       'tipo' => 'modelo', 'obrigatorio' => true],
            ['chave' => 'resultado_parcial',         'rotulo' => 'Resultado provisório (modelo padrão)',    'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'pub_resultado_parcial',     'rotulo' => 'Publicação do resultado provisório',      'tipo' => 'arquivo', 'obrigatorio' => true],
            // Os recursos não são uma peça única: cada OSC protocola o seu pelo
            // portal e recebe resposta própria (ver o model Recurso).
            ['chave' => 'resultado_definitivo',      'rotulo' => 'Resultado definitivo (modelo padrão)',    'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'pub_resultado_definitivo',  'rotulo' => 'Publicação do resultado definitivo',      'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'termo_homologacao',         'rotulo' => 'Termo de Adjudicação e Homologação (modelo padrão)', 'tipo' => 'modelo', 'obrigatorio' => true],
        ],

        // 2.2.2 Dispensa ou Inexigibilidade de Chamamento
        'dispensa_inexigibilidade' => [
            ['chave' => 'parecer_tecnico_cnas',      'rotulo' => 'Parecer técnico (CNAS) — SUAS (opcional)','tipo' => 'modelo',  'obrigatorio' => false],
            ['chave' => 'justificativa',             'rotulo' => 'Justificativa de dispensa/inexigibilidade','tipo' => 'modelo', 'obrigatorio' => true],
            ['chave' => 'pub_extrato',               'rotulo' => 'Publicação do extrato',                   'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'plano_trabalho',            'rotulo' => 'Plano de trabalho',                       'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'aprovacao_plano',           'rotulo' => 'Aprovação do plano de trabalho',          'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'docs_habilitacao',          'rotulo' => 'Documentos de habilitação',               'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'verificacao_habilitacao',   'rotulo' => 'Verificação da habilitação (checklist)',  'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'gestor_parceria',           'rotulo' => 'Gestor da parceria',                      'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'comissao_monitoramento',    'rotulo' => 'Comissão de Monitoramento e Avaliação',   'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'parecer_tecnico_celebracao','rotulo' => 'Parecer técnico para celebração',         'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'minuta_termo',              'rotulo' => 'Minuta do termo',                         'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'certidao_autuacao',         'rotulo' => 'Certidão de autuação',                    'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'protocolo_juridico',        'rotulo' => 'Protocolo na Unidade Jurídica',           'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'parecer_juridico',          'rotulo' => 'Parecer jurídico',                        'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'termo',                     'rotulo' => 'Termo',                                   'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'pub_extrato_final',         'rotulo' => 'Publicação do extrato',                   'tipo' => 'arquivo', 'obrigatorio' => true],
        ],

        // Celebração (Fluxo Etapa de Celebração) — ancorada na proposta aprovada
        'celebracao' => [
            ['chave' => 'convocacao_osc',        'rotulo' => 'Convocação da OSC (modelo padrão)',                      'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'plano_trabalho',        'rotulo' => 'Plano de Trabalho (enviado pela OSC)',                   'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'docs_habilitacao',      'rotulo' => 'Documentos de habilitação (enviados pela OSC)',          'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'aprovacao_plano',       'rotulo' => 'Aprovação do Plano de Trabalho (modelo padrão)',         'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'pedido_parecer',        'rotulo' => 'Pedido de Parecer Financeiro (modelo padrão)',           'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'parecer_financeiro',    'rotulo' => 'Parecer Financeiro (modelo padrão)',                     'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'portaria_gestor',       'rotulo' => 'Portaria do Gestor da Parceria',                         'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'portaria_comissao_mon', 'rotulo' => 'Portaria da Comissão de Monitoramento e Avaliação',      'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'parecer_tecnico',       'rotulo' => 'Parecer Técnico para celebração (modelo padrão)',        'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'protocolo_juridico',    'rotulo' => 'Protocolo na Unidade Jurídica (modelo padrão)',          'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'parecer_juridico',      'rotulo' => 'Parecer Jurídico (modelo padrão)',                       'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'parecer_scp',           'rotulo' => 'Parecer da SCP — conferência final (modelo padrão)',    'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'termo',                 'rotulo' => 'Termo de Parceria (modelo padrão)',                      'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'comprovante_publicacao','rotulo' => 'Comprovante de publicação (Diário Oficial e site)',      'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'autorizacao_inicio',    'rotulo' => 'Autorização de Início de Execução (modelo padrão)',      'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'dados_bancarios',       'rotulo' => 'Dados bancários (enviados pela OSC)',                    'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'op_global',             'rotulo' => 'Ordem de Pagamento Global (modelo padrão)',              'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'comprovante_empenho',   'rotulo' => 'Comprovante de empenho global',                          'tipo' => 'arquivo', 'obrigatorio' => true],
        ],

        // 2.3.4 Apostilamento
        'apostilamento' => [
            ['chave' => 'manifestacao_osc',          'rotulo' => 'Manifestação da OSC',                     'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'orcamento_cotacao',         'rotulo' => 'Orçamento/Cotação',                       'tipo' => 'arquivo', 'obrigatorio' => false],
            ['chave' => 'extratos_bancarios',        'rotulo' => 'Extratos bancários (movimento e aplicação)','tipo' => 'arquivo','obrigatorio' => false],
            ['chave' => 'plano_trabalho_atualizado', 'rotulo' => 'Plano de Trabalho atualizado',            'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'aprovacao_alteracao_plano', 'rotulo' => 'Aprovação da alteração do plano',         'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'autorizacao_ug',            'rotulo' => 'Autorização da Unidade Gestora',          'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'termo_apostilamento',       'rotulo' => 'Termo de apostilamento',                  'tipo' => 'modelo',  'obrigatorio' => true],
        ],

        // 2.3.2 Aditivo (complementa o Aditivo já existente com a documentação)
        'aditivo' => [
            ['chave' => 'manifestacao_osc',          'rotulo' => 'Manifestação da OSC',                     'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'formulario_prorrogacao',    'rotulo' => 'Formulário de prorrogação de prazo',      'tipo' => 'arquivo', 'obrigatorio' => false],
            ['chave' => 'justificativa_tecnica_osc', 'rotulo' => 'Justificativa Técnica da OSC',            'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'ata_eleicao',               'rotulo' => 'Ata de eleição/diretoria (se houver)',    'tipo' => 'arquivo', 'obrigatorio' => false],
            ['chave' => 'certidoes_regularidade',    'rotulo' => 'Certidões de regularidade atualizadas',   'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'orcamento_cotacao',         'rotulo' => 'Orçamento/Cotação',                       'tipo' => 'arquivo', 'obrigatorio' => false],
            ['chave' => 'extratos_bancarios',        'rotulo' => 'Extratos bancários',                      'tipo' => 'arquivo', 'obrigatorio' => false],
            ['chave' => 'declaracao_capacidade',     'rotulo' => 'Declaração de Manutenção da Capacidade Técnica','tipo' => 'arquivo','obrigatorio' => false],
            ['chave' => 'plano_trabalho_atualizado', 'rotulo' => 'Plano de Trabalho atualizado',            'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'aprovacao_alteracao_plano', 'rotulo' => 'Aprovação da alteração do plano',         'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'parecer_financeiro',        'rotulo' => 'Parecer financeiro',                      'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'justificativa_ug',          'rotulo' => 'Justificativa da Unidade Gestora',        'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'autorizacao_ug',            'rotulo' => 'Autorização da Unidade Gestora',          'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'minuta_termo',              'rotulo' => 'Minuta do termo',                         'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'certidao_autuacao',         'rotulo' => 'Certidão de Autuação',                    'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'protocolo_juridico',        'rotulo' => 'Protocolo na Unidade Jurídica',           'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'parecer_juridico',          'rotulo' => 'Parecer jurídico',                        'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'termo_aditivo',             'rotulo' => 'Termo de aditivo',                        'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'pub_extrato',               'rotulo' => 'Publicação do extrato',                   'tipo' => 'arquivo', 'obrigatorio' => true],
        ],
    ];

    /**
     * Trâmite da Seleção (só categoria `chamamento_publico`): setor que PREENCHE
     * cada peça e em qual etapa de `Chamamento::ETAPAS_SELECAO`.
     *
     * As peças anteriores ao julgamento (Edital, anexos, portaria da Comissão,
     * parecer jurídico e publicação do extrato) vêm do Planejamento e ficam fora
     * do trâmite — seguem editáveis por quem tem a permissão de chamamentos.
     */
    public const SELECAO_SETOR = [
        'relatorio_comissao'       => 'ug',
        'ata_comissao'             => 'ug',
        'resultado_parcial'        => 'ug',
        'pub_resultado_parcial'    => 'scp',
        'resultado_definitivo'     => 'ug',
        'pub_resultado_definitivo' => 'scp',
        'termo_homologacao'        => 'scp',  // a SCP emite; o Prefeito assina
    ];

    /**
     * Setor responsável pelas peças ANTERIORES ao julgamento.
     *
     * Elas continuam fora do trâmite (não têm etapa: precisam estar prontas
     * antes de a Seleção começar, para o edital ser publicado). O que faltava
     * era dizer de QUEM é cada uma — sem isso, qualquer usuário com permissão
     * de chamamentos preenchia e assinava todas, e na prática a Unidade Gestora
     * acabou assinando o próprio parecer jurídico: quem pede o parecer o emitia.
     *
     * Aqui vale só o setor, nunca a ordem — é a diferença entre "não é a sua
     * vez" (trâmite) e "não é o seu papel" (segregação de função).
     */
    public const SELECAO_SETOR_PREVIO = [
        'edital'             => 'ug',
        'anexos'             => 'ug',
        'comissao_selecao'   => 'ug',   // portaria de designação da Comissão
        'parecer_juridico'   => 'pj',
        'pub_extrato_edital' => 'scp',  // publicações são da SCP, como as demais
    ];

    public const SELECAO_ETAPA = [
        'relatorio_comissao'       => 0,
        'ata_comissao'             => 0,
        'resultado_parcial'        => 0,
        'pub_resultado_parcial'    => 1,
        'resultado_definitivo'     => 2,
        'pub_resultado_definitivo' => 3,
        'termo_homologacao'        => 3,
    ];

    /**
     * Quem ASSINA, quando difere de quem preenche: o Termo de Adjudicação e
     * Homologação é emitido pela SCP (etapa 3) e assinado pelo Prefeito (etapa 4).
     */
    public const SELECAO_ASSINATURA = [
        'termo_homologacao' => ['setor' => 'pm', 'etapa' => 4],
    ];

    public const CATEGORIA_LABELS = [
        'chamamento_publico'       => 'Chamamento Público',
        'dispensa_inexigibilidade' => 'Dispensa / Inexigibilidade',
        'celebracao'               => 'Celebração da Parceria',
        'apostilamento'            => 'Apostilamento',
        'aditivo'                  => 'Termo Aditivo',
    ];

    /**
     * Trâmite da Celebração (categoria `celebracao`, ancorada na Proposta):
     * setor que PREENCHE cada peça e em qual etapa de
     * `Proposta::ETAPAS_CELEBRACAO`.
     */
    public const CELEBRACAO_SETOR = [
        'convocacao_osc'         => 'ug',
        'plano_trabalho'         => 'osc',
        'docs_habilitacao'       => 'osc',
        'aprovacao_plano'        => 'ug',
        'pedido_parecer'         => 'scp',
        'parecer_financeiro'     => 'seplan',
        'portaria_gestor'        => 'ug',
        'portaria_comissao_mon'  => 'ug',
        'parecer_tecnico'        => 'ug',
        'protocolo_juridico'     => 'scp',
        'parecer_juridico'       => 'pj',
        'parecer_scp'            => 'scp',
        'termo'                  => 'scp',  // Município assina; a OSC contra-assina
        'comprovante_publicacao' => 'scp',
        'autorizacao_inicio'     => 'scp',
        'dados_bancarios'        => 'osc',
        'op_global'              => 'scp',  // a SCP elabora; a UG assina
        'comprovante_empenho'    => 'scp',
    ];

    public const CELEBRACAO_ETAPA = [
        'convocacao_osc'         => 0,
        'plano_trabalho'         => 1,
        'docs_habilitacao'       => 1,
        'aprovacao_plano'        => 2,
        'pedido_parecer'         => 3,
        'parecer_financeiro'     => 4,
        'portaria_gestor'        => 5,
        'portaria_comissao_mon'  => 5,
        'parecer_tecnico'        => 5,
        'protocolo_juridico'     => 6,
        'parecer_juridico'       => 7,
        'parecer_scp'            => 8,
        'termo'                  => 8,
        'comprovante_publicacao' => 10,
        'autorizacao_inicio'     => 10,
        'dados_bancarios'        => 11,
        'op_global'              => 12,
        'comprovante_empenho'    => 14,
    ];

    /**
     * A Ordem de Pagamento Global é elaborada pela SCP (etapa 11) e assinada
     * pela Unidade Gestora (etapa 12).
     */
    public const CELEBRACAO_ASSINATURA = [
        'op_global' => ['setor' => 'ug', 'etapa' => 13],
    ];

    /**
     * Contra-assinatura ("assinatura das partes"): o Termo é assinado pelo
     * Município (SCP, etapa 8) e contra-assinado pela OSC na etapa 9.
     */
    public const CELEBRACAO_CONTRA_ASSINATURA = [
        'termo' => ['setor' => 'osc', 'etapa' => 9],
    ];

    /**
     * Itens (chave) que podem ser "puxados" do módulo Gestão de Parcerias —
     * ou seja, preenchidos a partir dos documentos que a OSC já enviou na proposta.
     */
    public const PUXAVEIS = [
        'dispensa_inexigibilidade' => ['plano_trabalho', 'docs_habilitacao'],
        'aditivo'                  => ['manifestacao_osc', 'formulario_prorrogacao', 'ata_eleicao', 'certidoes_regularidade', 'orcamento_cotacao', 'extratos_bancarios', 'declaracao_capacidade', 'plano_trabalho_atualizado'],
        'apostilamento'            => ['manifestacao_osc', 'orcamento_cotacao', 'extratos_bancarios', 'plano_trabalho_atualizado'],
    ];

    /** Cabeçalho com brasão (mesmo padrão das peças do trâmite). */
    private const CABECALHO = <<<'HTML'
<table style="border:none;border-collapse:collapse;width:100%"><tbody><tr>
<td style="border:none;width:110px;vertical-align:middle"><img src="https://pmsgra.net/logo.png" width="90"></td>
<td style="border:none;text-align:center;vertical-align:middle"><strong>PREFEITURA MUNICIPAL DE SÃO GONÇALO DO RIO ABAIXO</strong><br>AV. CONTORNO OESTE, 1.657, CIDADE UNIVERSITÁRIA<br>CEP 35935-000 – ESTADO DE MINAS GERAIS</td>
</tr></tbody></table>
<p><br></p>
HTML;

    /**
     * Texto-modelo HTML das peças "modelo" da Seleção/Documentação
     * (semeado no `sincronizar`). Usa editor rico (TinyMCE) na UI.
     */
    public const MODELO = [
        'chamamento_publico' => [
            'edital' => self::CABECALHO . <<<'HTML'
<p style="text-align:center"><strong>EDITAL DE CHAMAMENTO PÚBLICO Nº XXX/20XX</strong></p>
<p>O MUNICÍPIO DE SÃO GONÇALO DO RIO ABAIXO, por intermédio da Secretaria Municipal de XXXXXX, torna público o presente Edital visando a seleção de Organização da Sociedade Civil interessada em celebrar TERMO DE XXXXXX, nos termos da Lei Federal nº 13.019/2014, do Decreto Municipal nº 048/2020 e demais normas aplicáveis.</p>
<p><strong>CAPÍTULO I — DO OBJETO</strong></p>
<p>1.1. Constitui objeto do presente Edital a seleção de OSC interessada em celebrar Termo de XXXXXX que tenha por objeto XXXXXXXXXX.</p>
<p>1.1.1. O procedimento reger-se-á pela Lei nº 13.019/2014, Decreto Municipal nº 048/2020 e pelas condições deste Edital.</p>
<p><strong>CAPÍTULO II — DOS RECURSOS</strong></p>
<p>2.1. Será destinado o valor total de até R$ XXXXXXXX (XXXXXXXX), conforme disponibilidade orçamentária e financeira.</p>
<p>2.2. Dotação: XXXXXXXXXX &nbsp; Ficha: XXXX &nbsp; Fonte: XXXX</p>
<p><strong>CAPÍTULO III — DAS CONDIÇÕES DE PARTICIPAÇÃO</strong></p>
<p>3.1. Poderão participar as OSCs que atendam aos requisitos da Lei nº 13.019/2014 e apresentem a documentação de habilitação exigida neste Edital.</p>
<p><strong>CAPÍTULO IV — DO PRAZO E DA FORMA DE APRESENTAÇÃO DAS PROPOSTAS</strong></p>
<p>4.1. As propostas deverão ser apresentadas no período de XX/XX/XXXX a XX/XX/XXXX, na forma indicada neste Edital.</p>
<p><strong>CAPÍTULO V — DA COMISSÃO DE SELEÇÃO</strong></p>
<p>5.1. A análise e o julgamento das propostas serão realizados pela Comissão de Seleção designada por portaria.</p>
<p><strong>CAPÍTULO VI — DOS CRITÉRIOS DE JULGAMENTO</strong></p>
<p>6.1. As propostas serão avaliadas conforme os critérios e pontuações previstos neste Edital e anexos.</p>
<p><strong>CAPÍTULO VII — DOS RECURSOS</strong></p>
<p>7.1. Caberá recurso nos prazos e formas previstos na legislação aplicável e neste Edital.</p>
<p><strong>CAPÍTULO VIII — DA HOMOLOGAÇÃO E DA CELEBRAÇÃO</strong></p>
<p>8.1. Homologado o resultado, a OSC selecionada será convocada para celebração do Termo, observadas as exigências legais.</p>
<p style="text-align:right">São Gonçalo do Rio Abaixo/MG, XX de XXXX de 20XX.</p>
<p style="text-align:center">XXXXXXXXXX<br>Secretária Municipal de XXXXXX<br>Unidade Gestora</p>
HTML,
            'parecer_juridico' => self::CABECALHO . <<<'HTML'
<p style="text-align:center"><strong>PARECER JURÍDICO Nº XXX/20XX</strong></p>
<p><strong>PROCESSO:</strong> XXXXXXXX</p>
<p><strong>INTERESSADO:</strong> Secretaria Municipal de XXXXXX — Unidade Gestora</p>
<p><strong>ASSUNTO:</strong> Análise jurídica da regularidade do procedimento e da minuta do termo (Lei Federal nº 13.019/2014).</p>
<p><strong>I — RELATÓRIO</strong></p>
<p>XXXXXXXXXXXX</p>
<p><strong>II — FUNDAMENTAÇÃO</strong></p>
<p>XXXXXXXXXXXX</p>
<p><strong>III — CONCLUSÃO</strong></p>
<p>Ante o exposto, esta Procuradoria opina pela XXXXXXXX (regularidade jurídica) do feito, podendo o processo prosseguir.</p>
<p style="text-align:center">XXXXXXXXXX<br>Procurador(a) do Município<br>Procuradoria Jurídica</p>
HTML,
            'relatorio_comissao' => self::CABECALHO . <<<'HTML'
<p style="text-align:center"><strong>RELATÓRIO DA COMISSÃO DE SELEÇÃO</strong><br>Portaria nº. XXX/XXXX</p>
<p style="text-align:center"><strong>CHAMAMENTO PÚBLICO Nº. XXXX/XXXX</strong><br>LEI Nº 13.019/2014<br>DECRETO MUNICIPAL Nº 048/2020</p>
<p><strong>1. IDENTIFICAÇÃO</strong></p>
<p>Chamamento Público nº: XXXXXXXX<br>Processo Administrativo nº: XXXXXXXX<br>Modalidade de Parceria: (Termo de Fomento / Termo de Colaboração)<br>Organização da Sociedade Civil – OSC: XXXXXXXX<br>Objeto da Parceria: XXXXXXXX<br>Valor Global Proposto: R$ XXXXXXXX<br>Vigência da Parceria: XXXXXXXX</p>
<p><strong>2. BASE LEGAL E COMPETÊNCIA</strong></p>
<p>O presente Relatório Técnico é elaborado pela Comissão de Seleção, regularmente designada pela Portaria nº. XXXX/XXXX, em conformidade com a Lei Federal nº 13.019/2014 e o Decreto Municipal nº 048/2020, com a finalidade de analisar e manifestar-se, de forma expressa, objetiva e motivada, sobre a proposta apresentada no âmbito do Chamamento Público nº. XXX/XXXX.</p>
<p>A análise observa, ainda, os princípios da legalidade, impessoalidade, moralidade, publicidade, eficiência, planejamento, transparência e controle, conforme exigido pelos órgãos de controle interno e externo.</p>
<p><strong>3. ANÁLISE TÉCNICA DA PROPOSTA</strong></p>
<p><strong>a) Do mérito da proposta e conformidade com a modalidade adotada</strong></p>
<p>A Comissão analisou a Proposta verificando: aderência do objeto ao Chamamento Público; compatibilidade com a modalidade de parceria adotada; coerência entre metas, atividades, indicadores e resultados esperados; alinhamento com a política pública municipal correspondente.</p>
<p>Conclui-se que o mérito da proposta ( ) atende &nbsp; ( ) atende com ressalvas &nbsp; ( ) não atende aos critérios técnicos e objetivos estabelecidos no edital, na Lei nº 13.019/2014 e no Decreto Municipal nº 048/2020.</p>
<p>Justificativa técnica: XXXXXXXX</p>
<p><strong>b) Da identidade e da reciprocidade de interesse das partes</strong></p>
<p>Restou evidenciada a convergência de interesses institucionais entre a Administração Pública Municipal e a OSC proponente, caracterizando atuação em mútua cooperação, sem transferência indevida de responsabilidades típicas do ente público, conforme os pressupostos do MROSC e do Decreto Municipal nº 048/2020.</p>
<p>Manifestação da Comissão: ( ) Atendida &nbsp; ( ) Parcialmente atendida &nbsp; ( ) Não atendida</p>
<p><strong>c) Da viabilidade de execução da parceria</strong></p>
<p>A Comissão avaliou a viabilidade da execução considerando os critérios exigidos pelo controle interno e pelo TCE/MG, notadamente: capacidade técnica e operacional da OSC; compatibilidade entre metas, prazos e recursos; adequação da equipe técnica proposta; experiência prévia da entidade em objetos similares; riscos identificados e medidas mitigadoras.</p>
<p>Conclui-se que a proposta é ( ) viável &nbsp; ( ) viável com ajustes &nbsp; ( ) inviável, sob os aspectos técnico, operacional e financeiro.</p>
<p><strong>d) Da verificação do cronograma de desembolso</strong></p>
<p>O cronograma de desembolso foi analisado quanto à compatibilidade com o cronograma de execução física; proporcionalidade entre liberação de recursos e cumprimento das metas; observância da capacidade financeira do Município.</p>
<p>Verificou-se que o cronograma ( ) está adequado &nbsp; ( ) necessita ajustes &nbsp; ( ) não está adequado, atendendo aos parâmetros exigidos pela Lei nº 13.019/2014, pelo Decreto Municipal nº 048/2020 e pelas boas práticas de controle financeiro.</p>
<p>Observações: XXXXXXXX</p>
<p><strong>e) Dos meios de fiscalização e dos procedimentos de avaliação</strong></p>
<p>Em atendimento às exigências do Decreto Municipal nº 048/2020 e ao checklist do Controle Interno/TCE-MG, a Comissão registra que a execução da parceria será fiscalizada e avaliada por meio de:</p>
<p><strong>Meios de fiscalização:</strong> acompanhamento contínuo pelo Gestor da Parceria; análise dos relatórios de execução física e financeira; verificação documental das despesas realizadas; diligências e visitas técnicas in loco, quando necessário; atuação da Comissão de Monitoramento e Avaliação.</p>
<p><strong>Procedimentos de avaliação:</strong> verificação do cumprimento das metas e indicadores pactuados; análise da conformidade da execução financeira; emissão de parecer técnico conclusivo sobre os resultados alcançados; adoção de medidas corretivas, quando cabíveis.</p>
<p>Esses mecanismos asseguram controle efetivo, rastreabilidade e transparência, mitigando riscos de glosa e apontamentos pelos órgãos de controle.</p>
<p><strong>4. CONCLUSÃO DA COMISSÃO DE SELEÇÃO</strong></p>
<p>Diante da análise técnica realizada, a Comissão de Seleção manifesta-se:</p>
<p>( ) Favoravelmente à seleção da proposta<br>( ) Favoravelmente à seleção da proposta, com ressalvas<br>( ) Desfavoravelmente à seleção da proposta</p>
<p>Motivação conclusiva: XXXXXXXX</p>
<p style="text-align:right">São Gonçalo do Rio Abaixo/MG, XX de XXXX de 20XX.</p>
<p style="text-align:center">XXXXXXXXXX<br>Presidente da Comissão de Seleção</p>
<p style="text-align:center">XXXXXXXXXX<br>Membro &nbsp;&nbsp;&nbsp; XXXXXXXXXX<br>Membro</p>
HTML,
            'ata_comissao' => self::CABECALHO . <<<'HTML'
<p style="text-align:center"><strong>ATA Nº XXX/20XX DA COMISSÃO DE SELEÇÃO</strong><br>CHAMAMENTO PÚBLICO Nº XXX/20XX</p>
<p>Aos XX dias do mês de XXXX de 20XX, às XX horas, reuniram-se os membros da Comissão de Seleção designados pela Portaria nº XXX/20XX, para proceder à análise e ao julgamento das propostas apresentadas em decorrência do Chamamento Público nº XXX/20XX, cujo objeto é XXXXXXXXXX.</p>
<p>Presentes os membros abaixo relacionados:</p>
<p>XXXXXXXXXX – Presidente;<br>XXXXXXXXXX – Membro;<br>XXXXXXXXXX – Membro.</p>
<p>Iniciados os trabalhos, a Comissão verificou as propostas protocoladas dentro do prazo estabelecido no edital, conforme relação a seguir:</p>
<table><thead><tr><th>Organização da Sociedade Civil</th><th>Pontuação Obtida</th><th>Classificação</th></tr></thead><tbody><tr><td>XXXXXXXX</td><td>XXXX</td><td>XXXX</td></tr></tbody></table>
<p>Após a análise individual e colegiada das propostas, observados os critérios de julgamento, pontuação e demais requisitos previstos no edital e na Lei Federal nº 13.019/2014, a Comissão deliberou pela seguinte classificação final:</p>
<p>1º Lugar: XXXXXXXXXX<br>2º Lugar: XXXXXXXXXX<br>3º Lugar: XXXXXXXXXX</p>
<p>A Comissão declara que todas as análises foram realizadas em conformidade com os princípios da legalidade, impessoalidade, moralidade, publicidade, eficiência, isonomia e julgamento objetivo, bem como em observância às disposições constantes do edital e da Lei Federal nº 13.019/2014.</p>
<p>Fica consignado que o resultado preliminar deverá ser publicado para fins de ciência dos interessados e abertura do prazo recursal previsto no edital.</p>
<p>Nada mais havendo a tratar, foi encerrada a reunião, lavrando-se a presente ata, que após lida e aprovada, segue assinada pelos membros da Comissão de Seleção.</p>
<p style="text-align:right">São Gonçalo do Rio Abaixo/MG, XX de XXXX de 20XX.</p>
<p style="text-align:center">XXXXXXXXXX<br>Presidente – Comissão de Seleção</p>
<p style="text-align:center">XXXXXXXXXX<br>Membro – Comissão de Seleção</p>
<p style="text-align:center">XXXXXXXXXX<br>Membro – Comissão de Seleção</p>
HTML,
            'resultado_parcial' => self::CABECALHO . <<<'HTML'
<p style="text-align:center"><strong>RESULTADO PROVISÓRIO DE SELEÇÃO E CLASSIFICAÇÃO DAS PROPOSTAS APRESENTADAS PELAS ORGANIZAÇÕES DA SOCIEDADE CIVIL NO CHAMAMENTO PÚBLICO EDITAL Nº. XXX/XXXX</strong></p>
<p>A Comissão de Seleção, no uso de suas atribuições legais previstas no art. XX, do Edital de Chamamento Público XXXX/XXXX e suas alterações, bem como a Portaria nº. XXX/XXXX,</p>
<p style="text-align:center"><strong>RESOLVE:</strong></p>
<p><strong>Art. 1º</strong> Tornar público o resultado provisório de seleção e classificação das Propostas apresentadas pelas organizações da sociedade civil visando a celebração de parcerias em regime de mútua colaboração para a execução relacionados no Edital de Chamamento nº XXXX/XXXX, nos termos da tabela abaixo:</p>
<table><thead><tr><th>Organização da Sociedade Civil</th><th>CNPJ</th><th>Título do Projeto</th><th>Nota Final</th><th>Classificação</th></tr></thead><tbody><tr><td>XXXXXXXX</td><td>XXXXXXXX</td><td>XXXXXXXX</td><td>XXXX</td><td>XXXX</td></tr></tbody></table>
<p><strong>Art. 2º</strong> Nos termos da Lei Federal nº 13.019/2014 e do respectivo edital de Chamamento Público, as Organizações da Sociedade Civil participantes poderão interpor recurso administrativo contra o resultado divulgado, observando o prazo estabelecido no cronograma do certame, até XX/XX/XXXX. O recurso deverá ser protocolado eletronicamente por meio do PGP, em arquivo único no formato PDF, devidamente assinado pelo representante legal da organização ou por procurador legalmente constituído, acompanhado da documentação pertinente, quando cabível.</p>
<p><strong>Parágrafo Único</strong> - O envio após o prazo previsto no caput deste artigo torna intempestivo o recurso, impedindo sua análise e julgamento.</p>
<p style="text-align:right">São Gonçalo do Rio Abaixo, XX/XX/XXXX.</p>
<p style="text-align:center">Membro 1 &nbsp;&nbsp;&nbsp; Membro 2 &nbsp;&nbsp;&nbsp; Membro 3<br>Comissão de Seleção do Chamamento Público XXXX/XXXX<br>Portaria n. XXXX/XXXX</p>
HTML,
            'resultado_definitivo' => self::CABECALHO . <<<'HTML'
<p style="text-align:center"><strong>RESULTADO DEFINITIVO DE SELEÇÃO E CLASSIFICAÇÃO DAS PROPOSTAS APRESENTADAS PELAS ORGANIZAÇÕES DA SOCIEDADE CIVIL NO CHAMAMENTO PÚBLICO EDITAL Nº. XXX/XXXX</strong></p>
<p>A Comissão de Seleção, no uso de suas atribuições legais previstas no art. XX, do Edital de Chamamento Público XXXX/XXXX e suas alterações, bem como a Portaria nº. XXX/XXXX,</p>
<p style="text-align:center"><strong>RESOLVE:</strong></p>
<p><strong>Art. 1º</strong> Tornar público o resultado definitivo de seleção e classificação das Propostas apresentadas pelas organizações da sociedade civil visando a celebração de parcerias em regime de mútua colaboração para a execução relacionados no Edital de Chamamento nº XXXX/XXXX, nos termos da tabela abaixo:</p>
<table><thead><tr><th>Organização da Sociedade Civil</th><th>CNPJ</th><th>Título do Projeto</th><th>Nota Final</th><th>Classificação</th></tr></thead><tbody><tr><td>XXXXXXXX</td><td>XXXXXXXX</td><td>XXXXXXXX</td><td>XXXX</td><td>XXXX</td></tr></tbody></table>
<p style="text-align:right">São Gonçalo do Rio Abaixo, XX/XX/XXXX.</p>
<p style="text-align:center">Membro 1 &nbsp;&nbsp;&nbsp; Membro 2 &nbsp;&nbsp;&nbsp; Membro 3<br>Comissão de Seleção do Chamamento Público XXXX/XXXX<br>Portaria n. XXXX/XXXX</p>
HTML,
            'termo_homologacao' => self::CABECALHO . <<<'HTML'
<p style="text-align:center"><strong>TERMO DE ADJUDICAÇÃO E HOMOLOGAÇÃO</strong></p>
<p>Fica autorizada e homologada a celebração de termo de XXXXX do Chamamento Público nº. XXX/XXXX com a:</p>
<p style="text-align:center">XXXXXXXXXX<br>CNPJ nº XXXXXXXX<br>Valor: R$ XXXXXXXX</p>
<p>desde que atendidos os termos da Lei nº. 13.019/2014 e cumprido o planejamento descrito no Plano de Trabalho.</p>
<p>O plano de trabalho e o termo de XXXXXX deverão ser disponibilizados no site da Prefeitura/Parcerias MROSC, como forma de atender o art. 32, § 1º da Lei Federal nº 13.019/2014.</p>
<p>O extrato do Termo, após o cumprimento dos prazos, deverá ser publicado no Diário Oficial do Estado, para que o mesmo tenha eficácia e ser disponibilizado no site da prefeitura para consulta pública.</p>
<p style="text-align:right">São Gonçalo do Rio Abaixo, XX/XX/XXXX.</p>
<p style="text-align:center">XXXXXXXXXXXXX<br>Prefeito Municipal</p>
HTML,
        ],
        'dispensa_inexigibilidade' => [
            'justificativa' => self::CABECALHO . <<<'HTML'
<p style="text-align:center"><strong>JUSTIFICATIVA PARA INEXIGIBILIDADE OU DISPENSA</strong><br>(art. 32 da Lei nº 13.019/2014)</p>
<p style="text-align:right">São Gonçalo do Rio Abaixo, XX de XXXX de 20XX.</p>
<p><strong>ÓRGÃO RESPONSÁVEL:</strong> Secretaria Municipal de XXXXXX</p>
<p><strong>OSC:</strong> XXXXXXXXXX</p>
<p><strong>DOTAÇÃO ORÇAMENTÁRIA:</strong> XXXX &nbsp; Ficha XXXX &nbsp; Fonte XXXX</p>
<p><strong>DURAÇÃO:</strong> XX meses</p>
<p><strong>OBJETO DA PARCERIA:</strong> XXXXXXXXXX.</p>
<p><strong>1. DESCRIÇÃO DA REALIDADE OBJETO DA PARCERIA</strong></p>
<p>XXXXXXXXXX.</p>
<p><strong>2. JUSTIFICATIVA</strong></p>
<p>Considerando que a Lei Federal 13.019/2014 estabeleceu o regime jurídico das parcerias entre a Administração Pública e as Organizações da Sociedade Civil, tendo como regra geral o Chamamento Público;</p>
<p>Considerando o Decreto Municipal 048/2020, que regulamenta a Lei nº 13.019/2014 no âmbito do município;</p>
<p>Considerando que o art. 30, VI, da Lei nº 13.019/2014 prevê a dispensa de Chamamento Público no caso de atividades de educação, saúde e assistência social executadas por OSC previamente credenciadas pelo órgão gestor da respectiva política;</p>
<p>Considerando que a OSC atende aos critérios do art. 2º, I, da Lei 13.019/2014 e apresentou os documentos exigidos;</p>
<p>Diante do exposto, entendemos haver justificativa válida, idônea e de interesse público para a celebração de Termo de XXXXXX por XXXXXX de Chamamento Público, conforme art. 30, VI, da Lei nº 13.019/2014.</p>
<p style="text-align:center">XXXXXXXXXX<br>Secretária Municipal de XXXXXX<br>Unidade Gestora</p>
HTML,
            'parecer_tecnico_cnas' => self::CABECALHO . <<<'HTML'
<p style="text-align:center"><strong>PARECER TÉCNICO</strong><br>(Art. 3º, §2º, II da Resolução nº 21/2016 - CNAS)</p>
<p><strong>ÓRGÃO RESPONSÁVEL:</strong> Secretaria Municipal de XXXXXX</p>
<p><strong>OSC:</strong> XXXXXXXXXX</p>
<p><strong>OBJETO DA PARCERIA:</strong> XXXXXXXXXX.</p>
<p>O presente parecer foi elaborado observando o disposto na Resolução nº 21/2016 - CNAS, que trata dos requisitos para a dispensa de chamamento público de OSC.</p>
<p>A OSC oferece serviço nos moldes da Política Nacional de Assistência Social e da Tipificação Nacional de Serviços Socioassistenciais (Resolução CNAS nº 109/2009), enquadrando-se na proteção social XXXXXX.</p>
<p>Destaca-se que a OSC é credenciada na Secretaria Municipal de Trabalho e Desenvolvimento Social e no Conselho Municipal de Assistência Social, e que o Município não possui outros serviços socioassistenciais voltados a XXXXXXXXXX.</p>
<p>Diante do exposto, conclui-se que as atividades exercidas pela OSC não podem ser interrompidas, tendo em vista que a descontinuidade da oferta apresenta dano mais gravoso à integridade do usuário.</p>
<p style="text-align:center">XXXXXXXXXX<br>Assistente Social<br>Secretaria Municipal de XXXXXX</p>
HTML,
            'aprovacao_plano' => self::CABECALHO . <<<'HTML'
<p style="text-align:center"><strong>APROVAÇÃO DO PLANO DE TRABALHO</strong></p>
<p><strong>OSC:</strong> XXXXXXXXXX<br><strong>CNPJ:</strong> XXXXXXXX<br><strong>Objeto da Parceria:</strong> XXXXXXXXXX<br><strong>Instrumento:</strong> ( ) Termo de Fomento &nbsp; ( ) Termo de Colaboração &nbsp; ( ) Acordo de Cooperação<br><strong>Secretaria/Unidade Gestora:</strong> XXXXXXXXXX</p>
<p><strong>ANÁLISE DO PLANO DE TRABALHO</strong></p>
<table><thead><tr><th>Item</th><th>Verificação</th><th>Sim</th><th>Não</th><th>Observações</th></tr></thead><tbody>
<tr><td>1</td><td>O Plano de Trabalho foi apresentado pela OSC selecionada no Chamamento Público?</td><td>( )</td><td>( )</td><td></td></tr>
<tr><td>2</td><td>O objeto do Plano de Trabalho está em conformidade com o objeto previsto no edital e na proposta aprovada?</td><td>( )</td><td>( )</td><td></td></tr>
<tr><td>3</td><td>A descrição da realidade que fundamenta a parceria está adequada e compatível com o interesse público?</td><td>( )</td><td>( )</td><td></td></tr>
<tr><td>4</td><td>As metas e os resultados esperados estão claramente definidos?</td><td>( )</td><td>( )</td><td></td></tr>
<tr><td>5</td><td>Os indicadores de monitoramento e avaliação estão previstos e são mensuráveis?</td><td>( )</td><td>( )</td><td></td></tr>
<tr><td>6</td><td>As atividades e etapas de execução estão devidamente detalhadas?</td><td>( )</td><td>( )</td><td></td></tr>
<tr><td>7</td><td>O cronograma de execução está compatível com a vigência da parceria?</td><td>( )</td><td>( )</td><td></td></tr>
<tr><td>8</td><td>O plano de aplicação dos recursos está compatível com o objeto da parceria?</td><td>( )</td><td>( )</td><td></td></tr>
<tr><td>9</td><td>As despesas previstas guardam relação direta com a execução do objeto?</td><td>( )</td><td>( )</td><td></td></tr>
<tr><td>10</td><td>Os valores apresentados são compatíveis com os preços praticados no mercado ou devidamente justificados?</td><td>( )</td><td>( )</td><td></td></tr>
<tr><td>11</td><td>O cronograma de desembolso está adequado à execução das atividades?</td><td>( )</td><td>( )</td><td></td></tr>
<tr><td>12</td><td>O Plano de Trabalho atende aos requisitos do art. 22 da Lei Federal nº 13.019/2014?</td><td>( )</td><td>( )</td><td></td></tr>
<tr><td>13</td><td>Há disponibilidade orçamentária para a celebração da parceria?</td><td>( )</td><td>( )</td><td></td></tr>
<tr><td>14</td><td>A parceria está alinhada às políticas públicas e às competências da Unidade Gestora?</td><td>( )</td><td>( )</td><td></td></tr>
<tr><td>15</td><td>Foram observadas as exigências do edital, da legislação aplicável e das normas municipais vigentes?</td><td>( )</td><td>( )</td><td></td></tr>
</tbody></table>
<p><strong>MANIFESTAÇÃO DA UNIDADE GESTORA</strong></p>
<p>Após análise do Plano de Trabalho apresentado pela Organização da Sociedade Civil acima identificada, considerando os requisitos previstos na Lei Federal nº 13.019/2014, no edital de Chamamento Público e nas normas municipais aplicáveis, esta Unidade Gestora conclui que:</p>
<p>( ) O Plano de Trabalho encontra-se apto e aprovado para prosseguimento dos trâmites visando à celebração da parceria.<br>( ) O Plano de Trabalho necessita de ajustes/complementações, conforme observações registradas neste documento.<br>( ) O Plano de Trabalho não atende aos requisitos necessários para aprovação.</p>
<p>Justificativa da decisão: XXXXXXXX</p>
<p style="text-align:right">São Gonçalo do Rio Abaixo/MG, XX/XX/XXXX.</p>
<p style="text-align:center">XXXXXXXXXX<br>Secretário(a) Municipal da Unidade Gestora XXXXX</p>
HTML,
            'parecer_tecnico_celebracao' => self::CABECALHO . <<<'HTML'
<p style="text-align:center"><strong>PARECER TÉCNICO PARA CELEBRAÇÃO DA PARCERIA</strong></p>
<p>A Secretaria Municipal de XXXXXX, com base no que estabelece o inciso V do art. 35 da Lei 13.019/2014, referente à parceria a ser firmada entre o Município de São Gonçalo do Rio Abaixo e a OSC XXXXXXXXXX, conforme o processo nº XXXXXXXX, que tem por objeto XXXXXXXXXX, vem por meio deste parecer se pronunciar de forma expressa sobre os pontos abaixo:</p>
<p>a) Quanto ao mérito do plano de trabalho, em conformidade com a modalidade de parceria adotada: <strong>FAVORÁVEL</strong>.</p>
<p>b) Quanto à identidade e reciprocidade de interesse das partes: <strong>FAVORÁVEL</strong>.</p>
<p>c) Quanto à viabilidade de execução: <strong>FAVORÁVEL</strong>.</p>
<p>d) Quanto ao cronograma de desembolso: <strong>FAVORÁVEL</strong>.</p>
<p>e) Quanto aos meios de fiscalização e à avaliação da execução física e financeira: <strong>FAVORÁVEL</strong>.</p>
<p>f) Quanto à designação do gestor da parceria: <strong>FAVORÁVEL</strong>.</p>
<p>g) Quanto à designação da comissão de monitoramento e avaliação: <strong>FAVORÁVEL</strong>.</p>
<p>h) Quanto às condições de funcionamento da instituição (art. 17 da Lei 4.320/1964): <strong>FAVORÁVEL</strong>.</p>
<p>Com base no exposto, o parecer é de que a celebração da parceria é possível.</p>
<p style="text-align:right">São Gonçalo do Rio Abaixo, XX de XXXX de 20XX.</p>
<p style="text-align:center">XXXXXXXXXX<br>Secretária de XXXXXX<br>Unidade Gestora</p>
HTML,
            'certidao_autuacao' => self::CABECALHO . <<<'HTML'
<p style="text-align:center"><strong>CERTIDÃO DE AUTUAÇÃO</strong></p>
<p>Ao(s) XX dia(s) do mês de XXXX de 20XX, eu, XXXXXXXXXX, do Setor de Convênios e Parcerias, autuei os documentos abaixo relacionados, referentes ao processo nº XXXXXXXX (Termo de XXXXXX), por intermédio da Secretaria Municipal de XXXXXX, que me foram apresentados:</p>
<ul>
<li>Manifestação de interesse da Unidade Gestora;</li>
<li>Reserva de dotação (parecer de viabilidade orçamentária);</li>
<li>Abertura do processo;</li>
<li>Plano de trabalho e aplicação de recurso;</li>
<li>Aprovação do plano de trabalho;</li>
<li>Documentos de habilitação (certidões, declarações, estatuto e alterações registradas, ata da diretoria, relação de dirigentes, RG/CPF e comprovante de endereço do representante legal);</li>
<li>Portaria do Gestor;</li>
<li>Portaria da Comissão de Monitoramento e Avaliação;</li>
<li>Parecer da Unidade Gestora;</li>
<li>Minuta do Termo.</li>
</ul>
<p style="text-align:right">São Gonçalo do Rio Abaixo - MG, XX de XXXX de 20XX.</p>
<p style="text-align:center">XXXXXXXXXX<br>Setor de Convênios e Parcerias</p>
HTML,
            'protocolo_juridico' => self::CABECALHO . <<<'HTML'
<p style="text-align:right">São Gonçalo do Rio Abaixo, XX de XXXX de 20XX.</p>
<p><strong>A/C Procuradoria Jurídica Municipal</strong></p>
<p>Venho por meio deste solicitar parecer jurídico acerca da possibilidade de XXXXXXXXXX, referente ao processo nº XXXXXXXX, conforme estabelece a Lei Federal nº 13.019/2014.</p>
<p>Também envolve a análise da minuta do termo, que segue em anexo.</p>
<p>Sendo o que temos para o momento, desde já agradecemos.</p>
<p style="text-align:center">XXXXXXXXXX<br>Secretaria Municipal de XXXXXX<br>Unidade Gestora</p>
HTML,
            'parecer_juridico' => self::CABECALHO . <<<'HTML'
<p style="text-align:center"><strong>PARECER JURÍDICO Nº XXX/20XX</strong></p>
<p><strong>PROCESSO:</strong> XXXXXXXX</p>
<p><strong>INTERESSADO:</strong> Secretaria Municipal de XXXXXX — Unidade Gestora</p>
<p><strong>ASSUNTO:</strong> Análise jurídica da regularidade do procedimento e da minuta do termo (Lei Federal nº 13.019/2014) — Dispensa/Inexigibilidade.</p>
<p><strong>I — RELATÓRIO</strong></p>
<p>XXXXXXXXXXXX</p>
<p><strong>II — FUNDAMENTAÇÃO</strong></p>
<p>XXXXXXXXXXXX</p>
<p><strong>III — CONCLUSÃO</strong></p>
<p>Ante o exposto, esta Procuradoria opina pela XXXXXXXX (regularidade jurídica) do feito, podendo o processo prosseguir para a celebração.</p>
<p style="text-align:center">XXXXXXXXXX<br>Procurador(a) do Município<br>Procuradoria Jurídica</p>
HTML,
        ],
        'aditivo' => [
            'parecer_financeiro' => self::CABECALHO . <<<'HTML'
<p><strong>Nº</strong> XXX/20XX</p>
<p><strong>ORIGEM:</strong> Planejamento</p>
<p><strong>ASSUNTO:</strong> Dotação orçamentária e impacto financeiro (Termo Aditivo)</p>
<p><strong>DATA:</strong> XX/XX/XXXX</p>
<p>A Secretaria Municipal de Planejamento, após análise, informa à Unidade Gestora que há previsão orçamentária e financeira na Lei Orçamentária Anual para o aditamento da parceria <strong>"XXXXXXXX"</strong>.</p>
<p><strong>Previsão da Despesa:</strong></p>
<table><thead><tr><th>Ano</th><th>Secretaria Municipal</th><th>Dotação</th><th>Recurso</th><th>Ficha</th><th>Desdobrada</th><th>Valor</th></tr></thead><tbody><tr><td>XXX</td><td>XXX</td><td>XXXXX</td><td>XXXX</td><td>XXXX</td><td>XXXXX</td><td>XXXXX</td></tr></tbody></table>
<table><thead><tr><th>Valor da Receita</th><th>Despesa Prevista</th><th>Impacto</th><th>Valor Total</th></tr></thead><tbody><tr><td>XXXXX</td><td>XXXXX</td><td>XXXX</td><td>XXXXX</td></tr></tbody></table>
<p>A estimativa do Impacto Orçamentário Financeiro para realização da despesa prevista no Exercício 20XX é de XXX% das receitas orçadas na Lei Orçamentária Anual nº XXXXX.</p>
<p>Sendo só no momento, me coloco à disposição para quaisquer eventuais esclarecimentos.</p>
<p style="text-align:center">XXXXXXXXXX<br>Secretário Municipal de Planejamento</p>
HTML,
            'certidao_autuacao' => self::CABECALHO . <<<'HTML'
<p style="text-align:center"><strong>CERTIDÃO DE AUTUAÇÃO</strong></p>
<p>Ao(s) XX dia(s) do mês de XXXX de 20XX, eu, XXXXXXXXXX, do Setor de Convênios e Parcerias, autuei os documentos abaixo relacionados ao Termo Aditivo do processo nº XXXXXXXX (Termo de XXXXXX), por intermédio da Secretaria Municipal de XXXXXX, que me foram apresentados:</p>
<ul>
<li>Manifestação da OSC;</li>
<li>Justificativa Técnica da OSC;</li>
<li>Certidões de regularidade atualizadas;</li>
<li>Plano de Trabalho atualizado;</li>
<li>Aprovação da alteração do plano de trabalho;</li>
<li>Parecer financeiro;</li>
<li>Justificativa da Unidade Gestora;</li>
<li>Autorização da Unidade Gestora;</li>
<li>Minuta do Termo Aditivo.</li>
</ul>
<p style="text-align:right">São Gonçalo do Rio Abaixo - MG, XX de XXXX de 20XX.</p>
<p style="text-align:center">XXXXXXXXXX<br>Setor de Convênios e Parcerias</p>
HTML,
            'protocolo_juridico' => self::CABECALHO . <<<'HTML'
<p style="text-align:right">São Gonçalo do Rio Abaixo, XX de XXXX de 20XX.</p>
<p><strong>A/C Procuradoria Jurídica Municipal</strong></p>
<p>Venho por meio deste solicitar parecer jurídico acerca da possibilidade de aditamento, referente ao processo nº XXXXXXXX, conforme estabelece a Lei Federal nº 13.019/2014.</p>
<p>Também envolve a análise da minuta do termo aditivo, que segue em anexo.</p>
<p>Sendo o que temos para o momento, desde já agradecemos.</p>
<p style="text-align:center">XXXXXXXXXX<br>Secretaria Municipal de XXXXXX<br>Unidade Gestora</p>
HTML,
            'parecer_juridico' => self::CABECALHO . <<<'HTML'
<p style="text-align:center"><strong>PARECER JURÍDICO Nº XXX/20XX</strong></p>
<p><strong>PROCESSO:</strong> XXXXXXXX</p>
<p><strong>INTERESSADO:</strong> Secretaria Municipal de XXXXXX — Unidade Gestora</p>
<p><strong>ASSUNTO:</strong> Análise jurídica do Termo Aditivo e da minuta (Lei Federal nº 13.019/2014).</p>
<p><strong>I — RELATÓRIO</strong></p>
<p>XXXXXXXXXXXX</p>
<p><strong>II — FUNDAMENTAÇÃO</strong></p>
<p>XXXXXXXXXXXX</p>
<p><strong>III — CONCLUSÃO</strong></p>
<p>Ante o exposto, esta Procuradoria opina pela XXXXXXXX (regularidade jurídica) do aditamento, podendo o processo prosseguir para a assinatura e publicação.</p>
<p style="text-align:center">XXXXXXXXXX<br>Procurador(a) do Município<br>Procuradoria Jurídica</p>
HTML,
        ],
        // Celebração: apenas os modelos próprios desta etapa. Os demais são
        // reaproveitados de outras categorias/motores em `modeloTexto()`.
        'celebracao' => [
            'convocacao_osc' => self::CABECALHO . <<<'HTML'
<p style="text-align:right">São Gonçalo do Rio Abaixo, XX de XXXX de 20XX.</p>
<p style="text-align:center"><strong>CONVOCAÇÃO PARA APRESENTAÇÃO DE PLANO DE TRABALHO E DOCUMENTOS DE HABILITAÇÃO</strong></p>
<p><strong>OSC:</strong> XXXXXXXXXX<br><strong>CNPJ:</strong> XXXXXXXX<br><strong>Chamamento Público nº:</strong> XXX/20XX<br><strong>Processo nº:</strong> XXXXXXXX</p>
<p>Prezado(a) Representante Legal,</p>
<p>Considerando a homologação do resultado definitivo do Chamamento Público nº XXX/20XX, na qual essa Organização da Sociedade Civil foi selecionada, e nos termos dos arts. 22 e 34 da Lei Federal nº 13.019/2014 e do Decreto Municipal nº 048/2020, fica essa OSC <strong>CONVOCADA</strong> a apresentar, no prazo de XX (XXXXX) dias, por meio do PGP:</p>
<p>a) o <strong>Plano de Trabalho</strong>, contendo a descrição da realidade, as metas, os indicadores, o cronograma de execução e o plano de aplicação dos recursos, conforme art. 22 da Lei nº 13.019/2014;</p>
<p>b) os <strong>documentos de habilitação</strong> exigidos no edital e no art. 34 da Lei nº 13.019/2014 (estatuto e alterações registradas, ata da atual diretoria, certidões de regularidade fiscal e trabalhista, comprovante de endereço da sede e documentos do representante legal).</p>
<p>O não atendimento no prazo poderá acarretar a perda do direito à celebração da parceria, com a convocação da próxima OSC classificada.</p>
<p style="text-align:center">XXXXXXXXXX<br>Secretária Municipal de XXXXXX<br>Unidade Gestora</p>
HTML,
            'termo' => self::CABECALHO . <<<'HTML'
<p style="text-align:center"><strong>TERMO DE XXXXXX Nº XXX/20XX</strong></p>
<p>TERMO DE XXXXXX QUE ENTRE SI CELEBRAM O <strong>MUNICÍPIO DE SÃO GONÇALO DO RIO ABAIXO</strong>, por intermédio da Secretaria Municipal de XXXXXX, e a organização da sociedade civil <strong>XXXXXXXXXX</strong>, na forma abaixo:</p>
<p><strong>ADMINISTRAÇÃO PÚBLICA:</strong> MUNICÍPIO DE SÃO GONÇALO DO RIO ABAIXO, CNPJ nº XXXXXXXX, neste ato representado pelo(a) Secretário(a) Municipal de XXXXXX, Sr(a). XXXXXXXXXX.</p>
<p><strong>ORGANIZAÇÃO DA SOCIEDADE CIVIL:</strong> XXXXXXXXXX, CNPJ nº XXXXXXXX, com sede em XXXXXXXXXX, neste ato representada por seu(sua) representante legal, Sr(a). XXXXXXXXXX, CPF nº XXXXXXXX.</p>
<p><strong>CLÁUSULA PRIMEIRA — DO OBJETO</strong></p>
<p>1.1. O presente Termo tem por objeto XXXXXXXXXX, conforme o Plano de Trabalho aprovado, que integra este instrumento independentemente de transcrição.</p>
<p><strong>CLÁUSULA SEGUNDA — DAS OBRIGAÇÕES DAS PARTES</strong></p>
<p>2.1. As partes obrigam-se ao cumprimento da Lei Federal nº 13.019/2014, do Decreto Municipal nº 048/2020 e das condições estabelecidas neste Termo e no Plano de Trabalho.</p>
<p><strong>CLÁUSULA TERCEIRA — DOS RECURSOS FINANCEIROS</strong></p>
<p>3.1. Para a execução do objeto será repassado o valor total de R$ XXXXXXXX (XXXXXXXX), à conta da dotação orçamentária XXXXX, Ficha XXXX, Fonte XXXX.</p>
<p>3.2. Os repasses observarão o cronograma de desembolso do Plano de Trabalho.</p>
<p><strong>CLÁUSULA QUARTA — DA MOVIMENTAÇÃO DOS RECURSOS</strong></p>
<p>4.1. Os recursos serão depositados e movimentados em conta bancária específica desta parceria, isenta de tarifas.</p>
<p><strong>CLÁUSULA QUINTA — DA VIGÊNCIA</strong></p>
<p>5.1. O presente Termo vigorará de XX/XX/XXXX a XX/XX/XXXX, podendo ser prorrogado nos termos da lei.</p>
<p><strong>CLÁUSULA SEXTA — DO GESTOR E DO MONITORAMENTO</strong></p>
<p>6.1. A execução será acompanhada pelo Gestor da Parceria designado por portaria e pela Comissão de Monitoramento e Avaliação.</p>
<p><strong>CLÁUSULA SÉTIMA — DA PRESTAÇÃO DE CONTAS</strong></p>
<p>7.1. A OSC prestará contas na forma dos arts. 63 a 72 da Lei nº 13.019/2014.</p>
<p><strong>CLÁUSULA OITAVA — DA DENÚNCIA E DA RESCISÃO</strong></p>
<p>8.1. O presente Termo poderá ser denunciado ou rescindido na forma do art. 42, XVI, da Lei nº 13.019/2014.</p>
<p><strong>CLÁUSULA NONA — DA PUBLICIDADE</strong></p>
<p>9.1. O extrato deste Termo será publicado no Diário Oficial e disponibilizado no site oficial do Município.</p>
<p><strong>CLÁUSULA DÉCIMA — DO FORO</strong></p>
<p>10.1. Fica eleito o foro da Comarca de XXXXXXXX para dirimir as questões oriundas deste Termo.</p>
<p style="text-align:right">São Gonçalo do Rio Abaixo, XX de XXXX de 20XX.</p>
<p style="text-align:center">XXXXXXXXXX<br>Secretária Municipal de XXXXXX<br>Administração Pública Municipal</p>
<p style="text-align:center">XXXXXXXXXX<br>Representante Legal<br>Organização da Sociedade Civil</p>
HTML,
            'parecer_scp' => self::CABECALHO . <<<'HTML'
<p style="text-align:center"><strong>PARECER DO SETOR DE CONVÊNIOS E PARCERIAS Nº XXX/20XX</strong><br>(conferência final do processo de celebração)</p>
<p><strong>PROCESSO:</strong> XXXXXXXX<br><strong>OSC:</strong> XXXXXXXXXX &nbsp; <strong>CNPJ:</strong> XXXXXXXX<br><strong>UNIDADE GESTORA:</strong> Secretaria Municipal de XXXXXX<br><strong>OBJETO:</strong> XXXXXXXXXX</p>
<p><strong>I — DA CONFERÊNCIA</strong></p>
<p>O Setor de Convênios e Parcerias procedeu à conferência final do processo, verificando a presença e a regularidade formal das peças abaixo, na forma da Lei Federal nº 13.019/2014 e do Decreto Municipal nº 048/2020:</p>
<table><thead><tr><th>Item</th><th>Peça</th><th>Conforme</th></tr></thead><tbody>
<tr><td>1</td><td>Plano de Trabalho apresentado pela OSC</td><td>( ) Sim ( ) Não</td></tr>
<tr><td>2</td><td>Documentos de habilitação (art. 34 da Lei nº 13.019/2014)</td><td>( ) Sim ( ) Não</td></tr>
<tr><td>3</td><td>Aprovação do Plano de Trabalho pela Unidade Gestora</td><td>( ) Sim ( ) Não</td></tr>
<tr><td>4</td><td>Parecer Financeiro da SEPLAN (dotação e impacto)</td><td>( ) Sim ( ) Não</td></tr>
<tr><td>5</td><td>Portaria do Gestor da Parceria</td><td>( ) Sim ( ) Não</td></tr>
<tr><td>6</td><td>Portaria da Comissão de Monitoramento e Avaliação</td><td>( ) Sim ( ) Não</td></tr>
<tr><td>7</td><td>Parecer Técnico da Unidade Gestora (art. 35, V)</td><td>( ) Sim ( ) Não</td></tr>
<tr><td>8</td><td>Parecer Jurídico da Procuradoria (art. 35, VI)</td><td>( ) Sim ( ) Não</td></tr>
<tr><td>9</td><td>Minuta do Termo compatível com o Plano de Trabalho</td><td>( ) Sim ( ) Não</td></tr>
</tbody></table>
<p><strong>II — DAS RESSALVAS</strong></p>
<p>XXXXXXXX</p>
<p><strong>III — CONCLUSÃO</strong></p>
<p>Ante o exposto, este Setor manifesta-se:</p>
<p>( ) <strong>Favoravelmente</strong> à celebração da parceria, estando o processo apto à assinatura das partes;<br>( ) <strong>Favoravelmente com ressalvas</strong>, devendo as observações do item II ser sanadas;<br>( ) <strong>Pela devolução</strong> do processo à origem, para saneamento das pendências apontadas.</p>
<p style="text-align:right">São Gonçalo do Rio Abaixo, XX de XXXX de 20XX.</p>
<p style="text-align:center">XXXXXXXXXX<br>Setor de Convênios e Parcerias (SCP)</p>
HTML,
            'autorizacao_inicio' => self::CABECALHO . <<<'HTML'
<p style="text-align:right">São Gonçalo do Rio Abaixo, XX de XXXX de 20XX.</p>
<p style="text-align:center"><strong>AUTORIZAÇÃO DE INÍCIO DE EXECUÇÃO</strong></p>
<p><strong>OSC:</strong> XXXXXXXXXX<br><strong>CNPJ:</strong> XXXXXXXX<br><strong>Termo de XXXXXX nº:</strong> XXX/20XX<br><strong>Processo nº:</strong> XXXXXXXX</p>
<p>Prezado(a) Representante Legal,</p>
<p>Comunicamos que, cumpridas as exigências legais e publicado o extrato do Termo de XXXXXX nº XXX/20XX no Diário Oficial e no site oficial do Município, fica essa Organização da Sociedade Civil <strong>AUTORIZADA a iniciar a execução</strong> do objeto pactuado a partir de XX/XX/XXXX, observado o Plano de Trabalho aprovado.</p>
<p>Solicitamos, ainda, a informação dos <strong>dados bancários</strong> da <strong>conta específica</strong> desta parceria (banco, agência, operação e conta corrente), a ser aberta exclusivamente para a movimentação dos recursos, conforme art. 51 da Lei Federal nº 13.019/2014, por meio do PGP.</p>
<p>Registre-se que as despesas somente poderão ser realizadas a partir da presente autorização e dentro da vigência da parceria.</p>
<p style="text-align:center">XXXXXXXXXX<br>Setor de Convênios e Parcerias (SCP)</p>
HTML,
        ],
    ];

    /**
     * Texto-modelo da peça. A Celebração reaproveita os modelos equivalentes de
     * outras categorias e motores (a rota Dispensa cobre os mesmos documentos),
     * em vez de duplicar o texto.
     */
    public static function modeloTexto(string $categoria, string $chave): ?string
    {
        if (isset(self::MODELO[$categoria][$chave])) {
            return self::MODELO[$categoria][$chave];
        }

        if ($categoria !== 'celebracao') {
            return null;
        }

        // Documentos idênticos aos da rota Dispensa/Inexigibilidade.
        $daDispensa = [
            'aprovacao_plano'    => 'aprovacao_plano',
            'parecer_tecnico'    => 'parecer_tecnico_celebracao',
            'protocolo_juridico' => 'protocolo_juridico',
            'parecer_juridico'   => 'parecer_juridico',
        ];
        if (isset($daDispensa[$chave])) {
            return self::MODELO['dispensa_inexigibilidade'][$daDispensa[$chave]] ?? null;
        }

        // Documentos que o trâmite do Processo já modela.
        if (in_array($chave, ['pedido_parecer', 'parecer_financeiro'], true)) {
            return ProcessoPeca::MODELO[$chave] ?? null;
        }

        // A Ordem de Pagamento Global tem o seu próprio ofício-modelo.
        if ($chave === 'op_global') {
            return OrdemPagamento::MODELO_GLOBAL;
        }

        return null;
    }

    public function pecaable(): MorphTo
    {
        return $this->morphTo();
    }

    public function assinante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assinado_por');
    }

    public function assinado(): bool
    {
        return !is_null($this->assinado_em);
    }

    public function contraAssinante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contra_assinado_por');
    }

    public function contraAssinado(): bool
    {
        return !is_null($this->contra_assinado_em);
    }

    /** A peça exige assinatura das partes (Município + OSC)? */
    public function exigeContraAssinatura(): bool
    {
        return $this->categoria === 'celebracao'
            && isset(self::CELEBRACAO_CONTRA_ASSINATURA[$this->chave]);
    }

    /**
     * Pode contra-assinar agora? Exige a assinatura do Município já lançada e,
     * como é a vez da OSC, que seja a OSC daquela parceria.
     *
     * A comparação de setor é por `setorNoTramite()`, como em podePreencher() e
     * podeAssinar(). Era o único ponto do motor que lia `$user->setor` direto:
     * a OSC não tem lotação (users.setor é NULL), então `NULL !== 'osc'` era
     * sempre verdadeiro e o botão de contra-assinar nunca aparecia para
     * ninguém — o Termo ficava eternamente "aguardando a contra-assinatura da
     * OSC", travando a etapa 10 da Celebração.
     *
     * E quem assina pela OSC é o responsável legal: o próprio Termo diz
     * "representada por seu(sua) representante legal". A equipe prepara os
     * documentos; o ato que vincula a entidade é de uma pessoa só — a mesma
     * régua de submeter proposta e interpor recurso.
     */
    public function podeContraAssinar(?User $user): bool
    {
        if (!$this->exigeContraAssinatura() || !$this->assinado() || $this->contraAssinado()) {
            return false;
        }

        $dono = $this->donoEmTramite();
        if (!$dono || $dono->tramiteEncerrado()) {
            return false;
        }

        $regra = self::CELEBRACAO_CONTRA_ASSINATURA[$this->chave];

        if (!$user
            || $user->setorNoTramite() !== $regra['setor']
            || $dono->tramiteEtapaAtual() !== $regra['etapa']
        ) {
            return false;
        }

        return $regra['setor'] !== 'osc'
            || ($this->oscDona($user, $dono) && $user->ehResponsavelLegalOsc());
    }

    /**
     * Por que não dá para contra-assinar agora — em português, com os fatos.
     * Null quando está liberado. Mesmo princípio de motivoNaoPodePreencher():
     * em vez de sumir com o botão, a tela diz de quem é a vez e o que falta.
     */
    public function motivoNaoPodeContraAssinar(?User $user): ?string
    {
        if (!$this->exigeContraAssinatura() || $this->contraAssinado() || $this->podeContraAssinar($user)) {
            return null;
        }

        if (!$this->assinado()) {
            return 'O Município ainda não assinou este documento.';
        }

        $dono  = $this->donoEmTramite();
        $regra = self::CELEBRACAO_CONTRA_ASSINATURA[$this->chave];

        if (!$dono || $dono->tramiteEncerrado()) {
            return 'O trâmite já foi concluído — os documentos ficam apenas para consulta.';
        }

        if (!$user || $user->setorNoTramite() !== $regra['setor']) {
            return 'A assinatura das partes é da OSC parceira.';
        }

        if (!$this->oscDona($user, $dono)) {
            return 'Este documento pertence a outra OSC.';
        }

        if (!$user->ehResponsavelLegalOsc()) {
            return 'Somente o responsável legal da OSC pode assinar o Termo.';
        }

        $etapaDoc   = $regra['etapa'] + 1;
        $etapaAtual = $dono->tramiteEtapaAtual() + 1;

        return $etapaDoc > $etapaAtual
            ? "Ainda não é a vez da assinatura das partes: ela ocorre na etapa {$etapaDoc}, "
                ."e o trâmite está na etapa {$etapaAtual}."
            : "A etapa da assinatura das partes (etapa {$etapaDoc}) já passou — "
                ."o trâmite está na etapa {$etapaAtual}.";
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

    public function temArquivo(): bool
    {
        return !is_null($this->arquivo_path);
    }

    /** Item de arquivo que aceita ser "puxado" do módulo Gestão de Parcerias. */
    public function puxavel(): bool
    {
        return $this->tipo === 'arquivo'
            && in_array($this->chave, self::PUXAVEIS[$this->categoria] ?? [], true);
    }

    /**
     * Documentos da OSC disponíveis para puxar, conforme o registro dono da peça:
     * Seleção (Chamamento) → documentos das propostas do chamamento;
     * Aditivo/Apostilamento → documentos da proposta do instrumento.
     */
    public function documentosDisponiveis()
    {
        $alvo = $this->pecaable;

        $propostaIds = match (true) {
            $alvo instanceof Chamamento => $alvo->propostas()->pluck('id'),
            $alvo instanceof Aditivo    => collect(array_filter([$alvo->instrumento?->proposta_id])),
            default                     => collect(),
        };

        if ($propostaIds->isEmpty()) {
            return collect();
        }

        return Documento::with('proposta.osc')
            ->whereIn('proposta_id', $propostaIds)
            ->latest()
            ->get();
    }

    public function preenchido(): bool
    {
        return $this->tipo === 'modelo' ? !empty($this->conteudo) : $this->temArquivo();
    }

    /**
     * A peça está pronta? Depende do tipo — e é isso que a tela confundia.
     *
     * Modelo é texto que alguém assina; arquivo é documento que já vem assinado
     * (ou publicado) de fora, e o sistema não o assina nunca — podeAssinar()
     * exige tipo 'modelo'. Ainda assim o checklist media todas as peças por
     * assinado(), então todo anexo ficava para sempre em "Preenchido — falta
     * assinar", cobrando uma ação que não existe e para a qual não há botão.
     *
     * O avanço do trâmite (Chamamento::pendenciasDaEtapa) sempre soube da
     * diferença; quem não sabia era a exibição.
     */
    public function concluida(): bool
    {
        return $this->tipo === 'modelo' ? $this->assinado() : $this->preenchido();
    }

    // ------------------------------------------------------------------
    // Trâmite da Seleção — quem pode preencher/assinar e quando
    // ------------------------------------------------------------------

    /**
     * O dono desta peça está em trâmite (Seleção, no Chamamento Público, ou
     * Celebração, na Proposta aprovada)? Fora desses casos (Dispensa, Aditivo,
     * Apostilamento) não há trâmite e as regras antigas valem — quem tem a
     * permissão da tela edita.
     */
    private function donoEmTramite(): Chamamento|Proposta|null
    {
        $alvo = $this->pecaable;

        return match (true) {
            $this->categoria === 'chamamento_publico'
                && $alvo instanceof Chamamento && $alvo->temTramiteSelecao() => $alvo,
            $this->categoria === 'celebracao' && $alvo instanceof Proposta => $alvo,
            default => null,
        };
    }

    /** Mapas de designação conforme a categoria em trâmite. */
    private function mapaSetor(): array
    {
        return $this->categoria === 'celebracao' ? self::CELEBRACAO_SETOR : self::SELECAO_SETOR;
    }

    private function mapaEtapa(): array
    {
        return $this->categoria === 'celebracao' ? self::CELEBRACAO_ETAPA : self::SELECAO_ETAPA;
    }

    private function mapaAssinatura(): array
    {
        return $this->categoria === 'celebracao' ? self::CELEBRACAO_ASSINATURA : self::SELECAO_ASSINATURA;
    }

    /** Setor designado para preencher a peça no trâmite. */
    public function selecaoSetor(): ?string
    {
        return $this->mapaSetor()[$this->chave] ?? null;
    }

    public function selecaoEtapa(): ?int
    {
        return $this->mapaEtapa()[$this->chave] ?? null;
    }

    /**
     * Setor dono da peça fora do trâmite (fase do edital) — null quando a peça
     * não é dessas ou a categoria não é chamamento público (dispensa e
     * inexigibilidade seguem sem designação, como sempre estiveram).
     */
    public function setorPrevio(): ?string
    {
        return $this->categoria === 'chamamento_publico'
            ? (self::SELECAO_SETOR_PREVIO[$this->chave] ?? null)
            : null;
    }

    /** Fora do trâmite: sem etapa, só o setor decide. */
    private function podeAgirNaFasePrevia(?User $user): bool
    {
        $setor = $this->setorPrevio();

        return $setor === null || $user?->setorNoTramite() === $setor;
    }

    public function selecaoSetorAssinatura(): ?string
    {
        return $this->mapaAssinatura()[$this->chave]['setor'] ?? $this->selecaoSetor();
    }

    public function selecaoEtapaAssinatura(): ?int
    {
        return $this->mapaAssinatura()[$this->chave]['etapa'] ?? $this->selecaoEtapa();
    }

    /**
     * Pode preencher (texto ou upload) agora? Só o setor designado, na etapa
     * designada, enquanto a Seleção não estiver encerrada.
     */
    /** A peça é governada por um trâmite (Seleção ou Celebração)? */
    public function emTramite(): bool
    {
        return $this->donoEmTramite() !== null && $this->selecaoSetor() !== null;
    }

    /**
     * Etapa da PRÓXIMA ação pendente desta peça — que nem sempre é a etapa em
     * que ela é preenchida.
     *
     * O Termo de Adjudicação e Homologação é o caso: a SCP o emite na etapa 4 e
     * o Prefeito o assina na etapa 5. Agrupado pela etapa de preenchimento, ele
     * caía no bloco da SCP; com o trâmite já na etapa 5, o Prefeito abria a tela
     * e via TODOS os blocos como "etapa vencida", sem nada marcado como dele —
     * justamente a assinatura que ele precisa dar.
     */
    public function etapaDaProximaAcao(): ?int
    {
        if ($this->preenchido() && !$this->assinado()) {
            return $this->selecaoEtapaAssinatura();
        }

        // Assinado pelo Município e à espera da OSC: a ação pendente é a
        // contra-assinatura, na etapa dela. Sem isto o Termo ficava no bloco de
        // quem já assinou — "etapa vencida" — enquanto a OSC, na etapa seguinte,
        // não via nada marcado como seu. Mesmo defeito que a assinatura do
        // Prefeito tinha na Seleção.
        if ($this->contraAssinaturaPendente()) {
            return self::CELEBRACAO_CONTRA_ASSINATURA[$this->chave]['etapa'];
        }

        return $this->selecaoEtapa();
    }

    /** Setor da próxima ação pendente — ver etapaDaProximaAcao(). */
    public function setorDaProximaAcao(): ?string
    {
        if ($this->preenchido() && !$this->assinado()) {
            return $this->selecaoSetorAssinatura();
        }

        if ($this->contraAssinaturaPendente()) {
            return self::CELEBRACAO_CONTRA_ASSINATURA[$this->chave]['setor'];
        }

        return $this->selecaoSetor();
    }

    /** Assinado pela Administração e ainda esperando a assinatura das partes. */
    public function contraAssinaturaPendente(): bool
    {
        return $this->exigeContraAssinatura() && $this->assinado() && !$this->contraAssinado();
    }

    /**
     * Em que etapa o trâmite dono desta peça está AGORA — null quando a peça
     * não é governada por trâmite nenhum.
     *
     * O checklist precisa disto para separar o que é a vez de agora do que só
     * chega depois: sem esse número, a lista sabe a etapa de cada documento mas
     * não sabe onde o processo está, e não tem como ordenar nada.
     */
    public function etapaAtualDoTramite(): ?int
    {
        return $this->donoEmTramite()?->tramiteEtapaAtual();
    }

    public function tramiteJaEncerrado(): bool
    {
        return (bool) $this->donoEmTramite()?->tramiteEncerrado();
    }

    /**
     * As etapas do trâmite dono, na ordem — vazio fora de trâmite.
     *
     * O checklist agrupa as peças por etapa, e só desenhava os blocos que
     * tinham documento: quando a única peça de uma etapa migrava para a etapa
     * da assinatura, o bloco sumia e a numeração pulava (12 → 14). Com a lista
     * completa do fluxo, a tela desenha todas as etapas, na mesma sequência da
     * trilha do trâmite.
     */
    public function etapasDoTramite(): array
    {
        return $this->donoEmTramite()?->tramiteEtapas() ?? [];
    }

    /** Nome por extenso de um setor, pelo mapa do trâmite dono. */
    public function rotuloDoSetor(?string $setor): string
    {
        return $this->donoEmTramite()?->tramiteSetorLabel($setor) ?? strtoupper((string) $setor);
    }

    /**
     * Quando a vez é da OSC, ela só atua nas peças da própria parceria.
     */
    private function oscDona(?User $user, Chamamento|Proposta|null $dono): bool
    {
        return $user?->ehRepresentanteOsc()
            && $dono instanceof Proposta
            && $user->osc->id === $dono->osc_id;
    }

    public function podePreencher(?User $user): bool
    {
        $dono = $this->donoEmTramite();

        // Fora do trâmite (fase do edital): sem etapa, mas com dono.
        if (!$dono || $this->selecaoSetor() === null) {
            return $this->podeAgirNaFasePrevia($user);
        }

        if ($dono->tramiteEncerrado() || $this->assinado()) {
            return false;
        }

        if (!$user
            || $user->setorNoTramite() !== $this->selecaoSetor()
            || $dono->tramiteEtapaAtual() !== $this->selecaoEtapa()
        ) {
            return false;
        }

        return $this->selecaoSetor() !== 'osc' || $this->oscDona($user, $dono);
    }

    /**
     * Por que não dá para preencher agora — em português, com os fatos.
     *
     * O checklist mostrava o documento num bloco cinza e mais nada: nem quem é
     * o responsável, nem em que etapa o trâmite está, nem o que falta. Quem
     * abria a peça não tinha como saber se era falta de permissão, se a vez era
     * de outro setor ou se a etapa ainda não havia chegado.
     *
     * Retorna null quando o preenchimento está liberado.
     */
    public function motivoNaoPodePreencher(?User $user): ?string
    {
        if ($this->podePreencher($user)) {
            return null;
        }

        $dono = $this->donoEmTramite();
        // Peça da fase do edital não tem trâmite de onde tirar o rótulo — cai
        // na lotação, senão a frase sairia com a sigla crua ("o setor pj").
        $rotulo = fn (?string $s) => $dono?->tramiteSetorLabel($s)
            ?? (User::LOTACOES[$s] ?? strtoupper((string) $s));

        if (!$user) {
            return 'Entre no sistema para preencher este documento.';
        }

        if ($this->assinado()) {
            return 'Este documento já foi assinado e não pode mais ser alterado.';
        }

        if ($dono?->tramiteEncerrado()) {
            return 'O trâmite já foi concluído — os documentos ficam apenas para consulta.';
        }

        // Fora do trâmite, quem manda é o setor prévio (fase do edital).
        $setorDaPeca = $this->selecaoSetor() ?? $this->setorPrevio();

        if ($user->setorNoTramite() !== $setorDaPeca) {
            // A OSC vê o próprio setor como 'osc', mas não é lotação de servidor.
            return $setorDaPeca === 'osc'
                ? 'Este documento é preenchido pela OSC parceira.'
                : 'Este documento é preenchido pelo setor '.$rotulo($setorDaPeca)
                    .', e o seu é '.$user->setorLabel().'.';
        }

        // Setor certo, mas a OSC não é a dona desta parceria.
        if ($setorDaPeca === 'osc' && !$this->oscDona($user, $dono)) {
            return 'Este documento pertence a outra OSC.';
        }

        // Peça fora do trâmite: não há etapa a explicar.
        if ($this->selecaoEtapa() === null) {
            return 'Este documento já foi assinado e não pode mais ser alterado.';
        }

        // Preenchido, à espera de assinatura de OUTRA etapa (a Ordem de
        // Pagamento Global: a SCP elabora, a UG assina). Dizer que "a etapa
        // deste documento já passou" era desnorteante — nada passou, o
        // documento está exatamente onde deveria, esperando quem assina.
        if ($this->preenchido()
            && !$this->assinado()
            && $this->selecaoEtapaAssinatura() !== $this->selecaoEtapa()
        ) {
            return 'Este documento já foi preenchido — falta a assinatura de '
                . $rotulo($this->selecaoSetorAssinatura())
                . ', na etapa ' . ($this->selecaoEtapaAssinatura() + 1) . '.';
        }

        // Setor certo — só não chegou a vez.
        $etapaDoc   = $this->selecaoEtapa() + 1;
        $etapaAtual = ($dono?->tramiteEtapaAtual() ?? 0) + 1;

        return $etapaDoc > $etapaAtual
            ? "Ainda não é a vez deste documento: ele é preenchido na etapa {$etapaDoc} do trâmite, "
                ."que está na etapa {$etapaAtual}."
            : "A etapa deste documento (etapa {$etapaDoc}) já passou — o trâmite está na etapa {$etapaAtual}.";
    }

    /**
     * Pode ver o conteúdo/arquivo da peça: quem atua no sistema, ou a OSC nas
     * peças da própria parceria.
     */
    public function podeVer(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->can('chamamentos') || $user->can('formalizacao')) {
            return true;
        }

        return $this->oscDona($user, $this->donoEmTramite());
    }

    /**
     * Pode assinar agora? Mesma regra, porém pelo setor/etapa de assinatura —
     * é o que reserva o Termo de Adjudicação e Homologação ao Prefeito.
     */
    public function podeAssinar(?User $user): bool
    {
        if ($this->tipo !== 'modelo' || empty($this->conteudo) || $this->assinado()) {
            return false;
        }

        $dono = $this->donoEmTramite();

        if (!$dono || $this->selecaoSetor() === null) {
            return $this->podeAgirNaFasePrevia($user);
        }

        if ($dono->tramiteEncerrado()) {
            return false;
        }

        if (!$user
            || $user->setorNoTramite() !== $this->selecaoSetorAssinatura()
            || $dono->tramiteEtapaAtual() !== $this->selecaoEtapaAssinatura()
        ) {
            return false;
        }

        return $this->selecaoSetorAssinatura() !== 'osc' || $this->oscDona($user, $dono);
    }

    /**
     * Explicação curta de por que a peça está travada (para a interface).
     * Devolve null quando o usuário pode atuar nela agora.
     */
    public function motivoTrava(?User $user = null): ?string
    {
        $dono = $this->donoEmTramite();

        if (!$dono || $this->selecaoSetor() === null || $this->assinado()) {
            return null;
        }

        // Quem pode preencher ou assinar agora não vê trava.
        if ($this->podePreencher($user) || $this->podeAssinar($user)) {
            return null;
        }

        if ($dono->tramiteEncerrado()) {
            return $this->categoria === 'celebracao' ? 'Celebração concluída.' : 'Seleção encerrada.';
        }

        // Etapa e setor da MESMA ação pendente. Antes o número vinha do
        // preenchimento e o setor, da assinatura: no bloco da Ordem de
        // Pagamento lia-se "Disponível na etapa 13 do trâmite (Unidade
        // Gestora)" — a etapa é da SCP, que elabora; a UG só assina, na 14.
        $etapa = $this->etapaDaProximaAcao();
        $setor = $dono->tramiteSetorLabel($this->setorDaProximaAcao());

        if ($etapa !== null && $dono->tramiteEtapaAtual() !== $etapa) {
            return 'Disponível na etapa ' . ($etapa + 1) . ' do trâmite (' . $setor . ').';
        }

        return 'Ação do setor responsável: ' . $setor . '.';
    }

    public function tamanhoFormatado(): string
    {
        if (!$this->tamanho) return '—';
        $kb = $this->tamanho / 1024;
        return $kb > 1024 ? number_format($kb / 1024, 1) . ' MB' : number_format($kb, 0) . ' KB';
    }

    /**
     * Cria (idempotente) as peças de uma categoria para um registro a partir do
     * template. $relacao permite apontar para uma relação morphMany diferente de
     * `pecas()`, caso algum dono use `pecas()` para outra finalidade.
     */
    public static function sincronizar(Model $pecaable, string $categoria, string $relacao = 'pecas'): void
    {
        $template = self::TEMPLATES[$categoria] ?? [];
        $tokens   = self::tokensDe($pecaable);

        foreach ($template as $i => $item) {
            $novos = [
                'rotulo'      => $item['rotulo'],
                'tipo'        => $item['tipo'],
                'obrigatorio' => $item['obrigatorio'] ?? true,
                'ordem'       => $i,
            ];

            // semeia o texto-modelo das peças "modelo" que possuem template
            $bruto = ($item['tipo'] ?? null) === 'modelo'
                ? self::modeloTexto($categoria, $item['chave'])
                : null;
            $texto = $bruto === null ? null : \App\Support\Modelo::preencher($bruto, $tokens);

            if ($texto !== null) {
                $novos['conteudo'] = $texto;
            }

            $peca = $pecaable->{$relacao}()->firstOrCreate(
                ['categoria' => $categoria, 'chave' => $item['chave']],
                $novos
            );

            // Peça semeada antes desta correção: guardou o modelo cru, com os
            // {{marcadores}} à mostra. Se ninguém mexeu nela (conteúdo idêntico
            // ao modelo) e ela não está assinada, recebe o texto preenchido.
            if ($texto !== null
                && !$peca->wasRecentlyCreated
                && !$peca->assinado()
                && $peca->conteudo === $bruto
            ) {
                $peca->update(['conteudo' => $texto]);
            }
        }
    }

    /**
     * Dados que o sistema já conhece, para entrar no lugar dos {{marcadores}}
     * dos modelos padrão.
     *
     * Os modelos emprestados de outros módulos (o ofício da Ordem de Pagamento
     * Global, o pedido de parecer e o parecer financeiro) trazem marcadores; a
     * semeadura das peças gravava o texto cru e eles chegavam à tela como
     * "{{favorecido}}", "{{ano}}" — ProcessoPeca e OrdemPagamento já preenchiam
     * os seus, só o motor de peças não.
     *
     * O que o sistema não tem como saber (o número do ofício, quem assina)
     * recebe o mesmo "XXXXX" que o resto do modelo usa para o que se digita —
     * apagar o marcador deixaria a frase truncada ("parceria com a , Termo").
     */
    private static function tokensDe(Model $pecaable): array
    {
        $osc = $instrumento = $orgao = $processo = null;

        if ($pecaable instanceof Proposta) {
            $osc         = $pecaable->osc?->name;
            $instrumento = $pecaable->instrumento?->numero;
            $orgao       = $pecaable->chamamento?->programa?->orgao?->name;
            $processo    = $pecaable->chamamento?->processo?->numero;
        } elseif ($pecaable instanceof Chamamento) {
            $orgao    = $pecaable->programa?->orgao?->name;
            $processo = $pecaable->processo?->numero;
        } elseif ($pecaable instanceof Aditivo) {
            $proposta    = $pecaable->instrumento?->proposta;
            $osc         = $proposta?->osc?->name;
            $instrumento = $pecaable->instrumento?->numero;
            $orgao       = $proposta?->chamamento?->programa?->orgao?->name;
        }

        $tokens = [
            'favorecido'      => $osc,
            'instrumento'     => $instrumento,
            'unidade_gestora' => $orgao,
            'numero_processo' => $processo,
            'cidade'          => 'São Gonçalo do Rio Abaixo',
            'data'            => now()->format('d/m/Y'),
            'ano'             => now()->year,
            // Sem dado no sistema: o número do ofício e o nome de quem assina
            // são preenchidos por quem redige.
            'op_numero'        => null,
            'responsavel_nome' => null,
        ];

        return array_map(fn ($v) => filled($v) ? $v : 'XXXXX', $tokens);
    }

    /**
     * Progresso (peças obrigatórias preenchidas / total obrigatórias).
     */
    public static function progresso($pecas): array
    {
        $obrig = $pecas->where('obrigatorio', true);
        $total = $obrig->count();
        $ok = $obrig->filter(fn ($p) => $p->preenchido())->count();

        return [
            'ok'      => $ok,
            'total'   => $total,
            'percent' => $total ? (int) round($ok / $total * 100) : 100,
        ];
    }
}
