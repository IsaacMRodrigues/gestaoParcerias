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

    /**
     * A cor diz o estado, na paleta da Prefeitura:
     *   cinza → inerte · laranja → em andamento · verde → ativo ou concluído com êxito
     *   vermelho → desfecho negativo (fora da paleta: é sinal de alerta)
     */
    public const STATUS_COLORS = [
        'em_planejamento' => 'gray',
        'em_tramite'      => 'accent',
        'concluido'       => 'brand',
        'arquivado'       => 'red',
    ];

    public const SETORES = [
        'ug'     => 'Unidade Gestora',
        'scp'    => 'Setor de Convênios e Parcerias (SCP)',
        'seplan' => 'Secretaria de Planejamento (SEPLAN)',
        'pj'     => 'Procuradoria Jurídica (PJ)',
    ];

    /** Setores centrais, que processam processos de qualquer órgão (a UG só vê os do seu). */
    public const SETORES_CENTRAIS = ['scp', 'seplan', 'pj'];

    /**
     * Etapas do Planejamento (índice na coluna etapa). Na etapa 1 a SCP decide a modalidade:
     * Chamamento segue por ETAPAS; Dispensa/Inexigibilidade, por ETAPAS_DISPENSA. Use $processo->etapas().
     */
    public const ETAPAS = [
        ['setor' => 'ug',     'acao' => 'Preencher Memorando e Termo de Referência e assinar'],
        ['setor' => 'scp',    'acao' => 'Analisar o Memorando e o Termo de Referência: aprovar ou rejeitar', 'analise' => true],
        ['setor' => 'scp',    'acao' => 'Solicitar o Parecer Financeiro à SEPLAN (Pedido de Parecer)'],
        ['setor' => 'seplan', 'acao' => 'Emitir o Parecer Financeiro e assinar'],
        ['setor' => 'ug',     'acao' => 'Conferir o parecer e fazer a Abertura do Processo (assinar AP)'],
        ['setor' => 'scp',    'acao' => 'Elaborar o Edital e seus anexos'],
        ['setor' => 'ug',     'acao' => 'Revisar e assinar o Edital, anexar a Portaria da Comissão de Seleção e encaminhar à SCP'],
        ['setor' => 'scp',    'acao' => 'Emitir e assinar o Protocolo (Solicitação de Parecer Jurídico) e encaminhar à Procuradoria'],
        ['setor' => 'pj',     'acao' => 'Emitir o Parecer Jurídico e encaminhar à SCP (preencher e assinar)'],
        ['setor' => 'scp',    'acao' => 'Publicar no site oficial (trâmite externo)'],
    ];

    /** Rota da Dispensa/Inexigibilidade: etapas 0–4 iguais; da 5 em diante, a Justificativa da UG. */
    public const ETAPAS_DISPENSA = [
        ['setor' => 'ug',     'acao' => 'Preencher Memorando e Termo de Referência e assinar'],
        ['setor' => 'scp',    'acao' => 'Analisar o Memorando e o Termo de Referência: aprovar ou rejeitar', 'analise' => true],
        ['setor' => 'scp',    'acao' => 'Solicitar o Parecer Financeiro à SEPLAN (Pedido de Parecer)'],
        ['setor' => 'seplan', 'acao' => 'Emitir o Parecer Financeiro e assinar'],
        ['setor' => 'ug',     'acao' => 'Conferir o parecer e fazer a Abertura do Processo (assinar AP)'],
        ['setor' => 'ug',     'acao' => 'Emitir e assinar a Justificativa de Dispensa/Inexigibilidade (e, se parceria do SUAS, o Parecer Técnico CNAS)'],
        ['setor' => 'scp',    'acao' => 'Publicar a Justificativa no site oficial (trâmite externo)'],
    ];

    // Modalidade da seleção — decidida pelo SCP na etapa de análise; define o caminho do processo.
    public const MODALIDADES = [
        'chamamento_publico' => 'Chamamento Público',
        'dispensa'           => 'Dispensa de Chamamento Público',
        'inexigibilidade'    => 'Inexigibilidade de Chamamento Público',
    ];

    /** Cor da modalidade: Chamamento (via ordinária) verde; Dispensa laranja; Inexigibilidade cinza. */
    public const MODALIDADES_COLORS = [
        'chamamento_publico' => 'brand',
        'dispensa'           => 'accent',
        'inexigibilidade'    => 'slate',
    ];

    public const MODALIDADES_DESC = [
        'chamamento_publico' => 'Processo padrão de seleção (licitatório/competitivo) para firmar parcerias.',
        'dispensa'           => 'A lei autoriza a contratação direta, mesmo podendo haver mais de um interessado — situações de urgência ou casos específicos previstos no marco regulatório.',
        'inexigibilidade'    => 'Não há possibilidade de competição: a organização é a única que pode executar o objeto ou detém exclusividade.',
    ];

    // Esfera do concedente — compõe o número do processo (UG.Seq.Ano.Esfera).
    public const ESFERAS = [
        '01' => 'Município',
        '02' => 'Estado',
        '03' => 'União',
        '04' => 'Outros',
    ];

    protected $fillable = [
        'numero', 'sequencial', 'esfera', 'modalidade', 'orgao_id', 'created_by', 'status', 'setor_atual', 'etapa',
    ];

    public function orgao(): BelongsTo
    {
        return $this->belongsTo(Orgao::class);
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function pecas(): HasMany
    {
        return $this->hasMany(ProcessoPeca::class);
    }

    public function peca(string $tipo): ?ProcessoPeca
    {
        return $this->pecas->firstWhere('tipo', $tipo);
    }

    /** Chamamento gerado na conclusão do trâmite (publicação). */
    public function chamamento(): HasOne
    {
        return $this->hasOne(Chamamento::class);
    }

    /** Instrumentos desta parceria (Processo → Chamamento → Proposta → Instrumento); pode haver vários. */
    public function instrumentosDaParceria()
    {
        if (! $this->chamamento) {
            return collect();
        }

        return Instrumento::whereHas('proposta', fn ($q) => $q->where('chamamento_id', $this->chamamento->id))
            ->with('proposta.osc')
            ->orderByDesc('created_at')
            ->get();
    }

    public function tramitacoes(): HasMany
    {
        return $this->hasMany(Tramitacao::class)->orderBy('enviado_em');
    }

    public function tramitacaoAtual(): ?Tramitacao
    {
        // Com as tramitações já carregadas (a tela do processo as carrega para o histórico), sem nova consulta.
        if ($this->relationLoaded('tramitacoes')) {
            return $this->tramitacoes->whereNull('recebido_em')->sortByDesc('enviado_em')->first();
        }

        return $this->tramitacoes()->whereNull('recebido_em')->latest('enviado_em')->first();
    }

    /** Há um encaminhamento aguardando o setor registrar o recebimento? */
    public function aguardandoRecebimento(): bool
    {
        return $this->tramitacaoAtual() !== null;
    }

    /** Próximo número sequencial: contador contínuo e global (nunca reinicia). */
    public static function proximoSequencial(): int
    {
        return (static::max('sequencial') ?? 0) + 1;
    }

    /** Número do processo no formato UG.Sequencial.Ano.Esfera (ex.: 0206.0133.2026.01). */
    public static function formatarNumero(string $codigoUg, int $sequencial, int $ano, string $esfera): string
    {
        return sprintf('%s.%04d.%04d.%s', $codigoUg, $sequencial, $ano, $esfera);
    }

    /** Alertas automáticos de conformidade (🔴 / 🟢). */
    public function alertas(): array
    {
        $alertas = [];

        if (!$this->peca('oficio')?->assinado()) {
            $alertas[] = ['nivel' => 'erro', 'texto' => 'Memorando não preenchido/assinado.'];
        }
        if (!$this->peca('termo_referencia')?->assinado()) {
            $alertas[] = ['nivel' => 'erro', 'texto' => 'Termo de Referência não preenchido/assinado.'];
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

    /** A modalidade é dispensa ou inexigibilidade? (define a rota do trâmite) */
    public function ehDispensa(): bool
    {
        return in_array($this->modalidade, ['dispensa', 'inexigibilidade'], true);
    }

    /** Vê processos de todos os órgãos? Administrador, auditoria ou setor central (SCP/SEPLAN/PJ). */
    protected static function usuarioVeTodosProcessos(User $user): bool
    {
        return $user->podeVerTodosOrgaos()
            || in_array($user->setor, self::SETORES_CENTRAIS, true);
    }

    /** Restringe aos processos que o usuário pode ver (por órgão, exceto centrais). */
    public function scopeVisiveisPara($query, User $user)
    {
        if (self::usuarioVeTodosProcessos($user)) {
            return $query;
        }

        return $query->where('orgao_id', $user->orgao_id);
    }

    /** Este processo pode ser visto por este usuário? (autorização do show). */
    public function visivelPara(User $user): bool
    {
        return self::usuarioVeTodosProcessos($user)
            || $this->orgao_id === $user->orgao_id;
    }

    /** Sequência de etapas conforme a modalidade (chamamento por padrão). */
    public function etapas(): array
    {
        return $this->ehDispensa() ? self::ETAPAS_DISPENSA : self::ETAPAS;
    }

    public function etapaInfo(?int $i = null): array
    {
        $i = $i ?? $this->etapa;
        return $this->etapas()[$i] ?? ['setor' => $this->setor_atual, 'acao' => '—'];
    }

    /** A etapa atual é de análise (aprovar/rejeitar), sem documento próprio? */
    public function etapaEhAnalise(): bool
    {
        return (bool) ($this->etapaInfo()['analise'] ?? false);
    }

    public function totalEtapas(): int
    {
        return count($this->etapas());
    }

    public function ultimaEtapa(): bool
    {
        return $this->etapa >= $this->totalEtapas() - 1;
    }

    public function proximoSetor(): ?string
    {
        return $this->etapas()[$this->etapa + 1]['setor'] ?? null;
    }

    public function setorAnterior(): ?string
    {
        return $this->etapa > 0 ? ($this->etapas()[$this->etapa - 1]['setor'] ?? null) : null;
    }

    /**
     * A etapa seguinte é do mesmo setor? Então é continuação, não remessa
     * (ver TramitacaoController::chegadaNoProprioSetor).
     */
    public function segueNoMesmoSetor(): bool
    {
        return $this->proximoSetor() !== null && $this->proximoSetor() === $this->setor_atual;
    }

    /** A etapa anterior é do mesmo setor — a devolução não muda de mãos. */
    public function voltaNoMesmoSetor(): bool
    {
        return $this->setorAnterior() !== null && $this->setorAnterior() === $this->setor_atual;
    }

    /** Pode avançar? Os alertas são consultivos; só não avança na última etapa. */
    public function podeAvancar(): bool
    {
        return !$this->ultimaEtapa();
    }

    /** Documentos que quem devolve pode marcar como errados (ver App\Support\Devolucao). */
    public function documentosDevolviveis(): \Illuminate\Support\Collection
    {
        return \App\Support\Devolucao::candidatas($this->pecas()->get(), (int) $this->etapa);
    }

    /** Peças que precisam estar assinadas para encaminhar a etapa atual (vazio = pode encaminhar). */
    public function pendenciasParaAvancar(): array
    {
        $pend = [];
        $ehDispensa = $this->ehDispensa();

        if ($this->etapa === 0) {
            if (!$this->peca('oficio')?->assinado())             $pend[] = 'Memorando';
            if (!$this->peca('termo_referencia')?->assinado())   $pend[] = 'Termo de Referência';
        } elseif ($this->etapa === 2) {
            if (!$this->peca('pedido_parecer')?->assinado())      $pend[] = 'Pedido de Parecer Financeiro';
        } elseif ($this->etapa === 3) {
            if (!$this->peca('parecer_financeiro')?->assinado())  $pend[] = 'Parecer Financeiro';
        } elseif ($this->etapa === 4) {
            if (!$this->peca('abertura')?->assinado())            $pend[] = 'Termo de Abertura';
        } elseif ($this->etapa === 5) {
            if ($ehDispensa) {
                // Dispensa/Inexigibilidade: a UG emite e assina a Justificativa
                // (o Parecer Técnico CNAS é opcional — só nas parcerias do SUAS).
                if (!$this->peca('justificativa_dispensa')?->assinado()) $pend[] = 'Justificativa de Dispensa/Inexigibilidade';
            } else {
                $edital = $this->peca('edital');
                if (empty($edital?->conteudo) || $edital->devolvida()) $pend[] = 'Edital (elaborar' . ($edital?->devolvida() ? ' — devolvido para correção' : '') . ')';
            }
        } elseif ($this->etapa === 6 && !$ehDispensa) {
            if (!$this->peca('edital')?->assinado())              $pend[] = 'Edital (assinatura da UG)';
            $portaria = $this->peca('portaria_comissao');
            if (!$portaria?->temAnexo() || $portaria->devolvida()) $pend[] = 'Portaria da Comissão de Seleção (anexar arquivo' . ($portaria?->devolvida() ? ' corrigido' : '') . ')';
        } elseif ($this->etapa === 7 && !$ehDispensa) {
            if (!$this->peca('solicitacao_parecer_juridico')?->assinado()) $pend[] = 'Solicitação de Parecer Jurídico (Protocolo)';
        } elseif ($this->etapa === 8 && !$ehDispensa) {
            if (!$this->peca('parecer_juridico')?->assinado())    $pend[] = 'Parecer Jurídico';
        }
        // etapa 1 (SCP): apenas analisa e devolve — sem documento obrigatório
        // etapa final (SCP publica): sem pendência — segue para "Concluir"

        return $pend;
    }

    /** Gera (idempotente) o Chamamento a partir do Processo concluído: a ponte Planejamento → Seleção. */
    public function gerarChamamentoPublicacao(): Chamamento
    {
        if ($existente = $this->chamamento) {
            return $existente;
        }

        abort_unless($this->status === 'concluido', 422, 'Só é possível publicar após concluir o trâmite.');
        abort_unless($this->modalidade, 422, 'O processo não tem modalidade definida.');
        abort_unless(isset(Chamamento::TIPOS[$this->modalidade]), 422, 'Modalidade inválida para gerar chamamento.');

        $this->loadMissing('orgao');
        $orgao = $this->orgao;
        $ano = $this->created_at?->year ?? now()->year;

        $programa = Programa::doOrgao($orgao, $ano);

        $tipoLabel = self::MODALIDADES[$this->modalidade] ?? $this->modalidade;
        $objeto = $this->extrairObjetoDoTermo() ?: ('Parceria originada do processo ' . $this->numero);

        $dados = [
            'programa_id'     => $programa->id,
            'processo_id'     => $this->id,
            'numero'          => $this->numero,
            'titulo'          => $tipoLabel . ' — processo ' . $this->numero,
            'objeto'          => $objeto,
            'tipo'            => $this->modalidade,
            'status'          => 'publicado',
            'data_publicacao' => now()->toDateString(),
        ];

        // Chamamento Público abre uma janela de inscrição padrão (30 dias, art. 26 §1º da
        // Lei 13.019/2014); a UG ajusta as datas se quiser.
        if ($this->modalidade === 'chamamento_publico') {
            $dados['data_inicio_inscricao'] = now()->toDateString();
            $dados['data_fim_inscricao']    = now()->addDays(30)->toDateString();
        }

        return Chamamento::create($dados);
    }

    /** Tenta obter o objeto a partir do Termo de Referência (texto livre/HTML). */
    public function extrairObjetoDoTermo(): ?string
    {
        $html = $this->peca('termo_referencia')?->conteudo;
        if (!$html) {
            return null;
        }

        $texto = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $texto = preg_replace('/\s+/u', ' ', $texto) ?? $texto;

        if (preg_match('/OBJETO(?:\s+DA\s+PARCERIA)?\s*:?\s*(.+?)(?:OBJETIVOS|ESTIMATIVA|PRAZO|JUSTIFICATIVA|$)/iu', $texto, $m)) {
            $objeto = trim($m[1], " \t\n\r\0\x0B.;");
            if (mb_strlen($objeto) > 20) {
                return mb_substr($objeto, 0, 2000);
            }
        }

        return null;
    }
}
