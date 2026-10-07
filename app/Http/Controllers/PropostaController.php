<?php

namespace App\Http\Controllers;

use App\Models\Proposta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $proposta->load(['chamamento.programa.orgao', 'decididaPor', 'osc', 'metas.etapas', 'planoItens', 'desembolsos',
            'contrapartidas', 'equipe', 'documentos.uploader',
            'recursos.chamamento']);

        return view('propostas.show', compact('proposta'));
    }

    /** A UG aprova (e abre a Celebração) ou reprova, com motivo, a proposta de dispensa ou inexigibilidade. */
    public function decidir(Request $request, Proposta $proposta): RedirectResponse
    {
        abort_unless($proposta->aguardaDecisaoDaUg(), 422, 'Esta proposta não aguarda decisão da Unidade Gestora.');
        abort_unless($proposta->podeSerDecididaPor($request->user()), 403,
            'Quem decide é o Responsável da Unidade Gestora da Secretaria do chamamento.');

        $dados = $request->validate([
            'decisao' => ['required', 'in:aprovar,reprovar'],
            'motivo'  => ['required_if:decisao,reprovar', 'nullable', 'string', 'max:5000'],
        ], ['motivo.required_if' => 'Diga à OSC por que a proposta não foi aprovada.']);

        $aprovar = $dados['decisao'] === 'aprovar';

        DB::transaction(fn () => $proposta->update([
            'status'         => $aprovar ? 'aprovada' : 'reprovada',
            'decidida_por'   => $request->user()->id,
            'decidida_em'    => now(),
            'decisao_motivo' => $aprovar ? null : $dados['motivo'],
        ] + ($aprovar ? [
            'celebracao_iniciada_em' => now(),
            'celebracao_etapa'       => 0,
            'celebracao_setor'       => Proposta::ETAPAS_CELEBRACAO[0]['setor'],
        ] : [])));

        return $aprovar
            ? redirect()->route('celebracao.show', $proposta)->with('success', 'Proposta aprovada. A Celebração começou.')
            : redirect()->route('propostas.show', $proposta)->with('success', 'Proposta reprovada. A OSC foi avisada.');
    }
}
