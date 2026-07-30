<?php

namespace App\Http\Controllers;

use App\Models\Chamamento;
use App\Models\Instrumento;
use App\Models\Proposta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function index(): View
    {
        $chamamentos = Chamamento::with(['programa.orgao'])
            ->whereIn('status', ['publicado', 'em_inscricao'])
            ->orderByRaw("FIELD(status, 'em_inscricao', 'publicado')")
            ->orderByDesc('data_publicacao')
            ->orderBy('data_fim_inscricao')
            ->paginate(12);

        return view('portal.index', compact('chamamentos'));
    }

    /**
     * Transparência pública: as parcerias já celebradas, com valores e vigência.
     * É o que Cidadão, Parlamentar e Conselho consultam — sem necessidade de login.
     */
    public function transparencia(Request $request): View
    {
        $filtros = $request->only(['busca', 'tipo', 'exercicio']);

        $instrumentos = Instrumento::with(['proposta.osc', 'proposta.chamamento.programa.orgao'])
            // só o que já foi assinado é público
            ->whereIn('status', ['assinado', 'vigente', 'encerrado'])
            ->when($filtros['busca'] ?? null, function ($q, $busca) {
                $q->where(function ($sub) use ($busca) {
                    $sub->where('numero', 'like', "%{$busca}%")
                        ->orWhere('objeto', 'like', "%{$busca}%")
                        ->orWhereHas('proposta.osc', fn ($o) => $o->where('name', 'like', "%{$busca}%"));
                });
            })
            ->when($filtros['tipo'] ?? null, fn ($q, $v) => $q->where('tipo', $v))
            ->when($filtros['exercicio'] ?? null, fn ($q, $v) => $q->whereYear('data_assinatura', $v))
            ->orderByDesc('data_assinatura')
            ->paginate(15)
            ->withQueryString();

        $totais = [
            'parcerias' => $instrumentos->total(),
            'repassado' => (float) Instrumento::whereIn('status', ['assinado', 'vigente', 'encerrado'])
                ->sum('valor_repasse'),
        ];

        $exercicios = Instrumento::whereIn('status', ['assinado', 'vigente', 'encerrado'])
            ->whereNotNull('data_assinatura')
            ->selectRaw('DISTINCT YEAR(data_assinatura) AS ano')
            ->orderByDesc('ano')
            ->pluck('ano');

        return view('portal.transparencia', compact('instrumentos', 'filtros', 'totais', 'exercicios'));
    }

    public function chamamento(Chamamento $chamamento): View
    {
        $chamamento->load(['programa.orgao', 'processo.pecas']);

        // Documentos públicos do chamamento: peças de texto assinadas do processo
        // de origem (Edital ou Justificativa de Dispensa) — têm página pública de
        // validação, onde a OSC lê o teor completo e confere a assinatura.
        // Recurso da OSC logada neste chamamento (para protocolar ou ver a resposta)
        $osc = auth()->user()?->osc;
        $meuRecurso = $osc
            ? $chamamento->recursos()->where('osc_id', $osc->id)->first()
            : null;
        $participei = $osc
            ? $chamamento->propostas()->where('osc_id', $osc->id)->exists()
            : false;

        $publicos = ['edital', 'justificativa_dispensa', 'parecer_cnas'];
        $documentosPublicos = $chamamento->processo
            ? $chamamento->processo->pecas
                ->whereIn('tipo', $publicos)
                ->filter(fn ($p) => $p->assinado() && $p->codigo_validacao)
                ->values()
            : collect();

        return view('portal.chamamento', compact(
            'chamamento', 'documentosPublicos', 'meuRecurso', 'participei'
        ));
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
        abort_unless($chamamento->aceitaPropostas(), 403, 'Este chamamento não está aberto para inscrições.');

        $osc = auth()->user()->osc;
        if (!$osc) {
            // Usuário interno da Administração não participa como OSC.
            if (auth()->user()->temAcessoInterno()) {
                return redirect()->route('portal.chamamento', $chamamento)
                    ->with('info', 'Você está conectado como usuário do sistema. A submissão de propostas é exclusiva das OSCs.');
            }

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
        abort_unless($chamamento->aceitaPropostas(), 403);

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
