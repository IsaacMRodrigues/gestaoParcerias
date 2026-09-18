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
        'extra', 'setor', 'etapa', 'criado_por', 'origem_processo_peca_id',
        'conteudo', 'arquivo_path', 'arquivo_nome', 'tamanho', 'mime_type',
        'assinado_por', 'assinado_em', 'codigo_validacao',
        'contra_assinado_por', 'contra_assinado_em', 'codigo_validacao_contra',
    ];

    protected function casts(): array
    {
        return [
            'obrigatorio'        => 'boolean',
            'extra'              => 'boolean',
            'etapa'              => 'integer',
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
            ['chave' => 'plano_trabalho',            'rotulo' => 'Plano de trabalho',                       'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'aprovacao_plano',           'rotulo' => 'Aprovação do plano de trabalho',          'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'docs_habilitacao',          'rotulo' => 'Documentos de habilitação',               'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'verificacao_habilitacao',   'rotulo' => 'Verificação da habilitação (checklist)',  'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'gestor_parceria',           'rotulo' => 'Gestor da parceria',                      'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'comissao_monitoramento',    'rotulo' => 'Comissão de Monitoramento e Avaliação',   'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'parecer_tecnico_celebracao','rotulo' => 'Parecer técnico para celebração',         'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'minuta_termo',              'rotulo' => 'Minuta do termo (modelo padrão)',         'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'certidao_autuacao',         'rotulo' => 'Certidão de autuação (modelo padrão)',    'tipo' => 'modelo',  'obrigatorio' => true],
            // Instrução do pedido de parecer: as vias que seguem à Procuradoria
            // junto com o Protocolo. A publicação do extrato veio do topo da
            // lista — ela é emitida antes, mas é aqui que precisa estar à mão.
            ['chave' => 'pub_extrato',               'rotulo' => 'Publicação do extrato da justificativa',  'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'minuta_termo_anexo',        'rotulo' => 'Minuta do termo (arquivo)',               'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'certidao_autuacao_anexo',   'rotulo' => 'Certidão de autuação (arquivo)',          'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'protocolo_juridico',        'rotulo' => 'Protocolo na Unidade Jurídica',           'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'parecer_juridico',          'rotulo' => 'Parecer jurídico',                        'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'termo',                     'rotulo' => 'Termo',                                   'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'pub_extrato_final',         'rotulo' => 'Publicação do extrato do termo',          'tipo' => 'arquivo', 'obrigatorio' => true],
        ],

        // Celebração (Fluxo Etapa de Celebração) — ancorada na proposta aprovada
        'celebracao' => [
            ['chave' => 'convocacao_osc',        'rotulo' => 'Convocação da OSC (modelo padrão)',                      'tipo' => 'modelo',  'obrigatorio' => true],
            // Item 1 do checklist: "a partir do preenchido". O plano não é um
            // arquivo à parte — é o que a OSC lançou no Portal, impresso para
            // assinar, de modo que o documento e o dado não possam divergir.
            ['chave' => 'plano_trabalho',        'rotulo' => 'Plano de Trabalho (preenchido no Portal)',               'tipo' => 'modelo',  'obrigatorio' => true],
            // Itens 2 a 6, 13, 14, 16 e 17 do checklist do módulo 3.2, um a um:
            // a caixa única "documentos de habilitação" não deixava ninguém ver
            // o que estava faltando.
            ['chave' => 'oficio_pedido',         'rotulo' => 'Ofício do pedido, assinado pelo representante legal',    'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'experiencia_previa',    'rotulo' => 'Comprovantes de experiência prévia (mínimo de um ano)',  'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'certidoes_habilitacao', 'rotulo' => 'Certidões de regularidade fiscal, previdenciária, tributária e de dívida ativa', 'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'relacao_dirigentes',    'rotulo' => 'Relação nominal atualizada dos dirigentes',              'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'docs_presidente',       'rotulo' => 'RG, CPF e comprovante de residência do presidente',      'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'planilha_pessoal',      'rotulo' => 'Planilha de detalhamento de despesas de pessoal (se houver)', 'tipo' => 'arquivo', 'obrigatorio' => false],
            ['chave' => 'balanco_patrimonial',   'rotulo' => 'Balanço patrimonial do exercício anterior',              'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'relatorio_fotografico', 'rotulo' => 'Relatório fotográfico colorido do local (obra ou reforma)', 'tipo' => 'arquivo', 'obrigatorio' => false],
            ['chave' => 'planilha_orcamentaria', 'rotulo' => 'Planilha orçamentária de custos, com regime de execução e BDI (obra ou reforma)', 'tipo' => 'arquivo', 'obrigatorio' => false],
            // Continua existindo para o que não cabe nos itens acima — e para
            // não perder o que as parcerias antigas já anexaram aqui.
            ['chave' => 'docs_habilitacao',      'rotulo' => 'Outros documentos de habilitação',                       'tipo' => 'arquivo', 'obrigatorio' => false],
            // As sete declarações da habilitação (módulo 3.2, itens 7 a 12 e 15).
            // Vêm preenchidas com o cadastro da OSC e só o responsável legal
            // assina — ver DECLARACOES_DO_RESPONSAVEL_LEGAL.
            ['chave' => 'decl_art7',             'rotulo' => 'Declaração — art. 7º, XXXIII, CF/88 (não emprega menor)',                 'tipo' => 'modelo', 'obrigatorio' => true],
            ['chave' => 'decl_art23',            'rotulo' => 'Declaração — art. 23, XIV, Decreto Municipal 048/2020 (sem contas pendentes)', 'tipo' => 'modelo', 'obrigatorio' => true],
            ['chave' => 'decl_art33',            'rotulo' => 'Declaração — art. 33, V, "c", Lei 13.019/2014 (condições materiais)',     'tipo' => 'modelo', 'obrigatorio' => true],
            ['chave' => 'decl_art34',            'rotulo' => 'Declaração — art. 34, VII, Lei 13.019/2014 (sede e tempo de existência)', 'tipo' => 'modelo', 'obrigatorio' => true],
            ['chave' => 'decl_art39',            'rotulo' => 'Declaração — art. 39, Lei 13.019/2014 (ausência de vedações)',            'tipo' => 'modelo', 'obrigatorio' => true],
            ['chave' => 'decl_art45',            'rotulo' => 'Declaração — art. 45, Lei 13.019/2014 (vedações de remuneração)',         'tipo' => 'modelo', 'obrigatorio' => true],
            ['chave' => 'decl_autenticidade',    'rotulo' => 'Declaração de autenticidade dos documentos',                              'tipo' => 'modelo', 'obrigatorio' => true],
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
            // Duas publicações, dois comprovantes: o Diário Oficial e o site do
            // Município são veículos distintos e exigidos em separado. Num campo
            // só, cabia um arquivo — anexar o segundo apagava o primeiro.
            ['chave' => 'comprovante_publicacao_doe',  'rotulo' => 'Comprovante de publicação no Diário Oficial',        'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'comprovante_publicacao_site', 'rotulo' => 'Comprovante de publicação no site oficial',          'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'autorizacao_inicio',    'rotulo' => 'Autorização de Início de Execução (modelo padrão)',      'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'dados_bancarios',       'rotulo' => 'Dados bancários (enviados pela OSC)',                    'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'op_global',             'rotulo' => 'Ordem de Pagamento Global (modelo padrão)',              'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'comprovante_empenho',   'rotulo' => 'Comprovante de empenho global',                          'tipo' => 'arquivo', 'obrigatorio' => true],
        ],

        // 3.4 Prestação de contas — os dez itens do checklist da OSC, mais os
        // dois documentos de análise da Administração.
        'prestacao_contas' => [
            ['chave' => 'oficio_encaminhamento',  'rotulo' => 'Ofício de encaminhamento da prestação de contas (modelo padrão)', 'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'relatorio',              'rotulo' => 'Relatório de Execução do Objeto e Financeira (preenchido no Portal)', 'tipo' => 'modelo', 'obrigatorio' => true],
            ['chave' => 'docs_execucao',          'rotulo' => 'Documentos que comprovam a execução do objeto',                  'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'extrato_conta',          'rotulo' => 'Extrato da conta corrente do período',                           'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'extrato_aplicacao',      'rotulo' => 'Extrato da conta de aplicação/poupança do período',              'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'comprovantes_despesas',  'rotulo' => 'Comprovantes de despesas, em ordem cronológica',                 'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'termo_compromisso',      'rotulo' => 'Termo de compromisso de guarda dos documentos (modelo padrão)',  'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'resumo_folha',           'rotulo' => 'Resumo da folha de pagamento (se houver despesa com pessoal)',   'tipo' => 'modelo',  'obrigatorio' => false],
            ['chave' => 'laudo_obra',             'rotulo' => 'Laudo e relatório fotográfico de obra (se for o caso)',          'tipo' => 'modelo',  'obrigatorio' => false],
            ['chave' => 'outros_documentos',      'rotulo' => 'Outros documentos',                                             'tipo' => 'arquivo', 'obrigatorio' => false],
            // Análise da Administração
            ['chave' => 'parecer_previo',         'rotulo' => 'Parecer prévio da SCP (modelo padrão)',                          'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'parecer_conclusivo',     'rotulo' => 'Parecer conclusivo da Unidade Gestora (modelo padrão)',          'tipo' => 'modelo',  'obrigatorio' => true],
        ],

        // 3.3 Alteração da Parceria — os dez itens do checklist da OSC. Os
        // marcados "se for o caso" no modelo entram como não obrigatórios.
        'alteracao' => [
            ['chave' => 'proposta_alteracao',  'rotulo' => 'Proposta de alteração (preenchida no Portal)',                        'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'oficio_alteracao',    'rotulo' => 'Ofício com justificativa fundamentada, assinado',                     'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'docs_alegacoes',      'rotulo' => 'Documentos que atestam as alegações da justificativa (se for o caso)','tipo' => 'arquivo', 'obrigatorio' => false],
            ['chave' => 'decl_capacidade',     'rotulo' => 'Declaração de manutenção da capacidade técnica',                      'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'extrato_conta',       'rotulo' => 'Extrato da conta corrente, atual',                                    'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'extrato_aplicacao',   'rotulo' => 'Extrato da conta de aplicação/poupança, atual',                       'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'comprovacao_contrapartida', 'rotulo' => 'Comprovação de cumprimento de contrapartida (se for o caso)',   'tipo' => 'arquivo', 'obrigatorio' => false],
            ['chave' => 'certidoes',           'rotulo' => 'Certidões de regularidade fiscal, previdenciária e tributária',       'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'orcamentos',          'rotulo' => 'Orçamentos (se for o caso)',                                          'tipo' => 'arquivo', 'obrigatorio' => false],
            ['chave' => 'decl_autenticidade',  'rotulo' => 'Declaração de autenticidade dos documentos apresentados',             'tipo' => 'modelo',  'obrigatorio' => true],
            // Análise da Administração
            ['chave' => 'autorizacao_ug',      'rotulo' => 'Autorização da Unidade Gestora (modelo padrão)',                      'tipo' => 'modelo',  'obrigatorio' => true],
            ['chave' => 'despacho_scp',        'rotulo' => 'Despacho da SCP sobre a alteração (modelo padrão)',                   'tipo' => 'modelo',  'obrigatorio' => true],
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

    /**
     * Peças que o Planejamento já produziu, por chave do checklist => tipo da
     * peça do processo (ver ProcessoPeca::TIPOS).
     *
     * Tudo o que está aqui nasce, é assinado e é publicado dentro do processo
     * de Planejamento. Pedir de novo na Seleção seria pedir um segundo original
     * do mesmo documento — com outra assinatura e outro código de validação. A
     * Seleção passa a apontar para o do Planejamento.
     *
     * `anexos` aponta para o edital de propósito: os anexos do chamamento são
     * os anexos do edital, e é lá que a SCP os envia.
     */
    public const ORIGEM_PLANEJAMENTO = [
        'chamamento_publico' => [
            'edital'             => 'edital',
            'anexos'             => 'edital',
            'comissao_selecao'   => 'portaria_comissao',
            'parecer_juridico'   => 'parecer_juridico',
            'pub_extrato_edital' => 'comprovante_publicacao',
        ],
        'dispensa_inexigibilidade' => [
            'justificativa'        => 'justificativa_dispensa',
            'parecer_tecnico_cnas' => 'parecer_cnas',
            'parecer_juridico'     => 'parecer_juridico',
            'pub_extrato'          => 'comprovante_publicacao',
        ],
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
        'oficio_pedido'          => 'osc',
        'experiencia_previa'     => 'osc',
        'certidoes_habilitacao'  => 'osc',
        'relacao_dirigentes'     => 'osc',
        'docs_presidente'        => 'osc',
        'planilha_pessoal'       => 'osc',
        'balanco_patrimonial'    => 'osc',
        'relatorio_fotografico'  => 'osc',
        'planilha_orcamentaria'  => 'osc',
        'docs_habilitacao'       => 'osc',
        'decl_art7'              => 'osc',
        'decl_art23'             => 'osc',
        'decl_art33'             => 'osc',
        'decl_art34'             => 'osc',
        'decl_art39'             => 'osc',
        'decl_art45'             => 'osc',
        'decl_autenticidade'     => 'osc',
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
        'comprovante_publicacao_doe'  => 'scp',
        'comprovante_publicacao_site' => 'scp',
        'autorizacao_inicio'     => 'scp',
        'dados_bancarios'        => 'osc',
        'op_global'              => 'scp',  // a SCP elabora; a UG assina
        'comprovante_empenho'    => 'scp',
    ];

    public const CELEBRACAO_ETAPA = [
        'convocacao_osc'         => 0,
        'plano_trabalho'         => 1,
        'oficio_pedido'          => 1,
        'experiencia_previa'     => 1,
        'certidoes_habilitacao'  => 1,
        'relacao_dirigentes'     => 1,
        'docs_presidente'        => 1,
        'planilha_pessoal'       => 1,
        'balanco_patrimonial'    => 1,
        'relatorio_fotografico'  => 1,
        'planilha_orcamentaria'  => 1,
        'docs_habilitacao'       => 1,
        'decl_art7'              => 1,
        'decl_art23'             => 1,
        'decl_art33'             => 1,
        'decl_art34'             => 1,
        'decl_art39'             => 1,
        'decl_art45'             => 1,
        'decl_autenticidade'     => 1,
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
        'comprovante_publicacao_doe'  => 10,
        'comprovante_publicacao_site' => 10,
        'autorizacao_inicio'     => 10,
        'dados_bancarios'        => 11,
        'op_global'              => 12,
        'comprovante_empenho'    => 14,
    ];

    /**
     * Prestação de contas: tudo o que a OSC monta fica na etapa 1; a análise
     * prévia é da SCP e o parecer conclusivo, da Unidade Gestora.
     */
    public const PRESTACAO_SETOR = [
        'oficio_encaminhamento' => 'osc',
        'relatorio'             => 'osc',
        'docs_execucao'         => 'osc',
        'extrato_conta'         => 'osc',
        'extrato_aplicacao'     => 'osc',
        'comprovantes_despesas' => 'osc',
        'termo_compromisso'     => 'osc',
        'resumo_folha'          => 'osc',
        'laudo_obra'            => 'osc',
        'outros_documentos'     => 'osc',
        'parecer_previo'        => 'scp',
        'parecer_conclusivo'    => 'ug',
    ];

    public const PRESTACAO_ETAPA = [
        'oficio_encaminhamento' => 0,
        'relatorio'             => 0,
        'docs_execucao'         => 0,
        'extrato_conta'         => 0,
        'extrato_aplicacao'     => 0,
        'comprovantes_despesas' => 0,
        'termo_compromisso'     => 0,
        'resumo_folha'          => 0,
        'laudo_obra'            => 0,
        'outros_documentos'     => 0,
        'parecer_previo'        => 1,
        'parecer_conclusivo'    => 2,
    ];

    /**
     * Alteração da parceria: a OSC instrui o pedido inteiro (etapa 0); a UG
     * autoriza (etapa 1) e a SCP processa (etapa 2).
     */
    public const ALTERACAO_SETOR = [
        'proposta_alteracao'       => 'osc',
        'oficio_alteracao'         => 'osc',
        'docs_alegacoes'           => 'osc',
        'decl_capacidade'          => 'osc',
        'extrato_conta'            => 'osc',
        'extrato_aplicacao'        => 'osc',
        'comprovacao_contrapartida' => 'osc',
        'certidoes'                => 'osc',
        'orcamentos'               => 'osc',
        'decl_autenticidade'       => 'osc',
        'autorizacao_ug'           => 'ug',
        'despacho_scp'             => 'scp',
    ];

    public const ALTERACAO_ETAPA = [
        'proposta_alteracao'       => 0,
        'oficio_alteracao'         => 0,
        'docs_alegacoes'           => 0,
        'decl_capacidade'          => 0,
        'extrato_conta'            => 0,
        'extrato_aplicacao'        => 0,
        'comprovacao_contrapartida' => 0,
        'certidoes'                => 0,
        'orcamentos'               => 0,
        'decl_autenticidade'       => 0,
        'autorizacao_ug'           => 1,
        'despacho_scp'             => 2,
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
     * Declarações que só o responsável legal da OSC assina.
     *
     * Todas abrem com "Eu, [nome], na qualidade de representante legal" ou são
     * feitas "sob as penas da Lei". A equipe da OSC pode revisar o texto, mas
     * assinar é afirmar em nome de quem responde pela entidade — deixar um
     * integrante fazê-lo seria uma pessoa declarando, sob pena de falsidade,
     * no lugar de outra. É a mesma régua de submeter proposta e contra-assinar
     * o Termo (ver User::ehResponsavelLegalOsc).
     */
    public const DECLARACOES_DO_RESPONSAVEL_LEGAL = [
        'decl_art7', 'decl_art23', 'decl_art33', 'decl_art34',
        'decl_art39', 'decl_art45', 'decl_autenticidade',
        // Alteração da parceria (3.3): a proposta e a declaração de capacidade
        // técnica são atos de quem responde pela organização.
        'proposta_alteracao', 'decl_capacidade',
        // Habilitação (3.2), item 1: "assinada eletronicamente pelo
        // representante legal".
        'plano_trabalho',
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
    /*
     * Declarações da habilitação (módulo 3.2). Diferem dos demais modelos em
     * duas coisas: são documentos da OSC, e por isso não levam o cabeçalho com
     * o brasão da Prefeitura; e vêm preenchidas com o cadastro da organização,
     * em vez de "XXXXX" — o que faltar no cadastro aparece como "XXXXX".
     *
     * O texto é o dos modelos entregues pela SCP, palavra por palavra — são
     * declarações feitas sob as penas da lei, e a redação é de quem as
     * escreveu. Só mudou o que estava errado de fato:
     * - art. 39, VI: o modelo citava o "Município de Montes Claros" (fora
     *   copiado de outro município); aqui é São Gonçalo do Rio Abaixo;
     * - art. 23: "prestação de contas HÁ nenhum órgão" → "A nenhum órgão";
     * - art. 34: "ativo há de ___ anos" → "ativo há ___ anos".
     * E o que era lacuna virou dado: os traços e colchetes dão lugar ao
     * cadastro, e "NOME / Presidente" dá lugar ao nome do representante legal
     * — quem declara o faz "na qualidade de representante legal", que nem
     * sempre é o presidente.
     */
    private const DECL_QUALIFICACAO = 'Eu, <strong>{{rep_nome}}</strong>, portador (a) da carteira de identidade n.º {{rep_rg}} expedida pela {{rep_rg_orgao}}, inscrito (a) no CPF sob o n.º {{rep_cpf}}, na qualidade de representante legal da <strong>{{osc_nome}}</strong>, sediada no(a) {{osc_endereco}}, Bairro {{osc_bairro}}, CEP: {{osc_cep}}, inscrita no CNPJ sob o n.º {{osc_cnpj}}';

    private const DECL_PENAS = '<p>A presente declaração é feita sob as penas da Lei, assumindo a declarante toda e qualquer responsabilidade, seja na esfera penal, civil ou administrativa, em caso de sua falsidade.</p>';

    private const DECL_FECHO = '<p>Por ser verdade, firmo a presente declaração.</p>'
        . '<p style="text-align:right">São Gonçalo do Rio Abaixo, {{data_extenso}}.</p>'
        . '<p style="text-align:center"><br>{{rep_nome}}<br>Representante legal — {{osc_nome}}</p>';

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
        /*
         * Prestação de contas. O ofício, o relatório e o resumo da folha não
         * estão aqui: nascem dos campos preenchidos pela OSC, com as somas já
         * feitas (ver App\Support\PrestacaoDocumento). Aqui ficam os que são
         * texto de verdade — o compromisso de guarda, o laudo de obra e os
         * dois pareceres da Administração.
         */
        // 3.3 Alteração da parceria. A declaração de capacidade técnica é o
        // oitavo modelo do módulo 3, e o único que faltava: ela só existe aqui.
        'alteracao' => [
            'proposta_alteracao' => <<<'HTML'
<p style="text-align:center"><strong>PROPOSTA DE ALTERAÇÃO DA PARCERIA</strong></p>
<p><strong>OSC:</strong> {{osc_nome}} — CNPJ {{osc_cnpj}}<br><strong>Termo nº:</strong> {{instrumento}}<br><strong>Processo nº:</strong> {{numero_processo}}<br><strong>Unidade Gestora:</strong> {{unidade_gestora}}</p>
<p><strong>Alteração pretendida:</strong> {{alteracao_titulo}}</p>
<p><strong>Descrição das alterações desejadas:</strong></p>
<p>{{alteracao_descricao}}</p>
<p><strong>Justificativa:</strong></p>
<p>{{alteracao_justificativa}}</p>
<p>O Plano de Trabalho alterado acompanha esta proposta, tramitado no Portal, com o plano de aplicação, o cronograma de execução e o cronograma de desembolso atualizados.</p>
<p style="text-align:right">São Gonçalo do Rio Abaixo, {{data_extenso}}.</p>
<p style="text-align:center"><br>{{rep_nome}}<br>Representante legal — {{osc_nome}}</p>
HTML,

            // Texto do modelo da SCP, com as lacunas viradas dado.
            'decl_capacidade' => <<<'HTML'
<p style="text-align:center"><strong>DECLARAÇÃO DE MANUTENÇÃO DA CAPACIDADE TÉCNICA</strong></p>
<p>A <strong>{{osc_nome}}</strong>, inscrita no CNPJ nº {{osc_cnpj}}, com sede à {{osc_endereco}}, por meio de seu representante legal abaixo assinado, DECLARA, para os devidos fins, que permanece com plenas condições técnicas, materiais, administrativas e operacionais para a execução do objeto pactuado no {{instrumento_tipo}} nº {{instrumento}}.</p>
<p>Declara, ainda, que dispõe de equipe qualificada, estrutura física adequada, recursos materiais suficientes e capacidade gerencial compatível com as metas e atividades previstas no Plano de Trabalho, comprometendo-se a manter tais condições durante toda a vigência do instrumento celebrado.</p>
<p>Por ser verdade, firma a presente declaração.</p>
<p style="text-align:right">São Gonçalo do Rio Abaixo, {{data_extenso}}.</p>
<p style="text-align:center"><br>{{rep_nome}}<br>CPF {{rep_cpf}}<br>Representante legal — {{osc_nome}}</p>
HTML,

            'decl_autenticidade' => <<<'HTML'
<p style="text-align:center"><strong>DECLARAÇÃO DE AUTENTICIDADE DOS DOCUMENTOS</strong></p>
<p>DECLARO, sob as penas do art. 299 do Código Penal, serem autênticos e verdadeiros todos os documentos e cópias juntados ao pedido de alteração do {{instrumento_tipo}} nº {{instrumento}}, observadas as demais determinações previstas na legislação.</p>
<p>DECLARO, ainda, que são de minha exclusiva responsabilidade a conformidade entre os dados informados e a documentação enviada, bem como a conservação, em papel, dos originais dos documentos digitalizados até que decaia o direito de revisão dos atos praticados no processo, para que, caso solicitado, sejam apresentados para qualquer tipo de conferência.</p>
<p style="text-align:right">São Gonçalo do Rio Abaixo, {{data_extenso}}.</p>
<p style="text-align:center"><br>{{rep_nome}}<br>Representante legal — {{osc_nome}}</p>
HTML,

            'autorizacao_ug' => self::CABECALHO . <<<'HTML'
<p style="text-align:center"><strong>AUTORIZAÇÃO DE ALTERAÇÃO DA PARCERIA</strong></p>
<p><strong>OSC:</strong> {{osc_nome}} — CNPJ {{osc_cnpj}}<br><strong>Termo nº:</strong> {{instrumento}}<br><strong>Processo nº:</strong> {{numero_processo}}</p>
<p>A <strong>{{unidade_gestora}}</strong>, na qualidade de Unidade Gestora da parceria, analisou o pedido de alteração apresentado pela organização e a documentação que o instrui.</p>
<p><strong>Alteração pretendida:</strong> {{alteracao_titulo}}</p>
<p><strong>Análise:</strong></p>
<p>XXXXX</p>
<p>Ante o exposto, <strong>AUTORIZA</strong> a alteração pretendida e encaminha o pedido ao Setor de Convênios e Parcerias para processamento.</p>
<p style="text-align:right">São Gonçalo do Rio Abaixo, {{data_extenso}}.</p>
<p style="text-align:center"><br>_______________________________<br>Responsável pela Unidade Gestora</p>
HTML,

            'despacho_scp' => self::CABECALHO . <<<'HTML'
<p style="text-align:center"><strong>DESPACHO — ALTERAÇÃO DA PARCERIA</strong></p>
<p><strong>OSC:</strong> {{osc_nome}} — CNPJ {{osc_cnpj}}<br><strong>Termo nº:</strong> {{instrumento}}<br><strong>Processo nº:</strong> {{numero_processo}}</p>
<p><strong>Alteração pretendida:</strong> {{alteracao_titulo}}</p>
<p>O Setor de Convênios e Parcerias conferiu a instrução do pedido e a autorização da Unidade Gestora.</p>
<p><strong>Instrumento de formalização:</strong> XXXXX (termo aditivo ou apostilamento, conforme a natureza da alteração).</p>
<p><strong>Conclusão:</strong></p>
<p>XXXXX</p>
<p style="text-align:right">São Gonçalo do Rio Abaixo, {{data_extenso}}.</p>
<p style="text-align:center"><br>_______________________________<br>Setor de Convênios e Parcerias</p>
HTML,
        ],

        'prestacao_contas' => [
            'termo_compromisso' => <<<'HTML'
<p style="text-align:center"><strong>TERMO DE COMPROMISSO</strong><br>(Anexo X — guarda da prestação de contas)</p>
<p>A OSC <strong>{{osc_nome}}</strong>, estabelecida à {{osc_endereco}}, Bairro {{osc_bairro}}, na cidade de {{osc_cidade}}/{{osc_uf}}, CEP {{osc_cep}}, inscrita no CNPJ sob o nº {{osc_cnpj}}, representada por {{rep_nome}}, na qualidade de representante legal, <strong>compromete-se</strong>, durante o prazo de 10 (dez) anos, contado do dia útil subsequente ao da apresentação desta prestação de contas, a manter em seu arquivo os documentos originais que a compõem, conforme determina o art. 63 do Decreto Municipal nº 048/2020.</p>
<p>Compromete-se, ainda, a apresentá-los à Administração Pública, aos órgãos de controle interno e externo e ao Ministério Público sempre que solicitado.</p>
<p style="text-align:right">São Gonçalo do Rio Abaixo, {{data_extenso}}.</p>
<p style="text-align:center"><br>{{rep_nome}}<br>Representante legal — {{osc_nome}}</p>
HTML,

            'laudo_obra' => <<<'HTML'
<p style="text-align:center"><strong>LAUDO DE OBRA</strong><br>(Anexo VIII — Termo de Aceitação Definitiva de Obra)</p>
<p><strong>OSC PARCEIRA:</strong> {{osc_nome}} — CNPJ {{osc_cnpj}}<br><strong>Termo nº:</strong> {{instrumento}}<br><strong>Processo nº:</strong> {{numero_processo}}</p>
<p>Declaramos, para os devidos fins, que recebemos na presente data, em perfeitas condições de uso e funcionamento e em conformidade com o termo de parceria acima identificado, a obra XXXXX, executada no Município de São Gonçalo do Rio Abaixo.</p>
<p><strong>LAUDO TÉCNICO — parecer e descrição:</strong></p>
<p>XXXXX</p>
<p style="text-align:right">São Gonçalo do Rio Abaixo, {{data_extenso}}.</p>
<table style="width:100%;border-collapse:collapse" border="0" cellpadding="8"><tbody><tr>
<td style="text-align:center">_______________________________<br>{{rep_nome}}<br>Representante legal — {{osc_nome}}<br>CPF {{rep_cpf}}</td>
<td style="text-align:center">_______________________________<br>Responsável técnico<br>Registro no CREA/CAU nº XXXXX</td>
</tr></tbody></table>
HTML,

            'parecer_previo' => self::CABECALHO . <<<'HTML'
<p style="text-align:center"><strong>PARECER PRÉVIO DE PRESTAÇÃO DE CONTAS</strong></p>
<p><strong>Processo nº:</strong> {{numero_processo}}<br><strong>OSC:</strong> {{osc_nome}} — CNPJ {{osc_cnpj}}<br><strong>Termo nº:</strong> {{instrumento}}<br><strong>Unidade Gestora:</strong> {{unidade_gestora}}</p>
<p>O Setor de Convênios e Parcerias procedeu à análise prévia da prestação de contas apresentada, conferindo a documentação do checklist, os extratos bancários, os comprovantes de despesa e a conciliação do período.</p>
<p><strong>Análise:</strong></p>
<p>XXXXX</p>
<p><strong>Conclusão:</strong> a prestação de contas encontra-se XXXXX (em condições de ser aprovada / em condições de ser aprovada com ressalvas / pendente de diligência), seguindo à Unidade Gestora para análise e decisão do Gestor da Parceria e da Comissão de Monitoramento e Avaliação.</p>
<p style="text-align:right">São Gonçalo do Rio Abaixo, {{data_extenso}}.</p>
<p style="text-align:center">XXXXX<br>Setor de Convênios e Parcerias (SCP)</p>
HTML,

            'parecer_conclusivo' => self::CABECALHO . <<<'HTML'
<p style="text-align:center"><strong>PARECER CONCLUSIVO DE PRESTAÇÃO DE CONTAS</strong></p>
<p><strong>Processo nº:</strong> {{numero_processo}}<br><strong>OSC:</strong> {{osc_nome}} — CNPJ {{osc_cnpj}}<br><strong>Termo nº:</strong> {{instrumento}}<br><strong>Unidade Gestora:</strong> {{unidade_gestora}}</p>
<p>O Gestor da Parceria e a Comissão de Monitoramento e Avaliação analisaram a prestação de contas e o parecer prévio do Setor de Convênios e Parcerias, verificando o cumprimento das metas do Plano de Trabalho e a regularidade da aplicação dos recursos.</p>
<p><strong>Análise:</strong></p>
<p>XXXXX</p>
<p><strong>Decisão:</strong> a prestação de contas fica XXXXX (aprovada / aprovada com ressalvas / rejeitada), nos termos do art. 72 da Lei Federal nº 13.019/2014.</p>
<p style="text-align:right">São Gonçalo do Rio Abaixo, {{data_extenso}}.</p>
<table style="width:100%;border-collapse:collapse" border="0" cellpadding="8"><tbody><tr>
<td style="text-align:center">_______________________________<br>Gestor da Parceria</td>
<td style="text-align:center">_______________________________<br>Comissão de Monitoramento e Avaliação</td>
<td style="text-align:center">_______________________________<br>{{unidade_gestora}}</td>
</tr></tbody></table>
HTML,
        ],

        // Celebração: apenas os modelos próprios desta etapa. Os demais são
        // reaproveitados de outras categorias/motores em `modeloTexto()`.
        'celebracao' => [
            'decl_art7' => '<p style="text-align:center"><strong>DECLARAÇÃO</strong><br>(art. 7º, XXXIII, CF/88)</p>'
                . '<p>' . self::DECL_QUALIFICACAO . ', declaro que não EMPREGAMOS MENOR DE IDADE, conforme dispõe o art. 7º, XXXIII, CF/88.</p>'
                . self::DECL_PENAS . self::DECL_FECHO,

            // Correção: "HÁ nenhum órgão" → "A nenhum órgão" (e o acento de ÓRGÃO).
            'decl_art23' => '<p style="text-align:center"><strong>DECLARAÇÃO</strong><br>(art. 23, XIV, decreto municipal 048/2020)</p>'
                . '<p>' . self::DECL_QUALIFICACAO . ', declaro que NÃO DEVEMOS PRESTAÇÃO DE CONTAS A NENHUM ÓRGÃO DE QUALQUER ESFERA, OU ENTIDADE.</p>'
                . self::DECL_PENAS . self::DECL_FECHO,

            // A OSC adota uma das três redações. No sistema não há "versão
            // final" separada — o texto é editado ali mesmo —, então a
            // observação diz o que fazer em vez de pedir que seja suprimida.
            'decl_art33' => <<<'HTML'
<p style="text-align:center"><strong>DECLARAÇÃO</strong><br>(art. 33, V, c, da Lei nº 13.019 de 2014)</p>
<p>Declaro, em conformidade com o art. 33, caput, inciso V, alínea “c”, da Lei nº 13.019, de 2014, que a <strong>{{osc_nome}}</strong>:</p>
<ul><li>dispõe de instalações e outras condições materiais para o desenvolvimento das atividades ou projetos previstos na parceria e o cumprimento das metas estabelecidas.</li></ul>
<p style="text-align:center">OU</p>
<ul><li>Irei contratar ou irei adquirir com recursos da parceria as condições materiais para o desenvolvimento das atividades ou projetos previstos na parceria e o cumprimento das metas estabelecidas.</li></ul>
<p style="text-align:center">OU</p>
<ul><li>dispõe de instalações e outras condições materiais para o desenvolvimento das atividades ou projetos previstos na parceria e o cumprimento das metas estabelecidas, bem como, ainda, irei contratar ou irei adquirir com recursos da parceria outros bens para tanto.</li></ul>
<p><em>OBS.: A organização da sociedade civil adotará uma das três redações acima, conforme a sua situação. Apague as duas que não se aplicam e esta observação antes de assinar.</em></p>
HTML
                . '<p style="text-align:right">São Gonçalo do Rio Abaixo, {{data_extenso}}.</p>'
                . '<p style="text-align:center"><br>{{rep_nome}}<br>Representante legal — {{osc_nome}}</p>',

            // Correção: "ativo há de ___ anos" → "ativo há ___ anos".
            'decl_art34' => <<<'HTML'
<p style="text-align:center"><strong>DECLARAÇÃO</strong><br>(Art. 34, VII da Lei n° 13.019/2014)</p>
<p>DECLARO para os devidos fins que, a Organização da Sociedade Civil (OSC), denominada de <strong>{{osc_nome}}</strong>, se encontra sediada à {{osc_logradouro}}, nº {{osc_numero}}, Bairro {{osc_bairro}}, na cidade de {{osc_cidade}}/{{osc_uf}}, conforme comprovante de conta (água, luz ou telefone)/contrato de locação, em anexo, inscrita no CNPJ nº {{osc_cnpj}}, ativo há {{osc_anos}} ({{osc_anos_extenso}}) anos de existência, confirmando a veracidade das informações confirmadas no comprovante de Cadastro Nacional de Pessoas Jurídicas, emitido pela Receita Federal do Brasil.</p>
HTML
                . self::DECL_FECHO,

            // Correção: o inciso VI citava o "Município de Montes Claros" — o
            // modelo fora copiado de outro município.
            'decl_art39' => <<<'HTML'
<p style="text-align:center"><strong>DECLARAÇÃO</strong><br>(art. 39 da Lei n° 13.019/2014)</p>
<p>Declaro, para fins de habilitação, que a <strong>{{osc_nome}}</strong> e seus dirigentes, não incorrem em quaisquer das vedações previstas no art. 39 da Lei Federal nº 13.019, de 2014 e, portanto:</p>
<p>I – é regularmente constituída (ou, se estrangeira, está autorizada a funcionar no território nacional);</p>
<p>II – não é omissa no dever de prestar contas de parceria anteriormente celebrada;</p>
<p>III – não tem como dirigente membro de Poder ou do Ministério Público, ou dirigente de órgão ou entidade da administração pública estadual ou, seus respectivos cônjuges ou companheiros, bem como parentes em linha reta, colateral ou por afinidade, até o segundo grau;</p>
<p>IV – não teve contas rejeitadas pela administração pública nos últimos cinco anos ou, foram sanadas as irregularidades que motivaram a rejeição e quitados os débitos eventualmente imputados ou, foi reconsiderada ou revista a decisão pela rejeição ou, a apreciação das contas encontra-se pendente de decisão sobre recurso com efeito suspensivo;</p>
<p>V – não há punição vigente de suspensão de participação em licitação e impedimento de contratar com a administração ou, de declaração de inidoneidade para licitar ou contratar com a administração pública;</p>
<p>VI – não há punição vigente de suspensão de participação em chamamento público e impedimento de celebrar parceria ou contrato com órgão ou entidade da administração pública do Município de São Gonçalo do Rio Abaixo;</p>
<p>VII – não há punição vigente de declaração de inidoneidade para participar de chamamento público e de celebrar parcerias ou contratos com órgãos ou entidades de qualquer esfera de governo;</p>
<p>VIII – não teve contas de parceria julgadas irregulares ou rejeitadas por Tribunal ou Conselho de Contas de qualquer esfera da Federação, em decisão irrecorrível, nos últimos 8 (oito) anos;</p>
<p>IX – não tem, entre seus dirigentes, pessoa:</p>
<p>a) cujas contas relativas a parcerias tenham sido julgadas irregulares ou rejeitadas por Tribunal ou Conselho de Contas de qualquer esfera da Federação, em decisão irrecorrível, nos últimos 8 (oito) anos;</p>
<p>b) julgada responsável por falta grave e inabilitada para o exercício de cargo em comissão ou função de confiança, enquanto durar a inabilitação;</p>
<p>c) considerada responsável por ato de improbidade, enquanto durarem os prazos estabelecidos nos incisos I, II e III do art. 12 da Lei no 8.429, de 2 de junho de 1992.</p>
HTML
                . self::DECL_FECHO,

            'decl_art45' => '<p style="text-align:center"><strong>DECLARAÇÃO</strong><br>(art. 45, da Lei Federal nº. 13.019/2014 e art. 17 e 41ss do Decreto Municipal nº. 048/2020)</p>'
                . '<p>' . self::DECL_QUALIFICACAO . ', declaro que não serão remunerados, a qualquer título, com os recursos repassados:</p>'
                . <<<'HTML'
<p>a) membro de Poder ou do Ministério Público ou dirigente de órgão ou de entidade da Administração Pública Municipal;</p>
<p>b) servidor ou empregado público, inclusive aquele que exerça cargo em comissão ou função de confiança, de órgão ou entidade da administração pública municipal celebrante, ressalvadas as hipóteses previstas em lei específica e na lei de diretrizes orçamentárias; e</p>
<p>c) pessoas naturais condenadas pela prática de crimes contra a Administração Pública ou contra o patrimônio público, de crimes eleitorais para os quais a lei comine pena privativa de liberdade, e de crimes de lavagem ou de ocultação de bens, direito e valores.</p>
<p>d) cônjuge, companheiro ou parente, em linha reta ou colateral, por consanguinidade e ou afinidade, até o terceiro grau, de agente público que exerça, na administração pública municipal, cargo de natureza especial, cargo de provimento em comissão ou função de direção, chefia ou assessoramento.</p>
HTML
                . self::DECL_PENAS . self::DECL_FECHO,

            'decl_autenticidade' => <<<'HTML'
<p style="text-align:center"><strong>DECLARAÇÃO DE AUTENTICIDADE DOS DOCUMENTOS</strong></p>
<p>DECLARO, sob as penas do art. 299 do Código Penal, serem autênticos e verdadeiros todos os documentos e cópias juntados ao processo nº {{numero_processo}}, observadas as demais determinações previstas na legislação.</p>
<p>DECLARO, ainda, que são de minha exclusiva responsabilidade a conformidade entre os dados informados e a documentação enviada, bem como a conservação, em papel, dos originais dos documentos digitalizados até que decaia o direito de revisão dos atos praticados no processo, para que, caso solicitado, sejam apresentados para qualquer tipo de conferência.</p>
HTML
                . '<p style="text-align:right">São Gonçalo do Rio Abaixo, {{data_extenso}}.</p>'
                . '<p style="text-align:center"><br>{{rep_nome}}<br>Representante legal — {{osc_nome}}</p>',

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

    /** Quem criou o anexo avulso — null nas peças que vêm do template. */
    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por');
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

    /** Documento do Planejamento que satisfaz este item (ver ORIGEM_PLANEJAMENTO). */
    public function origem(): BelongsTo
    {
        return $this->belongsTo(ProcessoPeca::class, 'origem_processo_peca_id');
    }

    /**
     * Este item é satisfeito por um documento do Planejamento?
     *
     * Quando sim, não há o que preencher nem o que assinar aqui: o documento
     * existe, assinado, no processo — a Seleção só o exibe.
     */
    public function vemDoPlanejamento(): bool
    {
        return $this->origem_processo_peca_id !== null;
    }

    public function preenchido(): bool
    {
        if ($this->vemDoPlanejamento()) {
            return true;
        }

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
        // Veio do Planejamento assinado: está pronta, e a assinatura que vale é
        // a de lá — a coluna assinado_em desta linha continua vazia de propósito,
        // para não haver duas assinaturas do mesmo documento.
        if ($this->vemDoPlanejamento()) {
            return true;
        }

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
    private function donoEmTramite(): Chamamento|Proposta|PrestacaoContas|Alteracao|null
    {
        $alvo = $this->pecaable;

        return match (true) {
            $this->categoria === 'chamamento_publico'
                && $alvo instanceof Chamamento && $alvo->temTramiteSelecao() => $alvo,
            $this->categoria === 'celebracao' && $alvo instanceof Proposta => $alvo,
            $this->categoria === 'prestacao_contas' && $alvo instanceof PrestacaoContas => $alvo,
            $this->categoria === 'alteracao' && $alvo instanceof Alteracao => $alvo,
            default => null,
        };
    }

    /** Mapas de designação conforme a categoria em trâmite. */
    private function mapaSetor(): array
    {
        return match ($this->categoria) {
            'celebracao'       => self::CELEBRACAO_SETOR,
            'prestacao_contas' => self::PRESTACAO_SETOR,
            'alteracao'        => self::ALTERACAO_SETOR,
            default            => self::SELECAO_SETOR,
        };
    }

    private function mapaEtapa(): array
    {
        return match ($this->categoria) {
            'celebracao'       => self::CELEBRACAO_ETAPA,
            'prestacao_contas' => self::PRESTACAO_ETAPA,
            'alteracao'        => self::ALTERACAO_ETAPA,
            default            => self::SELECAO_ETAPA,
        };
    }

    private function mapaAssinatura(): array
    {
        // A prestação de contas não tem documento assinado por setor diferente
        // de quem o preenche — cada peça é assinada por quem a emite.
        return match ($this->categoria) {
            'celebracao'       => self::CELEBRACAO_ASSINATURA,
            'prestacao_contas', 'alteracao' => [],
            default            => self::SELECAO_ASSINATURA,
        };
    }

    /** Setor designado para preencher a peça no trâmite. */
    /**
     * Setor designado para preencher. O anexo avulso guarda o seu na linha —
     * ele não está nos mapas, que são indexados pela chave do template.
     */
    public function selecaoSetor(): ?string
    {
        return $this->setor ?? $this->mapaSetor()[$this->chave] ?? null;
    }

    public function selecaoEtapa(): ?int
    {
        return $this->etapa ?? $this->mapaEtapa()[$this->chave] ?? null;
    }

    /**
     * Setor dono da peça fora do trâmite (fase do edital) — null quando a peça
     * não é dessas ou a categoria não é chamamento público (dispensa e
     * inexigibilidade seguem sem designação, como sempre estiveram).
     */
    public function setorPrevio(): ?string
    {
        // Anexo avulso não está em mapa nenhum (a chave é um uuid): o dono vai
        // gravado na própria linha, no ato de criar o espaço.
        if ($this->extra && $this->setor !== null) {
            return $this->setor;
        }

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
    /**
     * A peça é governada por um trâmite (Seleção ou Celebração)?
     *
     * Precisa das duas coisas: setor E etapa. Nos mapas do template as duas
     * andam juntas (mesmas chaves), mas um anexo avulso criado na fase prévia
     * guarda só o setor de quem o criou — sem etapa, ele fica onde nasceu, nos
     * documentos gerais, em vez de cair no bloco da etapa 1 por falta de número.
     */
    public function emTramite(): bool
    {
        return $this->donoEmTramite() !== null
            && $this->selecaoSetor() !== null
            && $this->selecaoEtapa() !== null;
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
    private function oscDona(?User $user, Chamamento|Proposta|PrestacaoContas|Alteracao|null $dono): bool
    {
        if (!$user?->ehRepresentanteOsc()) {
            return false;
        }

        // Na prestação de contas a OSC vem pela parceria: instrumento →
        // proposta → OSC.
        $oscDoDono = match (true) {
            $dono instanceof Proposta         => $dono->osc_id,
            $dono instanceof PrestacaoContas  => $dono->osc()?->id,
            $dono instanceof Alteracao        => $dono->osc()?->id,
            default                           => null,
        };

        return $oscDoDono !== null && $user->osc->id === $oscDoDono;
    }

    public function podePreencher(?User $user): bool
    {
        if ($this->vemDoPlanejamento()) {
            return false;
        }

        $dono = $this->donoEmTramite();

        // Fora do trâmite (fase do edital, dispensa, anexo avulso sem etapa).
        if (!$this->emTramite()) {
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
        if ($this->tipo !== 'modelo' || empty($this->conteudo) || $this->assinado()
            || $this->vemDoPlanejamento()
        ) {
            return false;
        }

        $dono = $this->donoEmTramite();

        if (!$this->emTramite()) {
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

        if ($this->selecaoSetorAssinatura() !== 'osc') {
            return true;
        }

        return $this->oscDona($user, $dono)
            && (!$this->ehDeclaracaoDoResponsavelLegal() || $user->ehResponsavelLegalOsc());
    }

    /** Declaração que só o responsável legal da OSC pode assinar. */
    public function ehDeclaracaoDoResponsavelLegal(): bool
    {
        return $this->categoria === 'celebracao'
            && in_array($this->chave, self::DECLARACOES_DO_RESPONSAVEL_LEGAL, true);
    }

    /**
     * Explicação curta de por que a peça está travada (para a interface).
     * Devolve null quando o usuário pode atuar nela agora.
     */
    public function motivoTrava(?User $user = null): ?string
    {
        $dono = $this->donoEmTramite();

        if (!$this->emTramite() || $this->assinado() || $this->vemDoPlanejamento()) {
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

            // Rótulo, ordem e obrigatoriedade moram no template: são a regra, e
            // não algo que se edite por peça. Sem isto, mudar o template só
            // valia para registros novos — reordenar a lista deixava os
            // chamamentos antigos embaralhados, com metade na ordem velha.
            $metadados = collect(['rotulo', 'ordem', 'obrigatorio'])
                ->mapWithKeys(fn ($campo) => [$campo => $novos[$campo]])
                // Comparação frouxa de propósito: `ordem` volta do banco como
                // string em alguns drivers, e `===` reescreveria a linha a cada
                // acesso à tela.
                ->reject(fn ($valor, $campo) => $peca->{$campo} == $valor)
                ->all();

            if (!$peca->wasRecentlyCreated && $metadados) {
                $peca->update($metadados);
            }

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

            // O modelo semeado não conta como preenchimento: é o texto em
            // branco que o sistema põe, não algo que alguém escreveu.
            self::ligarAoPlanejamento($peca, $pecaable, array_filter([$texto, $bruto]));
        }
    }

    /**
     * Aponta o item do checklist para o documento que o Planejamento já fez.
     *
     * Só quando o item ainda está intocado: se alguém digitou, anexou ou
     * assinou aqui, esse trabalho manda — apontar para o processo o esconderia
     * da tela sem aviso. E só quando o documento de lá está pronto de fato
     * (assinado, ou com anexo, conforme o tipo), para a Seleção não exibir um
     * espaço vazio como se fosse peça cumprida.
     */
    private static function ligarAoPlanejamento(self $peca, Model $pecaable, array $modelos = []): void
    {
        if ($peca->origem_processo_peca_id
            || !$pecaable instanceof Chamamento
            || !$pecaable->processo_id
        ) {
            return;
        }

        $tipo = self::ORIGEM_PLANEJAMENTO[$peca->categoria][$peca->chave] ?? null;

        if (!$tipo || !self::intocada($peca, $modelos)) {
            return;
        }

        $origem = $pecaable->processo?->pecas->firstWhere('tipo', $tipo);

        if (!$origem || !self::origemEstaPronta($peca, $origem)) {
            return;
        }

        $peca->update(['origem_processo_peca_id' => $origem->id]);
    }

    /**
     * Ninguém mexeu neste item ainda?
     *
     * Texto em branco não é o único estado "intocado": as peças de modelo
     * nascem com o texto-padrão do sistema já dentro. Medir por `conteudo`
     * vazio deixava justamente o Edital e o Parecer Jurídico de fora da
     * herança — os dois que mais interessavam — porque o modelo semeado
     * passava por trabalho de alguém.
     */
    private static function intocada(self $peca, array $modelos): bool
    {
        if ($peca->arquivo_path || $peca->assinado()) {
            return false;
        }

        return empty($peca->conteudo) || in_array($peca->conteudo, $modelos, true);
    }

    /**
     * O documento do Planejamento está pronto para valer por este item?
     *
     * Quem decide é o tipo do ITEM, não o da origem — e é o que faz o mapa
     * funcionar sem exceções. O item de modelo herda o texto e exige que ele
     * esteja assinado; o de arquivo herda os anexos e exige que exista ao menos
     * um. É assim que "Edital" e "Anexos" apontam para a mesma peça do processo
     * e ainda assim mostram coisas diferentes: o texto num, os arquivos no outro.
     */
    private static function origemEstaPronta(self $peca, ProcessoPeca $origem): bool
    {
        return $peca->tipo === 'modelo' ? $origem->assinado() : $origem->temAnexo();
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
    /**
     * Os mesmos marcadores usados ao semear, disponíveis a quem regera um
     * documento fora do momento da criação — a Proposta de Alteração, por
     * exemplo, que acompanha os campos enquanto não for assinada.
     */
    public static function tokensPara(Model $pecaable): array
    {
        return self::tokensDe($pecaable);
    }

    private static function tokensDe(Model $pecaable): array
    {
        $osc = $instrumento = $orgao = $processo = null;
        $cadastro = null;

        if ($pecaable instanceof Proposta) {
            $cadastro    = $pecaable->osc;
            $osc         = $pecaable->osc?->name;
            $instrumento = $pecaable->instrumento?->numero;
            $orgao       = $pecaable->chamamento?->programa?->orgao?->name;
            $processo    = $pecaable->chamamento?->processo?->numero;
        } elseif ($pecaable instanceof Chamamento) {
            $orgao    = $pecaable->programa?->orgao?->name;
            $processo = $pecaable->processo?->numero;
        } elseif ($pecaable instanceof PrestacaoContas) {
            $proposta    = $pecaable->instrumento?->proposta;
            $cadastro    = $proposta?->osc;
            $osc         = $cadastro?->name;
            $instrumento = $pecaable->instrumento?->numero;
            $orgao       = $proposta?->chamamento?->programa?->orgao?->name;
            $processo    = $proposta?->chamamento?->processo?->numero;
        } elseif ($pecaable instanceof Aditivo) {
            $proposta    = $pecaable->instrumento?->proposta;
            $osc         = $proposta?->osc?->name;
            $instrumento = $pecaable->instrumento?->numero;
            $orgao       = $proposta?->chamamento?->programa?->orgao?->name;
        } elseif ($pecaable instanceof Alteracao) {
            $proposta      = $pecaable->proposta();
            $cadastro      = $proposta?->osc;
            $osc           = $cadastro?->name;
            $instrumento   = $pecaable->instrumento?->numero;
            $orgao         = $proposta?->chamamento?->programa?->orgao?->name;
            $processo      = $proposta?->chamamento?->processo?->numero;
            $daAlteracao   = [
                'alteracao_titulo'        => $pecaable->titulo,
                'alteracao_descricao'     => $pecaable->descricao,
                'alteracao_justificativa' => $pecaable->justificativa,
                'instrumento_tipo'        => Instrumento::TIPOS[$pecaable->instrumento?->tipo] ?? 'Termo',
            ];
        }

        $daAlteracao = $daAlteracao ?? [];

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
            'data_extenso'     => now()->locale('pt_BR')->translatedFormat('j \\d\\e F \\d\\e Y'),
        ];

        // Dados do cadastro da OSC — é deles que as declarações da habilitação
        // se preenchem. Só a Proposta tem OSC; nos demais donos ficam "XXXXX".
        $tokens += self::tokensDaOsc($cadastro);

        // Campos que só a alteração tem (título, descrição e justificativa do
        // pedido) — é deles que a Proposta de Alteração se escreve sozinha.
        $tokens += $daAlteracao;

        return array_map(fn ($v) => filled($v) ? $v : 'XXXXX', $tokens);
    }

    /**
     * Marcadores do cadastro da OSC e do seu representante legal.
     *
     * O tempo de existência vem da data de abertura do CNPJ, e sai também por
     * extenso porque a declaração do art. 34 o pede assim ("ativo há 12 (doze)
     * anos"). Endereço sai inteiro (logradouro, número e complemento) para as
     * declarações que trazem a sede numa lacuna só, e por partes para a do art.
     * 34, que separa o número.
     */
    private static function tokensDaOsc(?Osc $osc): array
    {
        $anos = $osc?->data_abertura ? (int) $osc->data_abertura->diffInYears(now()) : null;

        $endereco = collect([
            $osc?->logradouro,
            filled($osc?->numero) ? 'nº ' . $osc->numero : null,
            $osc?->complemento,
        ])->filter(fn ($parte) => filled($parte))->implode(', ');

        return [
            'osc_nome'          => $osc?->name,
            'osc_cnpj'          => $osc?->cnpj,
            'osc_endereco'      => $endereco,
            'osc_logradouro'    => $osc?->logradouro,
            'osc_numero'        => $osc?->numero,
            'osc_bairro'        => $osc?->bairro,
            'osc_cep'           => $osc?->cep,
            'osc_cidade'        => $osc?->cidade,
            'osc_uf'            => $osc?->estado,
            'osc_anos'          => $anos,
            'osc_anos_extenso'  => $anos === null ? null : \App\Support\Extenso::inteiro($anos),
            'rep_nome'          => $osc?->resp_nome,
            'rep_cpf'           => $osc?->resp_cpf,
            'rep_rg'            => $osc?->resp_rg,
            'rep_rg_orgao'      => $osc?->resp_rg_orgao,
        ];
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
