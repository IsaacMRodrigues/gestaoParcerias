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
     */
    public static function sincronizar(Model $pecaable, string $categoria): void
    {
        $template = self::TEMPLATES[$categoria] ?? [];

        foreach ($template as $i => $item) {
            $pecaable->pecas()->firstOrCreate(
                ['categoria' => $categoria, 'chave' => $item['chave']],
                [
                    'rotulo'      => $item['rotulo'],
                    'tipo'        => $item['tipo'],
                    'obrigatorio' => $item['obrigatorio'] ?? true,
                    'ordem'       => $i,
                ]
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
