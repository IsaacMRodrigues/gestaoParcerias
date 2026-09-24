<?php

namespace App\Support;

use App\Mail\Aviso;
use App\Models\Alteracao;
use App\Models\Chamado;
use App\Models\ChamadoMensagem;
use App\Models\Chamamento;
use App\Models\Diligencia;
use App\Models\ManifestacaoInteresse;
use App\Models\PrestacaoContas;
use App\Models\Processo;
use App\Models\Proposta;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Avisos por e-mail: quem recebe o quê.
 *
 * Até aqui o sistema não mandava e-mail nenhum — a vez no trâmite, a conta
 * esperando aprovação e a resposta do suporte só existiam na tela, e quem não
 * entrava não sabia. Os avisos saem da caixa parcerias@pmsgra.net, pela fila
 * (ver App\Mail\Aviso).
 *
 * Disparam de eventos dos modelos, registrados em registrar(), e não dos
 * controllers: o que importa é o setor da vez ter mudado, por qualquer
 * caminho — avançar, devolver, decidir. Um caminho novo que mude o setor já
 * avisa sem ninguém lembrar de chamar nada.
 *
 * Quem recebe a vez segue a régua da Caixa de Entrada: mesmo setor, a
 * permissão do módulo e a parceria visível para a pessoa. E-mail de algo que a
 * pessoa não enxerga na tela seria pior que nenhum. Quem fez a ação não é
 * avisado dela.
 */
class Avisos
{
    public static function registrar(): void
    {
        // ── Contas ─────────────────────────────────────────────────────
        User::created(function (User $u) {
            if ($u->approval_status === 'pendente') {
                self::contaPendente($u);
            }
        });
        User::updated(function (User $u) {
            if ($u->wasChanged('approval_status') && in_array($u->approval_status, ['aprovado', 'recusado'], true)
                && $u->getOriginal('approval_status') === 'pendente') {
                self::contaDecidida($u);
            }
        });

        // ── Suporte ────────────────────────────────────────────────────
        ChamadoMensagem::created(fn (ChamadoMensagem $m) => self::mensagemDeSuporte($m));

        // ── Vez no trâmite ─────────────────────────────────────────────
        $vez = function (string $coluna, callable $aviso) {
            return [
                fn ($m) => $m->{$coluna} !== null ? $aviso($m) : null,
                fn ($m) => $m->wasChanged($coluna) && $m->{$coluna} !== null ? $aviso($m) : null,
            ];
        };

        foreach ([
            Processo::class              => $vez('setor_atual', fn (Processo $p) => self::vezDoProcesso($p)),
            Chamamento::class            => $vez('selecao_setor', fn (Chamamento $c) => self::vezDaSelecao($c)),
            Alteracao::class             => $vez('setor_atual', fn (Alteracao $a) => self::vezDaAlteracao($a)),
            PrestacaoContas::class       => $vez('setor', fn (PrestacaoContas $pc) => self::vezDaPrestacao($pc)),
            ManifestacaoInteresse::class => $vez('setor_atual', fn (ManifestacaoInteresse $m) => self::vezDaManifestacao($m)),
        ] as $modelo => [$aoCriar, $aoMudar]) {
            $modelo::created($aoCriar);
            $modelo::updated($aoMudar);
        }

        Proposta::updated(function (Proposta $p) {
            if ($p->wasChanged('celebracao_setor') && $p->celebracao_setor !== null) {
                self::vezDaCelebracao($p);
            }
            if ($p->wasChanged('status')) {
                self::propostaMudouDeStatus($p);
            }
        });

        // ── Resultados para a OSC ──────────────────────────────────────
        ManifestacaoInteresse::updated(function (ManifestacaoInteresse $m) {
            if ($m->wasChanged('status') && in_array($m->status, ['deferida', 'indeferida'], true)) {
                self::manifestacaoDecidida($m);
            }
        });
        Alteracao::updated(function (Alteracao $a) {
            if ($a->wasChanged('status') && $a->tramiteEncerrado()) {
                self::alteracaoDecidida($a);
            }
        });
        PrestacaoContas::updated(function (PrestacaoContas $pc) {
            if ($pc->wasChanged('concluida_em') && $pc->concluida_em !== null) {
                self::prestacaoConcluida($pc);
            }
        });
        Diligencia::created(fn (Diligencia $d) => self::diligencia($d));
    }

    // ════════════════════════════════════════════════════════════ envio

    /**
     * Enfileira o aviso para cada destinatário. Nunca derruba a ação que o
     * motivou: sem SMTP, sem fila ou com endereço ruim, o aviso se perde e o
     * registro fica no log — a gravação continua valendo.
     */
    public static function enviar(iterable $para, Aviso $aviso): void
    {
        $autor = auth()->id();

        collect($para)
            ->filter(fn ($u) => $u instanceof User && $u->id !== $autor
                && filter_var($u->email, FILTER_VALIDATE_EMAIL))
            ->unique('email')
            ->each(function (User $u) use ($aviso) {
                try {
                    Mail::to($u->email, $u->name)->queue(clone $aviso);
                } catch (\Throwable $e) {
                    Log::warning('Aviso por e-mail não enfileirado', ['para' => $u->email, 'erro' => $e->getMessage()]);
                }
            });
    }

    // ═══════════════════════════════════════════════════ quem recebe

    /** Servidores do setor que passam pelo filtro — conta ativa, aprovada, de dentro. */
    public static function doSetor(?string $setor, callable $filtro): Collection
    {
        if (!$setor || $setor === 'osc') {
            return collect();
        }

        return User::with('roles', 'permissions')
            ->where('status', true)->where('approval_status', 'aprovado')
            ->whereNull('osc_id')->where('setor', $setor)
            ->get()
            ->filter(fn (User $u) => $u->temAcessoInterno() && $filtro($u))
            ->values();
    }

    /**
     * A equipe da OSC que deve saber: o responsável legal sempre; os demais,
     * se o filtro os aceitar (em geral, ter a função daquele trabalho).
     */
    public static function daOsc(?int $oscId, ?callable $filtro = null): Collection
    {
        if (!$oscId) {
            return collect();
        }

        return User::with('roles', 'permissions', 'osc')
            ->where('status', true)->where('approval_status', 'aprovado')
            ->where('osc_id', $oscId)
            ->get()
            ->filter(fn (User $u) => $u->ehRepresentanteOsc()
                && ($u->ehResponsavelLegalOsc() || $filtro === null || $filtro($u)))
            ->values();
    }

    private static function comFuncao(string $funcao): callable
    {
        return fn (User $u) => !$u->oscSemFuncao($funcao);
    }

    private static function setorLabel(?string $setor): string
    {
        return User::LOTACOES[$setor] ?? Processo::SETORES[$setor] ?? strtoupper((string) $setor);
    }

    private static function etapa(int $atual, int $total, ?string $acao): string
    {
        return 'Etapa ' . ($atual + 1) . ' de ' . $total . ($acao ? ' — ' . $acao : '') . '.';
    }

    // ═══════════════════════════════════════════════════════ contas

    public static function contaPendente(User $conta): void
    {
        $aprovadores = User::with('roles', 'permissions')
            ->where('status', true)->where('approval_status', 'aprovado')->get()
            ->filter(fn (User $u) => $u->can('cadastros') || ($conta->osc_id && $u->can('aprovar_contas_osc')));

        $origem = $conta->osc_id
            ? 'Integrante da organização ' . ($conta->osc?->name ?? '') . ', cadastrado pelo responsável legal.'
            : 'Servidor' . ($conta->setor ? ' do setor ' . self::setorLabel($conta->setor) : '') . '.';

        self::enviar($aprovadores, new Aviso(
            assunto: 'Conta aguardando aprovação — ' . $conta->name,
            titulo: 'Há uma conta nova esperando aprovação',
            linhas: [$conta->name . ' (' . $conta->email . ').', $origem,
                'A pessoa só consegue entrar depois que a conta for aprovada.'],
            url: route('usuarios.pendentes'),
            botao: 'Ver aprovações pendentes',
        ));
    }

    public static function contaDecidida(User $conta): void
    {
        $aprovada = $conta->approval_status === 'aprovado';

        self::enviar([$conta], new Aviso(
            assunto: $aprovada ? 'Sua conta foi aprovada' : 'Sua conta não foi aprovada',
            titulo: $aprovada ? 'Sua conta no Portal de Parcerias foi aprovada' : 'Sua conta no Portal de Parcerias não foi aprovada',
            linhas: $aprovada
                ? ['Você já pode entrar com o seu e-mail e a senha que recebeu.',
                   'Se a senha foi definida por outra pessoa, o sistema vai pedir que você escolha a sua no primeiro acesso.']
                : array_values(array_filter([
                    $conta->rejeitado_motivo ? 'Motivo informado: ' . $conta->rejeitado_motivo : null,
                    'Se acha que houve engano, procure quem fez o seu cadastro.',
                ])),
            url: $aprovada ? route('login') : null,
            botao: 'Entrar',
        ));
    }

    // ══════════════════════════════════════════════════════ suporte

    public static function mensagemDeSuporte(ChamadoMensagem $m): void
    {
        if ($m->interna) {
            return;
        }

        $chamado = $m->chamado()->with('autor', 'conta')->first();
        if (!$chamado) {
            return;
        }

        $equipe = fn () => User::with('roles', 'permissions')
            ->where('status', true)->where('approval_status', 'aprovado')->get()
            ->filter(fn (User $u) => $u->can('suporte'));
        $url = route('suporte.show', $chamado);

        // A primeira mensagem é a abertura do chamado.
        if ($chamado->mensagens()->count() === 1) {
            self::enviar($equipe(), new Aviso(
                assunto: 'Chamado ' . $chamado->numero . ' — ' . $chamado->categoriaLabel(),
                titulo: 'Chamado novo no suporte: ' . $chamado->assunto,
                linhas: [
                    'Aberto por ' . $chamado->autor_nome . ($chamado->autor_vinculo ? ' (' . $chamado->autor_vinculo . ')' : '') . '.',
                    'Categoria: ' . $chamado->categoriaLabel() . '.',
                ],
                url: $url, botao: 'Abrir o chamado',
            ));

            // Pedido de senha feito sem login: a dona da conta fica sabendo —
            // se não foi ela quem pediu, é o aviso de que alguém tentou.
            if ($chamado->ehPedidoDeSenha() && $chamado->user_id === null) {
                self::enviar([$chamado->conta], new Aviso(
                    assunto: 'Pedido de nova senha para a sua conta',
                    titulo: 'Recebemos um pedido de nova senha para a sua conta',
                    linhas: [
                        'A equipe de suporte vai confirmar a sua identidade pelo contato informado no pedido antes de passar uma senha provisória.',
                        'Se não foi você quem pediu, avise o suporte: alguém tentou trocar a senha da sua conta.',
                    ],
                ));
            }

            return;
        }

        $daEquipe = $m->user_id !== null && $m->user_id !== $chamado->user_id && $m->autor?->can('suporte');

        if ($daEquipe) {
            self::enviar([$chamado->autor], new Aviso(
                assunto: 'Resposta no chamado ' . $chamado->numero,
                titulo: 'A equipe respondeu o seu chamado: ' . $chamado->assunto,
                linhas: ['Abra o chamado para ler a resposta e, se precisar, continuar a conversa.'],
                url: $url, botao: 'Ver a resposta',
            ));
        } else {
            self::enviar($equipe(), new Aviso(
                assunto: 'Nova mensagem no chamado ' . $chamado->numero,
                titulo: $chamado->autor_nome . ' escreveu no chamado: ' . $chamado->assunto,
                linhas: ['Situação do chamado: ' . $chamado->statusLabel() . '.'],
                url: $url, botao: 'Abrir o chamado',
            ));
        }
    }

    // ══════════════════════════════════════════════ vez no trâmite

    private static function avisoDeVez(string $tramite, string $titulo, string $etapa, ?string $detalhe, string $url, bool $paraOsc): Aviso
    {
        return new Aviso(
            assunto: $tramite . ' — ' . $titulo,
            titulo: ($paraOsc ? 'É a vez da sua organização' : 'É a vez do seu setor') . ' — ' . $tramite,
            linhas: array_values(array_filter([$titulo, $detalhe, $etapa])),
            url: $url,
            botao: 'Continuar no sistema',
        );
    }

    public static function vezDoProcesso(Processo $p): void
    {
        if ($p->status !== 'em_tramite') {
            return;
        }

        $info = $p->etapaInfo();

        self::enviar(
            self::doSetor($p->setor_atual, fn (User $u) => $u->can('planejamento') && $p->visivelPara($u)),
            self::avisoDeVez('Planejamento', 'Processo ' . $p->numero, self::etapa((int) $p->etapa, $p->totalEtapas(), $info['acao'] ?? null),
                $p->orgao?->name, route('processos.show', $p), false),
        );
    }

    public static function vezDaSelecao(Chamamento $c): void
    {
        if ($c->selecao_concluida_em || $c->tipo !== 'chamamento_publico') {
            return;
        }

        $orgao = $c->programa?->orgao_id;

        self::enviar(
            self::doSetor($c->selecao_setor, fn (User $u) => $u->can('chamamentos')
                && ($u->podeVerTodosOrgaos() || $u->orgao_id === $orgao)),
            self::avisoDeVez('Seleção', trim(($c->numero ? $c->numero . ' — ' : '') . $c->titulo),
                self::etapa((int) $c->selecao_etapa, count(Chamamento::ETAPAS_SELECAO), $c->etapaSelecaoInfo()['acao'] ?? null),
                $c->programa?->orgao?->name, route('chamamentos.selecao', $c), false),
        );
    }

    public static function vezDaCelebracao(Proposta $p): void
    {
        if ($p->celebracaoConcluida()) {
            return;
        }

        $etapa = self::etapa((int) $p->celebracao_etapa, $p->totalEtapasCelebracao(), $p->etapaCelebracaoInfo()['acao'] ?? null);
        $url   = route('celebracao.show', $p);

        if ($p->celebracao_setor === 'osc') {
            self::enviar(self::daOsc($p->osc_id, self::comFuncao('osc_celebracao')),
                self::avisoDeVez('Celebração', $p->titulo, $etapa, null, $url, true));

            return;
        }

        self::enviar(
            self::doSetor($p->celebracao_setor, fn (User $u) => $u->participaDaCelebracao() && $p->visivelPara($u)),
            self::avisoDeVez('Celebração', $p->titulo, $etapa, $p->osc?->name, $url, false),
        );
    }

    public static function vezDaAlteracao(Alteracao $a): void
    {
        if ($a->tramiteEncerrado() || $a->status === 'rascunho') {
            return;
        }

        $proposta = $a->proposta();
        $etapas   = $a->tramiteEtapas();
        $etapa    = self::etapa($a->tramiteEtapaAtual(), count($etapas), $etapas[$a->tramiteEtapaAtual()]['acao'] ?? null);
        $titulo   = trim(($a->numero ? $a->numero . ' — ' : '') . $a->titulo);
        $url      = route('alteracoes.show', $a);

        if ($a->setor_atual === 'osc') {
            self::enviar(self::daOsc($proposta?->osc_id, self::comFuncao('osc_propostas')),
                self::avisoDeVez('Alteração da parceria', $titulo, $etapa, null, $url, true));

            return;
        }

        self::enviar(
            self::doSetor($a->setor_atual, fn (User $u) => $proposta?->visivelPara($u)),
            self::avisoDeVez('Alteração da parceria', $titulo, $etapa, $proposta?->osc?->name, $url, false),
        );
    }

    public static function vezDaPrestacao(PrestacaoContas $pc): void
    {
        if ($pc->concluida_em) {
            return;
        }

        $proposta = $pc->instrumento?->proposta;
        $etapas   = $pc->tramiteEtapas();
        $etapa    = self::etapa($pc->tramiteEtapaAtual(), count($etapas), $etapas[$pc->tramiteEtapaAtual()]['acao'] ?? null);
        $titulo   = 'Prestação de contas' . ($pc->numero ? ' ' . $pc->numero : '') . ' — ' . ($proposta?->titulo ?? '');
        $url      = route('prestacao-contas.show', $pc);

        if ($pc->setor === 'osc') {
            // Não há função de OSC para a prestação: além do responsável legal,
            // recebe quem tem o perfil de Cadastrador de Prestação de Contas.
            self::enviar(self::daOsc($proposta?->osc_id, fn (User $u) => $u->hasRole('cadastrador_prestacao_contas')),
                self::avisoDeVez('Prestação de contas', $titulo, $etapa, null, $url, true));

            return;
        }

        self::enviar(
            self::doSetor($pc->setor, fn (User $u) => ($u->can('prestacao_contas') || $u->can('execucao'))
                && $proposta?->visivelPara($u)),
            self::avisoDeVez('Prestação de contas', $titulo, $etapa, $proposta?->osc?->name, $url, false),
        );
    }

    public static function vezDaManifestacao(ManifestacaoInteresse $m): void
    {
        if (!in_array($m->status, ['submetida', 'em_analise', 'analisada'], true)) {
            return;
        }

        self::enviar(
            self::doSetor($m->setor_atual, fn (User $u) => $u->can('chamamentos')
                && ManifestacaoInteresse::visiveisPara($u)->whereKey($m->id)->exists()),
            self::avisoDeVez('Manifestação de interesse', $m->titulo, ManifestacaoInteresse::STATUS[$m->status] . '.',
                $m->osc?->name, route('manifestacoes.show', $m), false),
        );
    }

    /** Proposta submetida chega à UG para análise; aprovada ou reprovada, a OSC fica sabendo. */
    public static function propostaMudouDeStatus(Proposta $p): void
    {
        if ($p->status === 'submetida') {
            self::enviar(
                self::doSetor('ug', fn (User $u) => $u->can('propostas') && $p->visivelPara($u)),
                self::avisoDeVez('Análise de proposta', $p->titulo, 'Proposta apresentada, aguardando análise.',
                    $p->osc?->name, route('propostas.show', $p), false),
            );

            return;
        }

        if (in_array($p->status, ['aprovada', 'reprovada'], true)) {
            $aprovada = $p->status === 'aprovada';

            self::enviar(self::daOsc($p->osc_id, self::comFuncao('osc_propostas')), new Aviso(
                assunto: 'Resultado da proposta — ' . $p->titulo,
                titulo: $aprovada ? 'Sua proposta foi aprovada' : 'Sua proposta não foi aprovada',
                linhas: array_values(array_filter([
                    $p->titulo,
                    $p->chamamento?->titulo,
                    $aprovada ? 'O próximo passo é a Celebração da parceria; o município avisa quando for a vez da organização.' : null,
                ])),
                url: route('portal.proposta.show', $p),
                botao: 'Ver a proposta',
            ));
        }
    }

    // ═════════════════════════════════════════ resultados para a OSC

    public static function manifestacaoDecidida(ManifestacaoInteresse $m): void
    {
        $deferida = $m->status === 'deferida';

        self::enviar(self::daOsc($m->osc_id, self::comFuncao('osc_manifestacoes')), new Aviso(
            assunto: 'Manifestação de interesse ' . ($deferida ? 'deferida' : 'indeferida'),
            titulo: 'Sua manifestação de interesse foi ' . ($deferida ? 'deferida' : 'indeferida'),
            linhas: [$m->titulo],
            url: route('portal.manifestacoes.show', $m),
            botao: 'Ver a manifestação',
        ));
    }

    public static function alteracaoDecidida(Alteracao $a): void
    {
        $proposta = $a->proposta();

        self::enviar(self::daOsc($proposta?->osc_id, self::comFuncao('osc_propostas')), new Aviso(
            assunto: 'Pedido de alteração — ' . (Alteracao::STATUS[$a->status] ?? $a->status),
            titulo: 'O pedido de alteração foi decidido: ' . (Alteracao::STATUS[$a->status] ?? $a->status),
            linhas: array_values(array_filter([trim(($a->numero ? $a->numero . ' — ' : '') . $a->titulo), $a->decisao_motivo])),
            url: route('alteracoes.show', $a),
            botao: 'Ver o pedido',
        ));
    }

    public static function prestacaoConcluida(PrestacaoContas $pc): void
    {
        $proposta = $pc->instrumento?->proposta;

        self::enviar(self::daOsc($proposta?->osc_id, fn (User $u) => $u->hasRole('cadastrador_prestacao_contas')), new Aviso(
            assunto: 'Prestação de contas concluída',
            titulo: 'A prestação de contas foi concluída',
            linhas: array_values(array_filter([$proposta?->titulo, 'Abra a prestação para ver o parecer conclusivo.'])),
            url: route('prestacao-contas.show', $pc),
            botao: 'Ver a prestação',
        ));
    }

    public static function diligencia(Diligencia $d): void
    {
        $proposta = $d->proposta;

        self::enviar(self::daOsc($proposta?->osc_id, self::comFuncao('osc_propostas')), new Aviso(
            assunto: 'Diligência na proposta — ' . ($proposta?->titulo ?? ''),
            titulo: 'O município pediu uma diligência na sua proposta',
            linhas: array_values(array_filter([
                $proposta?->titulo,
                $d->descricao,
                $d->prazo ? 'Prazo: ' . \Illuminate\Support\Carbon::parse($d->prazo)->format('d/m/Y') . '.' : null,
            ])),
            url: $proposta ? route('portal.proposta.show', $proposta) : null,
            botao: 'Responder',
        ));
    }
}
