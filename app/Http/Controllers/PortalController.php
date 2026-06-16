<?php

namespace App\Http\Controllers;

use App\Models\Chamamento;
use App\Models\Proposta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function index(): View
    {
        $chamamentos = Chamamento::with(['programa.orgao'])
            ->where('status', 'em_inscricao')
            ->orderBy('data_fim_inscricao')
            ->paginate(12);

        return view('portal.index', compact('chamamentos'));
    }

    public function chamamento(Chamamento $chamamento): View
    {
        $chamamento->load(['programa.orgao']);
        return view('portal.chamamento', compact('chamamento'));
    }

    public function minhasPropostas(): View
    {
        $osc = auth()->user()->osc;
        abort_unless($osc, 403, 'Sua conta não está vinculada a uma OSC.');

        $propostas = $osc->propostas()
            ->with(['chamamento.programa'])
            ->latest()
            ->paginate(10);

        return view('portal.minhas-propostas', compact('propostas', 'osc'));
    }

    public function participar(Chamamento $chamamento): View|RedirectResponse
    {
        abort_unless($chamamento->status === 'em_inscricao', 403, 'Este chamamento não está aberto para inscrições.');

        $osc = auth()->user()->osc;
        if (!$osc) {
            return redirect()->route('portal.osc.create')
                ->with('info', 'Cadastre sua OSC antes de submeter uma proposta.');
        }

        $existente = Proposta::where('chamamento_id', $chamamento->id)
            ->where('osc_id', $osc->id)
            ->first();

        if ($existente) {
            return redirect()->route('portal.proposta.show', $existente)
                ->with('info', 'Você já possui uma proposta para este chamamento.');
        }

        return view('portal.participar', compact('chamamento', 'osc'));
    }

    public function storeProposta(Request $request, Chamamento $chamamento): RedirectResponse
    {
        abort_unless($chamamento->status === 'em_inscricao', 403);

        $osc = auth()->user()->osc;
        abort_unless($osc, 403);

        $data = $request->validate([
            'titulo'               => ['required', 'string', 'max:255'],
            'objeto'               => ['required', 'string'],
            'justificativa'        => ['nullable', 'string'],
            'valor_solicitado'     => ['required', 'numeric', 'min:0'],
            'valor_proprio'        => ['nullable', 'numeric', 'min:0'],
            'data_inicio_prevista' => ['nullable', 'date'],
            'data_fim_prevista'    => ['nullable', 'date', 'after_or_equal:data_inicio_prevista'],
        ]);

        $proposta = Proposta::create([
            ...$data,
            'chamamento_id' => $chamamento->id,
            'osc_id'        => $osc->id,
            'status'        => 'rascunho',
        ]);

        return redirect()->route('portal.proposta.show', $proposta)
            ->with('success', 'Proposta criada! Revise, anexe documentos e submeta quando estiver pronta.');
    }

    public function showProposta(Proposta $proposta): View
    {
        $osc = auth()->user()->osc;
        abort_unless($osc && $proposta->osc_id === $osc->id, 403);

        $proposta->load(['chamamento.programa.orgao', 'documentos.uploader', 'pareceres']);
        return view('portal.proposta', compact('proposta'));
    }

    public function submeterProposta(Proposta $proposta): RedirectResponse
    {
        $osc = auth()->user()->osc;
        abort_unless($osc && $proposta->osc_id === $osc->id && $proposta->status === 'rascunho', 403);

        $proposta->update(['status' => 'submetida', 'submitted_at' => now()]);

        return redirect()->route('portal.proposta.show', $proposta)
            ->with('success', 'Proposta submetida com sucesso! Aguarde a análise do órgão responsável.');
    }
}
