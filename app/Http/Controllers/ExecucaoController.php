<?php

namespace App\Http\Controllers;

use App\Models\Despesa;
use App\Models\Instrumento;
use App\Models\Repasse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Execução da parceria: a Prefeitura registra os repasses; as despesas vêm do painel da OSC (LancamentoController). */
class ExecucaoController extends Controller
{
    /** Parcerias em execução, para escolher de qual instrumento ver repasses, despesas e saldo. */
    public function index(Request $request): View
    {
        $filtros = $request->only(['busca', 'status']);

        $instrumentos = Instrumento::with(['proposta.osc'])
            ->visiveisPara($request->user())
            ->withSum('repasses as total_repassado', 'valor')
            ->withSum('despesas as total_gasto', 'valor')
            // só faz sentido executar o que já foi assinado
            ->whereIn('status', ['vigente', 'assinado', 'encerrado'])
            ->when($filtros['busca'] ?? null, function ($q, $busca) {
                $q->where(function ($sub) use ($busca) {
                    $sub->where('numero', 'like', "%{$busca}%")
                        ->orWhere('objeto', 'like', "%{$busca}%")
                        ->orWhereHas('proposta.osc', fn ($o) => $o->where('name', 'like', "%{$busca}%"));
                });
            })
            ->when($filtros['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->orderByRaw("CASE status WHEN 'vigente' THEN 1 WHEN 'assinado' THEN 2 WHEN 'encerrado' THEN 3 ELSE 4 END")
            ->orderByDesc('data_inicio')
            ->paginate(15)
            ->withQueryString();

        return view('execucao.index', compact('instrumentos', 'filtros'));
    }

    public function show(Instrumento $instrumento): View
    {
        $instrumento->load(['proposta.osc', 'repasses', 'despesas']);

        // Alertas de inconsistência (4.4)
        $alertas = [];
        if ($instrumento->saldo() < 0) {
            $alertas[] = 'As despesas ultrapassam o total repassado (saldo negativo).';
        }
        $semNota = $instrumento->despesas->filter(fn ($d) => ! $d->temNotaFiscal())->count();
        if ($semNota > 0) {
            $alertas[] = "{$semNota} despesa(s) sem nota fiscal anexada.";
        }

        // Despesas agrupadas por natureza
        $porNatureza = $instrumento->despesas
            ->groupBy('natureza')
            ->map(fn ($g) => $g->sum('valor'));

        return view('execucao.show', compact('instrumento', 'alertas', 'porNatureza'));
    }

    public function storeRepasse(Request $request, Instrumento $instrumento): RedirectResponse
    {
        $data = $request->validate([
            'parcela'      => ['nullable', 'integer', 'min:1'],
            'data_repasse' => ['required', 'date'],
            'valor'        => ['required', 'numeric', 'min:0.01'],
            'documento'    => ['nullable', 'string', 'max:255'],
            'observacao'   => ['nullable', 'string', 'max:255'],
        ]);

        $instrumento->repasses()->create($data);

        return back()->with('success', 'Repasse registrado.');
    }

    /**
     * Corrige um repasse já lançado (data, valor, parcela, documento).
     */
    public function updateRepasse(Request $request, Repasse $repasse): RedirectResponse
    {
        $data = $request->validate([
            'parcela'      => ['nullable', 'integer', 'min:1'],
            'data_repasse' => ['required', 'date'],
            'valor'        => ['required', 'numeric', 'min:0.01'],
            'documento'    => ['nullable', 'string', 'max:255'],
            'observacao'   => ['nullable', 'string', 'max:255'],
        ]);

        $repasse->update($data);

        return redirect()->route('instrumentos.execucao', $repasse->instrumento)
            ->with('success', 'Repasse atualizado.');
    }

    public function destroyRepasse(Repasse $repasse): RedirectResponse
    {
        $instrumento = $repasse->instrumento;
        $repasse->delete();

        return redirect()->route('instrumentos.execucao', $instrumento)
            ->with('success', 'Repasse removido.');
    }

    public function downloadNotaFiscal(Despesa $despesa): StreamedResponse
    {
        abort_unless($despesa->nota_fiscal_path && Storage::disk('local')->exists($despesa->nota_fiscal_path), 404);

        return Storage::disk('local')->download($despesa->nota_fiscal_path, $despesa->nota_fiscal_nome);
    }
}
