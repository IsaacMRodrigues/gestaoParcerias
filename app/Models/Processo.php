<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Processo extends Model
{
    public const STATUS = [
        'em_planejamento' => 'Em Planejamento',
        'em_tramite'      => 'Em Trâmite',
        'concluido'       => 'Concluído (publicação)',
        'arquivado'       => 'Arquivado',
    ];

    public const STATUS_COLORS = [
        'em_planejamento' => 'gray',
        'em_tramite'      => 'yellow',
        'concluido'       => 'green',
        'arquivado'       => 'red',
    ];

    public const SETORES = [
        'ug'     => 'Unidade Gestora',
        'scp'    => 'Setor de Convênios e Parcerias (SCP)',
        'seplan' => 'Secretaria de Planejamento (SEPLAN)',
        'pj'     => 'Procuradoria Jurídica (PJ)',
    ];

    /**
     * Etapas do trâmite do planejamento (fluxo confirmado pelo cliente).
     * O índice da etapa é guardado na coluna `etapa`.
     */
    public const ETAPAS = [
        ['setor' => 'ug',     'acao' => 'Preencher Ofício e Termo de Referência e assinar'],
        ['setor' => 'scp',    'acao' => 'Receber e analisar o planejamento'],
        ['setor' => 'seplan', 'acao' => 'Analisar e emitir o Parecer Financeiro, e assinar'],
        ['setor' => 'ug',     'acao' => 'Conferir, resolver pendências e fazer a Abertura do Processo (assinar AP)'],
        ['setor' => 'scp',    'acao' => 'Elaborar o Edital (ou justificativa de dispensa/inexigibilidade)'],
        ['setor' => 'ug',     'acao' => 'Assinar o Edital'],
        ['setor' => 'scp',    'acao' => 'Publicar no site oficial (trâmite externo)'],
    ];

    public const AREAS_TEMATICAS = [
        'saude'                  => 'Saúde',
        'educacao'               => 'Educação',
        'assistencia_social'     => 'Assistência Social',
        'cultura'                => 'Cultura',
        'esporte'                => 'Esporte',
        'meio_ambiente'          => 'Meio Ambiente',
        'desenvolvimento_economico' => 'Desenvolvimento Econômico',
        'outra'                  => 'Outra',
    ];

    // Esfera do concedente — compõe o número do processo (UG.Seq.Ano.Esfera).
    public const ESFERAS = [
        '01' => 'Município',
        '02' => 'Estado',
        '03' => 'União',
        '04' => 'Outros',
    ];

    protected $fillable = [
        'numero', 'sequencial', 'esfera', 'orgao_id', 'created_by', 'status', 'setor_atual', 'etapa',
    ];

    public function orgao(): BelongsTo
    {
        return $this->belongsTo(Orgao::class);
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function termoReferencia(): HasOne
    {
        return $this->hasOne(TermoReferencia::class);
    }

    public function pecas(): HasMany
    {
        return $this->hasMany(ProcessoPeca::class);
    }

    public function peca(string $tipo): ?ProcessoPeca
    {
        return $this->pecas->firstWhere('tipo', $tipo);
    }

    public function tramitacoes(): HasMany
    {
        return $this->hasMany(Tramitacao::class)->orderBy('enviado_em');
    }

    public function tramitacaoAtual(): ?Tramitacao
    {
        return $this->tramitacoes()->whereNull('recebido_em')->latest('enviado_em')->first();
    }

    /**
     * Próximo número sequencial — contador contínuo e global (nunca reinicia).
     */
    public static function proximoSequencial(): int
    {
        return (static::max('sequencial') ?? 0) + 1;
    }

    /**
     * Monta o número do processo no formato UG.Sequencial.Ano.Esfera
     * (ex.: 0206.0133.2026.01).
     */
    public static function formatarNumero(string $codigoUg, int $sequencial, int $ano, string $esfera): string
    {
        return sprintf('%s.%04d.%04d.%s', $codigoUg, $sequencial, $ano, $esfera);
    }

    /**
     * Alertas automáticos de conformidade (🔴 / 🟢).
     */
    public function alertas(): array
    {
        $alertas = [];
        $tr = $this->termoReferencia;

        if (!$tr || !$tr->dotacao_orcamentaria) {
            $alertas[] = ['nivel' => 'erro', 'texto' => 'Não existe dotação orçamentária informada.'];
        }
        if ($tr && $tr->objeto_resumido && str_word_count($tr->objeto_resumido) < 5) {
            $alertas[] = ['nivel' => 'erro', 'texto' => 'Objeto genérico — descreva de forma específica e mensurável.'];
        }
        if (!$tr || !$tr->indicadores) {
            $alertas[] = ['nivel' => 'erro', 'texto' => 'Meta sem indicador definido.'];
        }
        if (!$tr || !$tr->justificativa) {
            $alertas[] = ['nivel' => 'erro', 'texto' => 'Ausência de justificativa.'];
        }
        if (!$tr || !$tr->valor_total) {
            $alertas[] = ['nivel' => 'erro', 'texto' => 'Valor total não informado.'];
        }

        if (empty($alertas)) {
            $alertas[] = ['nivel' => 'ok', 'texto' => 'Planejamento apto para abertura de processo.'];
        }

        return $alertas;
    }

    public function estaApto(): bool
    {
        foreach ($this->alertas() as $a) {
            if ($a['nivel'] === 'erro') {
                return false;
            }
        }
        return true;
    }

    // ----- Fluxo guiado -----

    public function etapaInfo(?int $i = null): array
    {
        $i = $i ?? $this->etapa;
        return self::ETAPAS[$i] ?? ['setor' => $this->setor_atual, 'acao' => '—'];
    }

    public function totalEtapas(): int
    {
        return count(self::ETAPAS);
    }

    public function ultimaEtapa(): bool
    {
        return $this->etapa >= $this->totalEtapas() - 1;
    }

    public function proximoSetor(): ?string
    {
        return self::ETAPAS[$this->etapa + 1]['setor'] ?? null;
    }

    public function setorAnterior(): ?string
    {
        return $this->etapa > 0 ? (self::ETAPAS[$this->etapa - 1]['setor'] ?? null) : null;
    }

    /**
     * Pode avançar da etapa atual? (os alertas de conformidade são consultivos,
     * não bloqueiam — a UG decide encaminhar; só não avança na última etapa)
     */
    public function podeAvancar(): bool
    {
        return !$this->ultimaEtapa();
    }

    /**
     * Peças que precisam estar ASSINADAS antes de encaminhar a etapa atual.
     * Retorna os rótulos pendentes (vazio = pode encaminhar).
     */
    public function pendenciasParaAvancar(): array
    {
        $pend = [];

        if ($this->etapa === 0) {
            if (!$this->peca('oficio')?->assinado())       $pend[] = 'Ofício';
            if (!$this->termoReferencia?->assinado())       $pend[] = 'Termo de Referência';
        } elseif ($this->etapa === 2) {
            if (!$this->peca('parecer_financeiro')?->assinado()) $pend[] = 'Parecer Financeiro';
        } elseif ($this->etapa === 3) {
            if (!$this->peca('abertura')?->assinado())      $pend[] = 'Abertura de Processo';
        }

        return $pend;
    }
}
