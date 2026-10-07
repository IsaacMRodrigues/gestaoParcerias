<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChamamentoRequest;
use App\Models\Chamamento;
use App\Models\Orgao;
use App\Models\Peca;
use App\Models\Programa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChamamentoController extends Controller
{
    /** Situações do filtro; "abertos" (publicados e em inscrição) é o padrão. */
    public const SITUACOES = [
        'abertos'      => 'Abertos',
        'em_analise'   => 'Em análise',
        'encerrado'    => 'Encerrados',
        'cancelado'    => 'Cancelados',
        'rascunho'     => 'Rascunhos',
        'todos'        => 'Todos',
    ];

    public function index(Request $request): View
    {
        $filtros = $request->only(['busca', 'orgao_id', 'tipo']);
        $filtros['situacao'] = array_key_exists($request->query('situacao'), self::SITUACOES)
            ? $request->query('situacao')
            : 'abertos';

        $chamamentos = Chamamento::with(['programa.orgao', 'processo'])
            ->when($filtros['busca'] ?? null, fn ($q, $busca) => $q->where(fn ($sub) => $sub
                ->where('numero', 'like', "%{$busca}%")
                ->orWhere('titulo', 'like', "%{$busca}%")
                ->orWhere('objeto', 'like', "%{$busca}%")))
            ->when($filtros['orgao_id'] ?? null, fn ($q, $v) => $q->whereHas('programa', fn ($p) => $p->where('orgao_id', $v)))
            ->when($filtros['tipo'] ?? null, fn ($q, $v) => $q->where('tipo', $v))
            ->when($filtros['situacao'] === 'abertos', fn ($q) => $q->whereIn('status', ['publicado', 'em_inscricao']))
            ->when(! in_array($filtros['situacao'], ['abertos', 'todos'], true), fn ($q) => $q->where('status', $filtros['situacao']))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $orgaos = Orgao::orderBy('name')->get();

        return view('chamamentos.index', compact('chamamentos', 'orgaos', 'filtros'));
    }

    public function create(): View
    {
        $this->somenteScp();

        $orgaos = Orgao::where('status', true)->orderBy('name')->get();

        return view('chamamentos.create', compact('orgaos'));
    }

    public function store(ChamamentoRequest $request): RedirectResponse
    {
        $this->somenteScp();

        $orgao = Orgao::findOrFail($request->validate(
            ['orgao_id' => ['required', 'exists:orgaos,id']],
            ['orgao_id.required' => 'Escolha a Secretaria do chamamento.'],
        )['orgao_id']);

        Programa::doOrgao($orgao)->chamamentos()->create($request->validated());

        return redirect()->route('chamamentos.index')
            ->with('success', 'Chamamento cadastrado com sucesso.');
    }

    public function edit(Chamamento $chamamento): View
    {
        $this->somenteScp();

        return view('chamamentos.edit', compact('chamamento'));
    }

    public function update(ChamamentoRequest $request, Chamamento $chamamento): RedirectResponse
    {
        $this->somenteScp();

        $dados = $request->validated();

        // Cancelado só sai pelo botão de reabrir, com motivo — não pela edição.
        if ($chamamento->cancelado()) {
            unset($dados['status']);
        }

        $chamamento->update($dados);

        return redirect()->route('chamamentos.index')
            ->with('success', 'Chamamento atualizado com sucesso.');
    }

    public function destroy(Chamamento $chamamento): RedirectResponse
    {
        $this->somenteScp();

        if ($bloqueio = $this->bloqueioDeExclusao($chamamento)) {
            return $bloqueio;
        }

        $chamamento->delete();

        return redirect()->route('chamamentos.index')
            ->with('success', 'Chamamento removido com sucesso.');
    }

    /** O cadastro do chamamento é da SCP (ver Chamamento::cadastroPermitidoA). */
    private function somenteScp(): void
    {
        abort_unless(Chamamento::cadastroPermitidoA(auth()->user()), 403, 'Só a SCP edita o chamamento.');
    }

    /**
     * 2.2 Seleção e Celebração — checklist documental do chamamento.
     */
    public function selecao(Chamamento $chamamento): View
    {
        $user = auth()->user();
        abort_unless($user->can('chamamentos')
            || ($user->hasRole('comissao_selecao') && $user->orgao_id !== null && $user->orgao_id === $chamamento->programa?->orgao_id),
            403);

        $categoria = $chamamento->categoriaPecas();
        Peca::sincronizar($chamamento, $categoria);

        $chamamento->load([
            'programa.orgao', 'processo',
            'pecas.assinante.roles', 'pecas.assinante.orgao',
            // Peças que o Planejamento já produziu: a Seleção exibe o documento
            // de lá (ver Peca::ORIGEM_PLANEJAMENTO), não uma cópia.
            'pecas.origem.processo', 'pecas.origem.anexos', 'pecas.origem.assinante',
            'selecaoTramitacoes.remetente',
            'recursos.osc',
            'propostas.osc',
        ]);
        $pecas = $chamamento->pecas;
        $progresso = Peca::progresso($pecas);

        // Julgamento: quem ainda não teve decisão (para adjudicar) e quem
        // venceu (para a tela apontar o caminho da Celebração).
        $emJulgamento = $chamamento->propostas->whereIn('status', ['submetida', 'em_analise']);
        $vencedoras   = $chamamento->propostas->where('status', 'aprovada');

        return view('chamamentos.selecao', compact(
            'chamamento', 'pecas', 'categoria', 'progresso', 'emJulgamento', 'vencedoras'
        ));
    }
}
