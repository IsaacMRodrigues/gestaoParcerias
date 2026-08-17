<?php

namespace App\Http\Controllers;

use App\Models\Chamamento;
use App\Models\Instrumento;
use App\Models\Osc;
use App\Models\Processo;
use App\Models\Programa;
use App\Models\Proposta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Busca global da barra de comandos (Ctrl+K).
 *
 * Atalho para chegar a um registro sem passar pela sequência
 * menu → listagem → filtro → paginação. Cada bloco só é consultado se o
 * usuário tem a permissão do módulo, e usa o mesmo escopo de visibilidade das
 * listagens (visiveisPara), para a busca nunca revelar o que a tela esconde.
 */
class BuscaController extends Controller
{
    /** Poucos por grupo: a barra mostra um resumo, não uma listagem. */
    private const POR_GRUPO = 5;

    public function __invoke(Request $request): JsonResponse
    {
        $termo = trim((string) $request->query('q', ''));

        // Uma letra casa com quase tudo e só devolveria ruído.
        if (mb_strlen($termo) < 2) {
            return response()->json(['grupos' => []]);
        }

        $user = $request->user();
        // % e _ são curingas do LIKE: escapados, viram texto comum.
        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $termo).'%';

        $grupos = [];

        if ($user->can('planejamento')) {
            $grupos[] = $this->grupo('Processos', 'processo', Processo::visiveisPara($user)
                ->with('orgao')
                ->where(fn ($q) => $q->where('numero', 'like', $like)->orWhere('modalidade', 'like', $like))
                ->latest('id')->limit(self::POR_GRUPO)->get()
                ->map(fn (Processo $p) => [
                    'titulo'    => 'Processo '.$p->numero,
                    'subtitulo' => collect([$p->orgao?->sigla ?: $p->orgao?->name, Processo::STATUS[$p->status] ?? $p->status])
                        ->filter()->implode(' · '),
                    'url' => route('processos.show', $p),
                ]));
        }

        if ($user->can('propostas')) {
            $grupos[] = $this->grupo('Propostas', 'proposta', Proposta::visiveisPara($user)
                ->with('osc')
                ->where(fn ($q) => $q->where('titulo', 'like', $like)
                    ->orWhere('objeto', 'like', $like)
                    ->orWhereHas('osc', fn ($o) => $o->where('name', 'like', $like)))
                ->latest('id')->limit(self::POR_GRUPO)->get()
                ->map(fn (Proposta $p) => [
                    'titulo'    => $p->titulo,
                    'subtitulo' => collect([$p->osc?->name, Proposta::STATUS[$p->status] ?? $p->status])
                        ->filter()->implode(' · '),
                    'url' => route('propostas.show', $p),
                ]));
        }

        if ($user->can('chamamentos')) {
            $grupos[] = $this->grupo('Chamamentos', 'chamamento', Chamamento::query()
                ->where(fn ($q) => $q->where('numero', 'like', $like)
                    ->orWhere('titulo', 'like', $like)
                    ->orWhere('objeto', 'like', $like))
                ->latest('id')->limit(self::POR_GRUPO)->get()
                ->map(fn (Chamamento $c) => [
                    'titulo'    => trim(($c->numero ? $c->numero.' — ' : '').$c->titulo),
                    'subtitulo' => 'Seleção',
                    'url'       => route('chamamentos.selecao', $c),
                ]));

            $grupos[] = $this->grupo('Programas', 'programa', Programa::query()
                ->where(fn ($q) => $q->where('name', 'like', $like)
                    ->orWhere('sigla', 'like', $like)
                    ->orWhere('objetivo', 'like', $like))
                ->latest('id')->limit(self::POR_GRUPO)->get()
                ->map(fn (Programa $p) => [
                    'titulo'    => $p->name,
                    'subtitulo' => collect([$p->sigla, 'Chamamentos do programa'])->filter()->implode(' · '),
                    'url'       => route('programas.chamamentos.index', $p),
                ]));
        }

        if ($user->can('formalizacao')) {
            $grupos[] = $this->grupo('Instrumentos', 'instrumento', Instrumento::query()
                ->with('proposta.osc')
                ->where(fn ($q) => $q->where('numero', 'like', $like)
                    ->orWhere('objeto', 'like', $like)
                    ->orWhereHas('proposta.osc', fn ($o) => $o->where('name', 'like', $like)))
                ->latest('id')->limit(self::POR_GRUPO)->get()
                ->map(fn (Instrumento $i) => [
                    'titulo'    => trim(($i->numero ?: 'Instrumento sem número')),
                    'subtitulo' => collect([$i->proposta?->osc?->name, Instrumento::TIPOS[$i->tipo] ?? $i->tipo])
                        ->filter()->implode(' · '),
                    'url' => route('instrumentos.show', $i),
                ]));
        }

        if ($user->can('cadastros')) {
            $grupos[] = $this->grupo('OSCs', 'osc', Osc::query()
                ->where(fn ($q) => $q->where('name', 'like', $like)
                    ->orWhere('cnpj', 'like', $like)
                    ->orWhere('resp_nome', 'like', $like))
                ->orderBy('name')->limit(self::POR_GRUPO)->get()
                ->map(fn (Osc $o) => [
                    'titulo'    => $o->name,
                    'subtitulo' => collect([$o->cnpj, $o->cidade])->filter()->implode(' · '),
                    'url'       => route('oscs.edit', $o),
                ]));
        }

        return response()->json([
            'grupos' => array_values(array_filter($grupos)),
        ]);
    }

    /** Grupos vazios são descartados para a barra não exibir cabeçalho sem itens. */
    private function grupo(string $rotulo, string $icone, $itens): ?array
    {
        return $itens->isEmpty() ? null : [
            'rotulo' => $rotulo,
            'icone'  => $icone,
            'itens'  => $itens->values()->all(),
        ];
    }
}
