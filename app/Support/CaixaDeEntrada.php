<?php

namespace App\Support;

use App\Models\Chamamento;
use App\Models\Processo;
use App\Models\Proposta;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * O que está parado esperando o setor do usuário.
 *
 * A caixa de entrada nasceu olhando só para o Planejamento (Processo), e por
 * isso servia apenas aos quatro setores daquele fluxo. Quem atua na Seleção ou
 * na Celebração via sempre "nenhum processo aguardando", mesmo com trabalho
 * parado no próprio nome — o Gabinete do Prefeito, por exemplo, assina a
 * homologação e nunca teve caixa nenhuma, porque não aparece em
 * Processo::SETORES.
 *
 * Cada trâmite guarda o setor da vez numa coluna própria (setor_atual,
 * selecao_setor, celebracao_setor); a análise de proposta não tem coluna
 * nenhuma — é da UG do órgão do chamamento. Esta classe pergunta as quatro
 * fontes e devolve uma lista só, ordenada pelo que espera há mais tempo.
 */
class CaixaDeEntrada
{
    private function __construct(
        public readonly Collection $itens,
    ) {
    }

    public static function para(?User $user): self
    {
        if (!$user?->setor || !$user->temAcessoInterno()) {
            return new self(collect());
        }

        $itens = collect()
            ->concat(self::processos($user))
            ->concat(self::selecoes($user))
            ->concat(self::analiseDePropostas($user))
            ->concat(self::celebracoes($user))
            ->sortBy('desde')   // o mais antigo primeiro: é o que está esperando há mais tempo
            ->values();

        return new self($itens);
    }

    public function total(): int
    {
        return $this->itens->count();
    }

    public function vazia(): bool
    {
        return $this->itens->isEmpty();
    }

    /** Quantos itens por trâmite, sem os zerados — para o resumo da tela. */
    public function porTramite(): array
    {
        return $this->itens->groupBy('tramite')->map->count()->all();
    }

    /** Planejamento: processos em trâmite parados no setor. */
    private static function processos(User $user): Collection
    {
        if (!$user->can('planejamento')) {
            return collect();
        }

        return Processo::with('orgao')
            ->visiveisPara($user)
            ->where('setor_atual', $user->setor)
            ->where('status', 'em_tramite')
            ->get()
            ->map(fn (Processo $p) => [
                'tramite'   => 'Planejamento',
                'titulo'    => 'Processo '.$p->numero,
                'subtitulo' => collect([
                    $p->orgao?->sigla ?: $p->orgao?->name,
                    'Etapa '.($p->etapa + 1).'/'.$p->totalEtapas().' — '.$p->etapaInfo()['acao'],
                ])->filter()->implode(' · '),
                // Recebimento pendente é o único item que exige uma ação antes
                // de qualquer outra; a tela destaca isso.
                'aguardaRecebimento' => $p->aguardandoRecebimento(),
                'url'   => route('processos.show', $p),
                'desde' => $p->updated_at,
            ]);
    }

    /** Seleção: chamamentos públicos com o trâmite parado no setor. */
    private static function selecoes(User $user): Collection
    {
        if (!$user->can('chamamentos')) {
            return collect();
        }

        return Chamamento::with('programa.orgao')
            ->where('tipo', 'chamamento_publico')
            ->where('selecao_setor', $user->setor)
            ->whereNull('selecao_concluida_em')
            ->get()
            ->map(fn (Chamamento $c) => [
                'tramite'   => 'Seleção',
                'titulo'    => trim(($c->numero ? $c->numero.' — ' : '').$c->titulo),
                'subtitulo' => collect([
                    $c->programa?->orgao?->sigla ?: $c->programa?->orgao?->name,
                    'Etapa '.($c->selecao_etapa + 1).'/'.count(Chamamento::ETAPAS_SELECAO)
                        .' — '.($c->etapaSelecaoInfo()['acao'] ?? ''),
                ])->filter()->implode(' · '),
                'aguardaRecebimento' => false,
                'url'   => route('chamamentos.selecao', $c),
                'desde' => $c->updated_at,
            ]);
    }

    /**
     * Análise: propostas que a OSC submeteu e ainda esperam a Unidade Gestora.
     *
     * Era o único trabalho do sistema que não tinha fila: a proposta chegava
     * pelo portal, caía na listagem de Propostas e ficava lá, sem que nada
     * avisasse ninguém — nem a caixa (que só olhava os três trâmites) nem o
     * painel (cujo card conta apenas 'em_analise'). Quem submetia esperava
     * análise que ninguém sabia estar pendente.
     *
     * Análise de proposta é da UG do órgão que abriu o chamamento — por isso o
     * filtro por setor, e não a caixa inteira para todo mundo. O recorte por
     * órgão vem de visiveisPara(), o mesmo da listagem, para a caixa nunca
     * mostrar o que a tela esconde.
     *
     * Fica na fila enquanto houver o que fazer: 'submetida' (ninguém pegou) e
     * 'em_analise' (alguém pegou e não terminou). Sair da fila é decidir —
     * aprovar, reprovar ou cancelar.
     */
    private static function analiseDePropostas(User $user): Collection
    {
        if ($user->setor !== 'ug' || !$user->can('propostas')) {
            return collect();
        }

        return Proposta::with(['osc', 'chamamento.programa.orgao'])
            ->visiveisPara($user)
            ->whereIn('status', ['submetida', 'em_analise'])
            ->get()
            ->map(fn (Proposta $p) => [
                'tramite'   => 'Análise',
                'titulo'    => $p->titulo,
                'subtitulo' => collect([
                    $p->osc?->name,
                    $p->chamamento?->titulo,
                    $p->status === 'submetida' ? 'Aguardando análise' : 'Em análise',
                ])->filter()->implode(' · '),
                'aguardaRecebimento' => false,
                'url'   => route('propostas.show', $p),
                // submitted_at é quando a espera começou; updated_at mudaria a
                // cada edição e faria a proposta parecer recém-chegada.
                'desde' => $p->submitted_at ?? $p->updated_at,
            ]);
    }

    /**
     * Celebração: parcerias com o trâmite parado no setor.
     *
     * Sem exigir permissão de módulo, ao contrário dos dois blocos acima. Não é
     * descuido: a rota `celebracao.show` só pede autenticação, e a Celebração
     * passa por setores que não têm `propostas` nem `formalizacao` — a PJ, por
     * exemplo, emite o Parecer Jurídico na etapa 8 e só tem `pareceres_juridico`.
     * Filtrar por permissão aqui reproduziria justamente o defeito que esta
     * classe veio corrigir: o setor com trabalho parado e caixa vazia.
     *
     * Os dois blocos acima mantêm o filtro porque as rotas de destino
     * (`processos.show` e `chamamentos.selecao`) exigem permissão — sem ele o
     * item apareceria na caixa e devolveria 403 ao ser clicado.
     */
    private static function celebracoes(User $user): Collection
    {
        return Proposta::with('osc')
            ->visiveisPara($user)
            ->where('celebracao_setor', $user->setor)
            ->whereNotNull('celebracao_iniciada_em')
            ->whereNull('celebracao_concluida_em')
            ->get()
            ->map(fn (Proposta $p) => [
                'tramite'   => 'Celebração',
                'titulo'    => $p->titulo,
                'subtitulo' => collect([
                    $p->osc?->name,
                    'Etapa '.($p->celebracao_etapa + 1).'/'.$p->totalEtapasCelebracao()
                        .' — '.($p->etapaCelebracaoInfo()['acao'] ?? ''),
                ])->filter()->implode(' · '),
                'aguardaRecebimento' => false,
                'url'   => route('celebracao.show', $p),
                'desde' => $p->updated_at,
            ]);
    }
}
