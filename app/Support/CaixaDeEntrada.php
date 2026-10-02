<?php

namespace App\Support;

use App\Models\Chamamento;
use App\Models\ManifestacaoInteresse;
use App\Models\Processo;
use App\Models\Proposta;
use App\Models\User;
use Illuminate\Support\Collection;

class CaixaDeEntrada
{
    private function __construct(
        public readonly Collection $itens,
    ) {
    }

    public static function para(?User $user): self
    {
        if (!$user) {
            return new self(collect());
        }

        if ($user->ehRepresentanteOsc()) {
            return new self(self::celebracoesDaOsc($user)->sortBy('desde')->values());
        }

        if (!$user->setor || !$user->temAcessoInterno()) {
            return new self(collect());
        }

        $itens = collect()
            ->concat(self::processos($user))
            ->concat(self::selecoes($user))
            ->concat(self::analiseDePropostas($user))
            ->concat(self::celebracoes($user))
            ->concat(self::manifestacoes($user))
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
            ->where('status', '!=', 'cancelado') // cancelado não anda: não é a vez de ninguém
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

    private static function analiseDePropostas(User $user): Collection
    {
        if ($user->setor !== 'ug' || !$user->can('propostas')) {
            return collect();
        }

        return Proposta::with(['osc', 'chamamento.programa.orgao'])
            ->visiveisPara($user)
            ->whereIn('status', ['submetida', 'em_analise'])
            ->whereHas('chamamento', fn ($q) => $q->where('status', '!=', 'cancelado'))
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

    private static function manifestacoes(User $user): Collection
    {
        if (!$user->can('chamamentos')) {
            return collect();
        }

        return ManifestacaoInteresse::with(['osc', 'orgao'])
            ->visiveisPara($user)
            ->emTramite()
            ->where('setor_atual', $user->setor)
            ->get()
            ->map(fn (ManifestacaoInteresse $m) => [
                'tramite'   => $m->ehNovaProposta() ? 'Nova Proposta' : 'Manifestação',
                'titulo'    => $m->titulo,
                'subtitulo' => collect([
                    $m->osc?->name,
                    $m->orgao?->sigla ?: $m->orgao?->name,
                    $m->ehNovaProposta()
                        ? ($m->status === 'submetida' ? 'Recebida — encaminhar à Unidade Gestora' : 'Deferir ou indeferir')
                        : ($m->status === 'em_analise'
                        ? 'Manifestação técnica da Secretaria'
                        : ($m->status === 'analisada' ? 'Decisão do SCP' : 'Recebida — encaminhar à Secretaria')),
                ])->filter()->implode(' · '),
                'aguardaRecebimento' => false,
                'url'   => route('manifestacoes.show', $m),
                'desde' => $m->updated_at,
            ]);
    }

    private static function celebracoesDaOsc(User $user): Collection
    {
        return Proposta::with('chamamento')
            ->where('osc_id', $user->osc_id)
            ->where('celebracao_setor', 'osc')
            ->whereNotNull('celebracao_iniciada_em')
            ->whereNull('celebracao_concluida_em')
            ->get()
            ->map(fn (Proposta $p) => [
                'tramite'   => 'Celebração',
                'titulo'    => $p->titulo,
                'subtitulo' => 'Etapa '.($p->celebracao_etapa + 1).'/'.$p->totalEtapasCelebracao()
                    .' — '.($p->etapaCelebracaoInfo()['acao'] ?? ''),
                'aguardaRecebimento' => false,
                'url'   => route('celebracao.show', $p),
                'desde' => $p->updated_at,
            ]);
    }

    private static function celebracoes(User $user): Collection
    {
        // Na etapa conjunta da Celebração (UG e SCP em paralelo), o
        // item fica na caixa de cada setor até ele concluir a sua parte.
        return Proposta::with('osc')
            ->visiveisPara($user)
            // E o Gestor da Parceria escolhido pela SCP; nas etapas com perfil, só quem o tem.
            ->where(fn ($q) => $q->where('celebracao_setor', $user->setor)
                ->orWhereIn('celebracao_etapa', Proposta::etapasConjuntasDoSetor($user->setor))
                ->orWhere(fn ($g) => $g->where('celebracao_setor', 'gestor')->where('celebracao_gestor_id', $user->id)))
            ->whereNotNull('celebracao_iniciada_em')
            ->whereNull('celebracao_concluida_em')
            ->get()
            ->filter(fn (Proposta $p) => $p->usuarioTemAVezNaCelebracao($user))
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
