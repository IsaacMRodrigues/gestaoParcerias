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
        'assinado_por', 'assinado_em',
    ];

    protected function casts(): array
    {
        return [
            'obrigatorio' => 'boolean',
            'assinado_em' => 'datetime',
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
            ['chave' => 'resultado_parcial',         'rotulo' => 'Resultado parcial',                       'tipo' => 'arquivo', 'obrigatorio' => false],
            ['chave' => 'pub_resultado_parcial',     'rotulo' => 'Publicação do resultado parcial',         'tipo' => 'arquivo', 'obrigatorio' => false],
            ['chave' => 'recursos',                  'rotulo' => 'Recursos',                                'tipo' => 'arquivo', 'obrigatorio' => false],
            ['chave' => 'resultado_definitivo',      'rotulo' => 'Resultado definitivo',                    'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'pub_resultado_definitivo',  'rotulo' => 'Publicação do resultado definitivo',      'tipo' => 'arquivo', 'obrigatorio' => true],
            ['chave' => 'termo_homologacao',         'rotulo' => 'Termo de homologação',                    'tipo' => 'arquivo', 'obrigatorio' => true],
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

    public const CATEGORIA_LABELS = [
        'chamamento_publico'       => 'Chamamento Público',
        'dispensa_inexigibilidade' => 'Dispensa / Inexigibilidade',
        'apostilamento'            => 'Apostilamento',
        'aditivo'                  => 'Termo Aditivo',
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

    /**
     * Texto-modelo pré-preenchido das peças "modelo" da Seleção/Documentação
     * (semeado no `sincronizar`). Chaveado por categoria → chave. A Seleção usa
     * textarea simples (sem editor rico), então o conteúdo é texto puro editável.
     *
     * Fontes: Modelos oficiais I–XI do cliente + esqueleto do Edital (exemplo 005/2025).
     * Obs.: o arquivo "VI Parecer Jurídico" é idêntico ao XI (protocolo/solicitação);
     * o parecer jurídico em si usa o esqueleto já adotado no trâmite (ProcessoPeca).
     */
    public const MODELO = [
        'chamamento_publico' => [
            'edital' => <<<'TXT'
EDITAL DE CHAMAMENTO PÚBLICO Nº XXX/20XX

O MUNICÍPIO DE SÃO GONÇALO DO RIO ABAIXO, por intermédio da Secretaria Municipal de XXXXXX, torna público o presente Edital visando a seleção de Organização da Sociedade Civil interessada em celebrar TERMO DE XXXXXX, nos termos da Lei Federal nº 13.019/2014, do Decreto Municipal nº 048/2020 e demais normas aplicáveis.

CAPÍTULO I — DO OBJETO
1.1. Constitui objeto do presente Edital a seleção de OSC interessada em celebrar Termo de XXXXXX que tenha por objeto XXXXXXXXXX.
1.1.1. O procedimento reger-se-á pela Lei nº 13.019/2014, Decreto Municipal nº 048/2020 e pelas condições deste Edital.

CAPÍTULO II — DOS RECURSOS
2.1. Será destinado o valor total de até R$ XXXXXXXX (XXXXXXXX), conforme disponibilidade orçamentária e financeira.
2.2. Dotação: XXXXXXXXXX  Ficha: XXXX  Fonte: XXXX

CAPÍTULO III — DAS CONDIÇÕES DE PARTICIPAÇÃO
3.1. Poderão participar as OSCs que atendam aos requisitos da Lei nº 13.019/2014 e apresentem a documentação de habilitação exigida neste Edital.

CAPÍTULO IV — DO PRAZO E DA FORMA DE APRESENTAÇÃO DAS PROPOSTAS
4.1. As propostas deverão ser apresentadas no período de XX/XX/XXXX a XX/XX/XXXX, na forma indicada neste Edital.

CAPÍTULO V — DA COMISSÃO DE SELEÇÃO
5.1. A análise e o julgamento das propostas serão realizados pela Comissão de Seleção designada por portaria.

CAPÍTULO VI — DOS CRITÉRIOS DE JULGAMENTO
6.1. As propostas serão avaliadas conforme os critérios e pontuações previstos neste Edital e anexos.

CAPÍTULO VII — DOS RECURSOS
7.1. Caberá recurso nos prazos e formas previstos na legislação aplicável e neste Edital.

CAPÍTULO VIII — DA HOMOLOGAÇÃO E DA CELEBRAÇÃO
8.1. Homologado o resultado, a OSC selecionada será convocada para celebração do Termo, observadas as exigências legais.

São Gonçalo do Rio Abaixo/MG, XX de XXXX de 20XX.

XXXXXXXXXX
Secretária Municipal de XXXXXX
Unidade Gestora
TXT,
            'parecer_juridico' => <<<'TXT'
PARECER JURÍDICO Nº XXX/20XX

PROCESSO: XXXXXXXX
INTERESSADO: Secretaria Municipal de XXXXXX — Unidade Gestora
ASSUNTO: Análise jurídica da regularidade do procedimento e da minuta do termo (Lei Federal nº 13.019/2014).

I — RELATÓRIO
XXXXXXXXXXXX

II — FUNDAMENTAÇÃO
XXXXXXXXXXXX

III — CONCLUSÃO
Ante o exposto, esta Procuradoria opina pela XXXXXXXX (regularidade jurídica) do feito, podendo o processo prosseguir.

XXXXXXXXXX
Procurador(a) do Município
Procuradoria Jurídica
TXT,
        ],

        'dispensa_inexigibilidade' => [
            'justificativa' => <<<'TXT'
JUSTIFICATIVA PARA INEXIGIBILIDADE OU DISPENSA
(art. 32 da Lei nº 13.019/2014)

São Gonçalo do Rio Abaixo, XX de XXXX de 20XX.

ÓRGÃO RESPONSÁVEL: Secretaria Municipal de XXXXXX
OSC: XXXXXXXXXX
DOTAÇÃO ORÇAMENTÁRIA: XXXX  Ficha XXXX  Fonte XXXX
DURAÇÃO: XX meses
OBJETO DA PARCERIA: XXXXXXXXXX.

1. DESCRIÇÃO DA REALIDADE OBJETO DA PARCERIA
XXXXXXXXXX.

2. JUSTIFICATIVA
Considerando que a Lei Federal 13.019/2014 estabeleceu o regime jurídico das parcerias entre a Administração Pública e as Organizações da Sociedade Civil, tendo como regra geral o Chamamento Público;
Considerando o Decreto Municipal 048/2020, que regulamenta a Lei nº 13.019/2014 no âmbito do município;
Considerando que o art. 30, VI, da Lei nº 13.019/2014 prevê a dispensa de Chamamento Público no caso de atividades de educação, saúde e assistência social executadas por OSC previamente credenciadas pelo órgão gestor da respectiva política;
Considerando que a OSC atende aos critérios do art. 2º, I, da Lei 13.019/2014 e apresentou os documentos exigidos;
Diante do exposto, entendemos haver justificativa válida, idônea e de interesse público para a celebração de Termo de XXXXXX por XXXXXX de Chamamento Público, conforme art. 30, VI, da Lei nº 13.019/2014.

XXXXXXXXXX
Secretária Municipal de XXXXXX
Unidade Gestora
TXT,
            'parecer_tecnico_cnas' => <<<'TXT'
PARECER TÉCNICO
(Art. 3º, §2º, II da Resolução nº 21/2016 - CNAS)

ÓRGÃO RESPONSÁVEL: Secretaria Municipal de XXXXXX
OSC: XXXXXXXXXX
OBJETO DA PARCERIA: XXXXXXXXXX.

O presente parecer foi elaborado observando o disposto na Resolução nº 21/2016 - CNAS, que trata dos requisitos para a dispensa de chamamento público de OSC.

A OSC oferece serviço nos moldes da Política Nacional de Assistência Social e da Tipificação Nacional de Serviços Socioassistenciais (Resolução CNAS nº 109/2009), enquadrando-se na proteção social XXXXXX.

Destaca-se que a OSC é credenciada na Secretaria Municipal de Trabalho e Desenvolvimento Social e no Conselho Municipal de Assistência Social, e que o Município não possui outros serviços socioassistenciais voltados a XXXXXXXXXX.

Diante do exposto, conclui-se que as atividades exercidas pela OSC não podem ser interrompidas, tendo em vista que a descontinuidade da oferta apresenta dano mais gravoso à integridade do usuário.

XXXXXXXXXX
Assistente Social
Secretaria Municipal de XXXXXX
TXT,
            'parecer_tecnico_celebracao' => <<<'TXT'
PARECER TÉCNICO PARA CELEBRAÇÃO DA PARCERIA

A Secretaria Municipal de XXXXXX, com base no que estabelece o inciso V do art. 35 da Lei 13.019/2014, referente à parceria a ser firmada entre o Município de São Gonçalo do Rio Abaixo e a OSC XXXXXXXXXX, conforme o processo nº XXXXXXXX, que tem por objeto XXXXXXXXXX, vem por meio deste parecer se pronunciar de forma expressa sobre os pontos abaixo:

a) Quanto ao mérito do plano de trabalho, em conformidade com a modalidade de parceria adotada: FAVORÁVEL.
b) Quanto à identidade e reciprocidade de interesse das partes: FAVORÁVEL.
c) Quanto à viabilidade de execução: FAVORÁVEL.
d) Quanto ao cronograma de desembolso: FAVORÁVEL.
e) Quanto aos meios de fiscalização e à avaliação da execução física e financeira: FAVORÁVEL.
f) Quanto à designação do gestor da parceria: FAVORÁVEL.
g) Quanto à designação da comissão de monitoramento e avaliação: FAVORÁVEL.
h) Quanto às condições de funcionamento da instituição (art. 17 da Lei 4.320/1964): FAVORÁVEL.

Com base no exposto, o parecer é de que a celebração da parceria é possível.

São Gonçalo do Rio Abaixo, XX de XXXX de 20XX.

XXXXXXXXXX
Secretária de XXXXXX
Unidade Gestora
TXT,
            'certidao_autuacao' => <<<'TXT'
CERTIDÃO DE AUTUAÇÃO

Ao(s) XX dia(s) do mês de XXXX de 20XX, eu, XXXXXXXXXX, do Setor de Convênios e Parcerias, autuei os documentos abaixo relacionados, referentes ao processo nº XXXXXXXX (Termo de XXXXXX), por intermédio da Secretaria Municipal de XXXXXX, que me foram apresentados:

- Manifestação de interesse da Unidade Gestora;
- Reserva de dotação (parecer de viabilidade orçamentária);
- Abertura do processo;
- Plano de trabalho e aplicação de recurso;
- Aprovação do plano de trabalho;
- Documentos de habilitação (certidões, declarações, estatuto e alterações registradas, ata da diretoria, relação de dirigentes, RG/CPF e comprovante de endereço do representante legal);
- Portaria do Gestor;
- Portaria da Comissão de Monitoramento e Avaliação;
- Parecer da Unidade Gestora;
- Minuta do Termo.

São Gonçalo do Rio Abaixo - MG, XX de XXXX de 20XX.

XXXXXXXXXX
Setor de Convênios e Parcerias
TXT,
            'protocolo_juridico' => <<<'TXT'
São Gonçalo do Rio Abaixo, XX de XXXX de 20XX.

A/C Procuradoria Jurídica Municipal

Venho por meio deste solicitar parecer jurídico acerca da possibilidade de XXXXXXXXXX, referente ao processo nº XXXXXXXX, conforme estabelece a Lei Federal nº 13.019/2014.

Também envolve a análise da minuta do termo, que segue em anexo.

Sendo o que temos para o momento, desde já agradecemos.

XXXXXXXXXX
Secretaria Municipal de XXXXXX
Unidade Gestora
TXT,
            'parecer_juridico' => <<<'TXT'
PARECER JURÍDICO Nº XXX/20XX

PROCESSO: XXXXXXXX
INTERESSADO: Secretaria Municipal de XXXXXX — Unidade Gestora
ASSUNTO: Análise jurídica da regularidade do procedimento e da minuta do termo (Lei Federal nº 13.019/2014) — Dispensa/Inexigibilidade.

I — RELATÓRIO
XXXXXXXXXXXX

II — FUNDAMENTAÇÃO
XXXXXXXXXXXX

III — CONCLUSÃO
Ante o exposto, esta Procuradoria opina pela XXXXXXXX (regularidade jurídica) do feito, podendo o processo prosseguir para a celebração.

XXXXXXXXXX
Procurador(a) do Município
Procuradoria Jurídica
TXT,
        ],

        'aditivo' => [
            'parecer_financeiro' => <<<'TXT'
Nº XXX/20XX

ORIGEM: Planejamento
ASSUNTO: Dotação orçamentária e impacto financeiro (Termo Aditivo)
DATA: XX/XX/XXXX

A Secretaria Municipal de Planejamento, após análise, informa à Unidade Gestora que há previsão orçamentária e financeira na Lei Orçamentária Anual para o aditamento da parceria "XXXXXXXX".

Previsão da Despesa:
Ano | Secretaria Municipal | Dotação | Recurso | Ficha | Desdobrada | Valor
XXX | XXX | XXXXX | XXXX | XXXX | XXXXX | XXXXX

Valor da Receita | Despesa Prevista | Impacto | Valor Total
XXXXX | XXXXX | XXXX | XXXXX

A estimativa do Impacto Orçamentário Financeiro para realização da despesa prevista no Exercício 20XX é de XXX% das receitas orçadas na Lei Orçamentária Anual nº XXXXX.

Sendo só no momento, me coloco à disposição para quaisquer eventuais esclarecimentos.

XXXXXXXXXX
Secretário Municipal de Planejamento
TXT,
            'certidao_autuacao' => <<<'TXT'
CERTIDÃO DE AUTUAÇÃO

Ao(s) XX dia(s) do mês de XXXX de 20XX, eu, XXXXXXXXXX, do Setor de Convênios e Parcerias, autuei os documentos abaixo relacionados ao Termo Aditivo do processo nº XXXXXXXX (Termo de XXXXXX), por intermédio da Secretaria Municipal de XXXXXX, que me foram apresentados:

- Manifestação da OSC;
- Justificativa Técnica da OSC;
- Certidões de regularidade atualizadas;
- Plano de Trabalho atualizado;
- Aprovação da alteração do plano de trabalho;
- Parecer financeiro;
- Justificativa da Unidade Gestora;
- Autorização da Unidade Gestora;
- Minuta do Termo Aditivo.

São Gonçalo do Rio Abaixo - MG, XX de XXXX de 20XX.

XXXXXXXXXX
Setor de Convênios e Parcerias
TXT,
            'protocolo_juridico' => <<<'TXT'
São Gonçalo do Rio Abaixo, XX de XXXX de 20XX.

A/C Procuradoria Jurídica Municipal

Venho por meio deste solicitar parecer jurídico acerca da possibilidade de aditamento, referente ao processo nº XXXXXXXX, conforme estabelece a Lei Federal nº 13.019/2014.

Também envolve a análise da minuta do termo aditivo, que segue em anexo.

Sendo o que temos para o momento, desde já agradecemos.

XXXXXXXXXX
Secretaria Municipal de XXXXXX
Unidade Gestora
TXT,
            'parecer_juridico' => <<<'TXT'
PARECER JURÍDICO Nº XXX/20XX

PROCESSO: XXXXXXXX
INTERESSADO: Secretaria Municipal de XXXXXX — Unidade Gestora
ASSUNTO: Análise jurídica do Termo Aditivo e da minuta (Lei Federal nº 13.019/2014).

I — RELATÓRIO
XXXXXXXXXXXX

II — FUNDAMENTAÇÃO
XXXXXXXXXXXX

III — CONCLUSÃO
Ante o exposto, esta Procuradoria opina pela XXXXXXXX (regularidade jurídica) do aditamento, podendo o processo prosseguir para a assinatura e publicação.

XXXXXXXXXX
Procurador(a) do Município
Procuradoria Jurídica
TXT,
        ],
    ];

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

    public function tamanhoFormatado(): string
    {
        if (!$this->tamanho) return '—';
        $kb = $this->tamanho / 1024;
        return $kb > 1024 ? number_format($kb / 1024, 1) . ' MB' : number_format($kb, 0) . ' KB';
    }

    /**
     * Cria (idempotente) as peças de uma categoria para um registro a partir do template.
     * $relacao permite apontar para uma relação morphMany diferente de `pecas()`
     * (o Processo já usa `pecas()` para as peças do trâmite, então a Seleção
     * polimórfica vive em `pecasSelecao()`).
     */
    public static function sincronizar(Model $pecaable, string $categoria, string $relacao = 'pecas'): void
    {
        $template = self::TEMPLATES[$categoria] ?? [];

        foreach ($template as $i => $item) {
            $novos = [
                'rotulo'      => $item['rotulo'],
                'tipo'        => $item['tipo'],
                'obrigatorio' => $item['obrigatorio'] ?? true,
                'ordem'       => $i,
            ];

            // semeia o texto-modelo das peças "modelo" que possuem template
            if (($item['tipo'] ?? null) === 'modelo' && isset(self::MODELO[$categoria][$item['chave']])) {
                $novos['conteudo'] = self::MODELO[$categoria][$item['chave']];
            }

            $pecaable->{$relacao}()->firstOrCreate(
                ['categoria' => $categoria, 'chave' => $item['chave']],
                $novos
            );
        }
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
