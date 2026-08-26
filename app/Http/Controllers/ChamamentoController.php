<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChamamentoRequest;
use App\Models\Chamamento;
use App\Models\Peca;
use App\Models\Programa;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ChamamentoController extends Controller
{
    public function index(Programa $programa): View
    {
        $chamamentos = $programa->chamamentos()
            ->with('processo')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('chamamentos.index', compact('programa', 'chamamentos'));
    }

    public function create(Programa $programa): View
    {
        return view('chamamentos.create', compact('programa'));
    }

    public function store(ChamamentoRequest $request, Programa $programa): RedirectResponse
    {
        $programa->chamamentos()->create($request->validated());

        return redirect()->route('programas.chamamentos.index', $programa)
            ->with('success', 'Chamamento cadastrado com sucesso.');
    }

    public function edit(Programa $programa, Chamamento $chamamento): View
    {
        return view('chamamentos.edit', compact('programa', 'chamamento'));
    }

    public function update(ChamamentoRequest $request, Programa $programa, Chamamento $chamamento): RedirectResponse
    {
        $chamamento->update($request->validated());

        return redirect()->route('programas.chamamentos.index', $programa)
            ->with('success', 'Chamamento atualizado com sucesso.');
    }

    public function destroy(Programa $programa, Chamamento $chamamento): RedirectResponse
    {
        if ($bloqueio = $this->bloqueioDeExclusao($chamamento)) {
            return $bloqueio;
        }

        $chamamento->delete();

        return redirect()->route('programas.chamamentos.index', $programa)
            ->with('success', 'Chamamento removido com sucesso.');
    }

    /**
     * 2.2 Seleção e Celebração — checklist documental do chamamento.
     */
    public function selecao(Chamamento $chamamento): View
    {
        $categoria = $chamamento->categoriaPecas();
        Peca::sincronizar($chamamento, $categoria);

        $chamamento->load([
            'programa.orgao', 'processo',
            'pecas.assinante.roles', 'pecas.assinante.orgao',
            // Peças que o Planejamento já produziu: a Seleção exibe o documento
            // de lá (ver Peca::ORIGEM_PLANEJAMENTO), não uma cópia.
            'pecas.origem.processo', 'pecas.origem.anexos', 'pecas.origem.assinante',
            'selecaoTramitacoes.remetente',
            'recursos.osc', 'recursos.respondente',
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
