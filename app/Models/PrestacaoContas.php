<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

/**
 * Prestação de contas de uma parceria (módulo 3.4).
 *
 * Parcial ou final, sempre de um período. O que a OSC preenche são campos, não
 * texto: os totais, o saldo e o líquido saem daqui calculados, como pediu a
 * cliente ao mandar "colocar fórmula" nas planilhas.
 *
 * Nada que a Execução já registrou é digitado de novo — os repasses e as
 * despesas do período vêm de lá, e as metas, do Plano de Trabalho.
 */
class PrestacaoContas extends Model
{
    use HasFactory;

    protected $table = 'prestacoes_contas';

    protected $fillable = [
        'instrumento_id', 'tipo', 'numero', 'periodo_inicio', 'periodo_fim', 'parcelas_recebidas',
        'folhas', 'responsavel_nome', 'responsavel_email', 'responsavel_telefone',
        'objetivo_geral', 'objetivos_especificos', 'conclusao',
        'banco', 'agencia', 'conta_corrente',
        'saldo_anterior', 'outros_creditos', 'recursos_proprios', 'despesas_bancarias', 'valor_ressarcido',
        'folha_funcionarios', 'folha_salarios', 'folha_vantagens', 'folha_adicionais',
        'folha_inss', 'folha_irrf', 'folha_plano_saude', 'folha_fgts', 'folha_ferias', 'folha_rescisao',
        'etapa', 'setor', 'iniciada_em', 'concluida_em', 'created_by',
    ];

    public const TIPOS = ['parcial' => 'Parcial', 'final' => 'Final'];

    public const SETORES = [
        'osc' => 'Organização da Sociedade Civil',
        'scp' => 'Setor de Convênios e Parcerias (SCP)',
        'ug'  => 'Unidade Gestora',
    ];

    /** Fluxo da fase de prestação de contas (módulo 3.4). */
    public const ETAPAS = [
        ['setor' => 'osc', 'acao' => 'Elaborar e enviar a prestação de contas'],
        ['setor' => 'scp', 'acao' => 'Analisar previamente e encaminhar à Unidade Gestora'],
        ['setor' => 'ug',  'acao' => 'Gestor da Parceria e Comissão de Monitoramento analisam e aprovam'],
    ];

    /**
     * Os quatro blocos do Relatório de Metas Financeiras (Anexo VI) e as
     * naturezas de despesa que caem em cada um.
     */
    public const BLOCOS = [
        'pessoal'      => ['rotulo' => '1. Pessoal',                         'naturezas' => ['recursos_humanos']],
        'encargos'     => ['rotulo' => '2. Encargos sociais e trabalhistas', 'naturezas' => ['encargos']],
        'manutencao'   => ['rotulo' => '3. Manutenção e outros',             'naturezas' => ['material_consumo', 'servicos_pf', 'servicos_pj', 'outros']],
        'equipamentos' => ['rotulo' => '4. Equipamentos e material perm.',   'naturezas' => ['equipamento']],
    ];

    protected function casts(): array
    {
        return [
            'periodo_inicio' => 'date',
            'periodo_fim'    => 'date',
            'iniciada_em'    => 'datetime',
            'concluida_em'   => 'datetime',
            'etapa'          => 'integer',
        ];
    }

    // ------------------------------------------------------------------ elos

    public function instrumento(): BelongsTo
    {
        return $this->belongsTo(Instrumento::class);
    }

    public function metas(): HasMany
    {
        return $this->hasMany(PrestacaoMeta::class, 'prestacao_id')->orderBy('ordem');
    }

    public function glosas(): HasMany
    {
        return $this->hasMany(PrestacaoGlosa::class, 'prestacao_id');
    }

    public function bens(): HasMany
    {
        return $this->hasMany(PrestacaoBem::class, 'prestacao_id');
    }

    public function tramitacoes(): HasMany
    {
        return $this->hasMany(PrestacaoTramitacao::class, 'prestacao_id')->orderBy('enviado_em');
    }

    public function pecas(): MorphMany
    {
        return $this->morphMany(Peca::class, 'pecaable')->orderBy('ordem');
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function osc(): ?Osc
    {
        return $this->instrumento?->proposta?->osc;
    }

    // ------------------------------------------------- o que vem da Execução

    /** Repasses recebidos dentro do período desta prestação. */
    public function repasses(): Collection
    {
        return $this->instrumento
            ? $this->instrumento->repasses()->whereBetween('data_repasse', [$this->periodo_inicio, $this->periodo_fim])->orderBy('data_repasse')->get()
            : collect();
    }

    /** Despesas lançadas dentro do período desta prestação. */
    public function despesas(): Collection
    {
        return $this->instrumento
            ? $this->instrumento->despesas()->whereBetween('data_despesa', [$this->periodo_inicio, $this->periodo_fim])->orderBy('data_despesa')->get()
            : collect();
    }

    // ------------------------------------------------------- Anexo IV: saldo

    public function totalRepassado(): float
    {
        return (float) $this->repasses()->sum('valor');
    }

    public function totalGasto(): float
    {
        return (float) $this->despesas()->sum('valor');
    }

    public function totalCreditos(): float
    {
        return $this->totalRepassado() + (float) $this->outros_creditos + (float) $this->recursos_proprios;
    }

    public function totalDebitos(): float
    {
        return $this->totalGasto() + (float) $this->despesas_bancarias;
    }

    /** Saldo do período: o que entrou menos o que saiu, menos o devolvido. */
    public function saldoAtual(): float
    {
        return (float) $this->saldo_anterior + $this->totalCreditos()
            - $this->totalDebitos() - (float) $this->valor_ressarcido;
    }

    // ------------------------------------ Anexo V: despesas por bloco e total

    public function blocoDaNatureza(string $natureza): string
    {
        foreach (self::BLOCOS as $chave => $bloco) {
            if (in_array($natureza, $bloco['naturezas'], true)) {
                return $chave;
            }
        }

        return 'manutencao';
    }

    /** Despesas do período agrupadas nos quatro blocos do Anexo VI. */
    public function despesasPorBloco(): Collection
    {
        return $this->despesas()->groupBy(fn (Despesa $d) => $this->blocoDaNatureza($d->natureza));
    }

    public function totalDoBloco(string $bloco): float
    {
        return (float) $this->despesasPorBloco()->get($bloco, collect())->sum('valor');
    }

    /** Anexo V separa a folha (pessoal e encargos) das demais despesas. */
    public function totalComPessoal(): float
    {
        return $this->totalDoBloco('pessoal') + $this->totalDoBloco('encargos');
    }

    public function totalDemaisDespesas(): float
    {
        return $this->totalGasto() - $this->totalComPessoal();
    }

    // ---------------------------------------------- Anexo VI: metas e glosas

    /** Glosa somada dos seis meses, por bloco. */
    public function glosaDoBloco(string $bloco): float
    {
        $g = $this->glosas->firstWhere('natureza', $bloco);

        return $g ? $g->totalGlosado() : 0.0;
    }

    public function totalGlosado(): float
    {
        return (float) $this->glosas->sum(fn (PrestacaoGlosa $g) => $g->totalGlosado());
    }

    /** Saldo por bloco: aprovado no Plano de Trabalho − executado − glosado. */
    public function saldoDoBloco(string $bloco): float
    {
        $aprovado = (float) ($this->glosas->firstWhere('natureza', $bloco)?->valor_aprovado ?? 0);

        return $aprovado - $this->totalDoBloco($bloco) - $this->glosaDoBloco($bloco);
    }

    // ------------------------------------------------- Anexo VII: bens móveis

    public function totalBens(): float
    {
        return (float) $this->bens->sum(fn (PrestacaoBem $b) => $b->total());
    }

    // ------------------------------------------- Anexo 16: folha de pagamento

    public function folhaProventos(): float
    {
        return (float) $this->folha_salarios + (float) $this->folha_vantagens + (float) $this->folha_adicionais;
    }

    public function folhaDescontos(): float
    {
        return (float) $this->folha_inss + (float) $this->folha_irrf + (float) $this->folha_plano_saude;
    }

    public function folhaLiquido(): float
    {
        return $this->folhaProventos() - $this->folhaDescontos();
    }

    public function folhaEncargos(): float
    {
        return (float) $this->folha_fgts + (float) $this->folha_ferias + (float) $this->folha_rescisao;
    }

    // ------------------------------------------------------------- trâmite

    public function rotulo(): string
    {
        $qual = $this->tipo === 'final' ? 'Final' : 'Parcial nº ' . ($this->numero ?: 1);

        return $qual . ' — ' . $this->periodo_inicio->format('d/m/Y') . ' a ' . $this->periodo_fim->format('d/m/Y');
    }

    public function concluida(): bool
    {
        return !is_null($this->concluida_em);
    }

    public function ultimaEtapa(): bool
    {
        return $this->etapa >= count(self::ETAPAS) - 1;
    }

    public function podeAvancar(): bool
    {
        return !$this->concluida() && !$this->ultimaEtapa();
    }

    /**
     * O que falta para encaminhar a etapa atual: as peças obrigatórias dela.
     * Mesma régua da Celebração — só a etapa corrente é cobrada.
     */
    public function pendencias(): array
    {
        $pend = [];

        foreach ($this->pecas as $peca) {
            if (!$peca->obrigatorio || ($peca->selecaoEtapa() !== $this->etapa)) {
                continue;
            }

            if ($peca->tipo === 'modelo' && !$peca->assinado()) {
                $pend[] = $peca->rotulo . ' (assinar)';
            } elseif ($peca->tipo === 'arquivo' && !$peca->temArquivo()) {
                $pend[] = $peca->rotulo . ' (anexar arquivo)';
            }
        }

        return $pend;
    }

    // Interface uniforme de trâmite, usada pelo motor de peças (ver Peca).
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
        return $this->concluida();
    }

    public function tramiteSetorLabel(?string $setor): string
    {
        return self::SETORES[$setor] ?? (User::LOTACOES[$setor] ?? strtoupper((string) $setor));
    }
}
