<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Alteração da Parceria: a OSC monta e assina, a UG autoriza, a SCP processa. Durante o pedido
 * a OSC edita o plano; o retrato de antes fica em plano_antes.
 */
class Alteracao extends Model
{
    protected $table = 'alteracoes';

    protected $fillable = [
        'instrumento_id', 'numero', 'titulo', 'descricao', 'justificativa',
        'status', 'setor_atual', 'etapa', 'plano_antes',
        'criada_por', 'enviada_em', 'decisao_motivo', 'decidida_por', 'decidida_em',
    ];

    protected function casts(): array
    {
        return [
            'plano_antes'   => 'array',
            'enviada_em'    => 'datetime',
            'decidida_em'   => 'datetime',
            'etapa'         => 'integer',
        ];
    }

    public const STATUS = [
        'rascunho'   => 'Em elaboração pela OSC',
        'em_analise' => 'Em análise',
        'aprovada'   => 'Aprovada',
        'indeferida' => 'Indeferida',
    ];

    public const STATUS_COLORS = [
        'rascunho'   => 'gray',
        'em_analise' => 'accent',
        'aprovada'   => 'brand',
        'indeferida' => 'red',
    ];

    public const SETORES = [
        'osc' => 'Organização da Sociedade Civil',
        'ug'  => 'Unidade Gestora',
        'scp' => 'Setor de Convênios e Parcerias (SCP)',
    ];

    /** Fluxograma da fase de execução, parte das alterações. */
    public const ETAPAS = [
        ['setor' => 'osc', 'acao' => 'Elaborar o pedido, instruir o checklist e assinar'],
        ['setor' => 'ug',  'acao' => 'Analisar e autorizar a alteração'],
        ['setor' => 'scp', 'acao' => 'Analisar a documentação e processar a alteração'],
    ];

    // ------------------------------------------------------------------ elos

    public function instrumento(): BelongsTo
    {
        return $this->belongsTo(Instrumento::class);
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criada_por');
    }

    public function decididaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decidida_por');
    }

    public function tramitacoes(): HasMany
    {
        return $this->hasMany(AlteracaoTramitacao::class)->orderBy('enviado_em');
    }

    public function pecas(): MorphMany
    {
        return $this->morphMany(Peca::class, 'pecaable')->chaperone('pecaable')->orderBy('ordem');
    }

    public function proposta(): ?Proposta
    {
        return $this->instrumento?->proposta;
    }

    public function osc(): ?Osc
    {
        return $this->proposta()?->osc;
    }

    public function orgao(): ?Orgao
    {
        return $this->proposta()?->chamamento?->programa?->orgao;
    }

    // ---------------------------------------------------------------- estado

    public function emElaboracao(): bool
    {
        return $this->status === 'rascunho';
    }

    public function decidida(): bool
    {
        return in_array($this->status, ['aprovada', 'indeferida'], true);
    }

    public function statusLabel(): string
    {
        return self::STATUS[$this->status] ?? $this->status;
    }

    /** Alterações que ainda ocupam alguém — é o selo "em andamento" do portal. */
    public function scopeEmAndamento($query)
    {
        return $query->whereIn('status', ['rascunho', 'em_analise']);
    }

    // ------------------------------------------------------------- checklist

    /** Peças obrigatórias da etapa atual que ainda faltam. */
    public function pendencias(): array
    {
        $pend = [];

        foreach ($this->pecas as $peca) {
            if (!$peca->obrigatorio || $peca->selecaoEtapa() !== (int) $this->etapa) {
                continue;
            }

            if ($peca->tipo === 'modelo' && !$peca->assinado()) {
                $pend[] = $peca->rotulo . ' (assinar)';
            } elseif ($peca->tipo === 'arquivo' && (!$peca->temArquivo() || $peca->devolvida())) {
                $pend[] = $peca->rotulo . ($peca->devolvida() ? ' (devolvido — enviar o arquivo corrigido)' : ' (anexar arquivo)');
            }
        }

        return $pend;
    }

    /** O que mudou no plano desde a abertura do pedido (compara com plano_antes). */
    public function mudancasNoPlano(): array
    {
        $antes = $this->plano_antes;
        if (!$antes || !($p = $this->proposta())) {
            return [];
        }

        $agora = self::retratoDoPlano($p);
        $linhas = [];

        foreach ([
            'valor_solicitado' => 'Valor solicitado ao município',
            'valor_proprio'    => 'Contrapartida da OSC',
            'aplicacao'        => 'Total do plano de aplicação',
            'desembolso'       => 'Total do cronograma de desembolso',
        ] as $chave => $rotulo) {
            $de   = (float) ($antes[$chave] ?? 0);
            $para = (float) ($agora[$chave] ?? 0);
            if (abs($de - $para) > 0.01) {
                $linhas[] = ['rotulo' => $rotulo, 'de' => $de, 'para' => $para];
            }
        }

        foreach (['metas' => 'Quantidade de metas', 'itens' => 'Itens do plano de aplicação'] as $chave => $rotulo) {
            if (($antes[$chave] ?? null) !== ($agora[$chave] ?? null)) {
                $linhas[] = ['rotulo' => $rotulo, 'de' => $antes[$chave] ?? 0, 'para' => $agora[$chave] ?? 0, 'contagem' => true];
            }
        }

        return $linhas;
    }

    /** O retrato que se guarda ao abrir o pedido. */
    public static function retratoDoPlano(Proposta $p): array
    {
        return [
            'valor_solicitado' => (float) $p->valor_solicitado,
            'valor_proprio'    => (float) $p->valor_proprio,
            'aplicacao'        => $p->totalPlanoAplicacao(),
            'desembolso'       => $p->totalDesembolso(),
            'metas'            => $p->metas()->count(),
            'itens'            => $p->planoItens()->count(),
        ];
    }

    // Interface uniforme de trâmite, usada pelo motor de peças (ver Peca).
    /** Documentos que quem devolve pode marcar como errados (ver App\Support\Devolucao). */
    public function documentosDevolviveis(): \Illuminate\Support\Collection
    {
        return \App\Support\Devolucao::candidatas($this->pecas()->get(), (int) $this->etapa);
    }

    public function tramiteEtapaAtual(): int
    {
        return (int) $this->etapa;
    }

    public function tramiteEtapas(): array
    {
        return self::ETAPAS;
    }

    public function tramiteEncerrado(): bool
    {
        return $this->decidida();
    }

    public function tramiteSetorLabel(?string $setor): string
    {
        return self::SETORES[$setor] ?? (User::LOTACOES[$setor] ?? strtoupper((string) $setor));
    }
}
