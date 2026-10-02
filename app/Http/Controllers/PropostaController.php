<?php

namespace App\Http\Controllers;

use App\Models\Proposta;
use Illuminate\View\View;

/** Propostas do lado do município: ler e analisar (criar e submeter são da OSC, no portal). */
class PropostaController extends Controller
{
    public function index(): View
    {
        // O recurso da OSC aparece marcado na lista: é por aqui que a Comissão
        // de Seleção chega à proposta para julgá-lo.
        $propostas = Proposta::with(['chamamento.programa', 'osc', 'recursos'])
            ->visiveisPara(auth()->user())
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Novas Propostas em andamento: ainda sem chamamento, com a
        // SCP ou com a UG. Deferidas, viram proposta desta mesma lista.
        $novasPropostas = \App\Models\ManifestacaoInteresse::with(['osc', 'orgao'])
            ->doTipo('proposta')
            ->emTramite()
            ->visiveisPara(auth()->user())
            ->latest('submetida_em')
            ->get();

        return view('propostas.index', compact('propostas', 'novasPropostas'));
    }

    public function show(Proposta $proposta): View
    {
        $proposta->load(['chamamento.programa', 'osc', 'metas.etapas', 'planoItens', 'desembolsos',
            'contrapartidas', 'equipe', 'pareceres.diligencias', 'documentos.uploader',
            'recursos.chamamento', 'recursos.respondente']);

        return view('propostas.show', compact('proposta'));
    }
}
