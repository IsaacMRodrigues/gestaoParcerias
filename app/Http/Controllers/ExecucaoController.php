<?php

namespace App\Http\Controllers;

use App\Models\Despesa;
use App\Models\Instrumento;
use App\Models\Repasse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExecucaoController extends Controller
{
    /**
     * Lista as parcerias em execução — é por aqui que se escolhe de qual
     * instrumento se quer ver repasses, despesas e saldo.
     */
    public function index(Request $request): View
    {
        $filtros = $request->only(['busca', 'status']);

        $instrumentos = Instrumento::with(['proposta.osc'])
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
            ->orderByRaw("FIELD(status, 'vigente', 'assinado', 'encerrado')")
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

    public function storeDespesa(Request $request, Instrumento $instrumento): RedirectResponse
    {
        $data = $request->validate([
            'data_despesa'       => ['required', 'date'],
            'valor'              => ['required', 'numeric', 'min:0.01'],
            'natureza'           => ['required', Rule::in(array_keys(Despesa::NATUREZAS))],
            'fornecedor'         => ['nullable', 'string', 'max:255'],
            'doc_fornecedor'     => ['nullable', 'string', 'max:18'],
            'descricao'          => ['nullable', 'string', 'max:255'],
            'nota_fiscal_numero' => ['nullable', 'string', 'max:255'],
            'nota_fiscal'        => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $despesa = $instrumento->despesas()->make($request->only([
            'data_despesa', 'valor', 'natureza', 'fornecedor', 'doc_fornecedor', 'descricao', 'nota_fiscal_numero',
        ]));

        if ($request->hasFile('nota_fiscal')) {
            $file = $request->file('nota_fiscal');
            $despesa->nota_fiscal_path = $file->store('notas-fiscais', 'local');
            $despesa->nota_fiscal_nome = $file->getClientOriginalName();
        }

        $despesa->save();

        return back()->with('success', 'Despesa registrada.');
    }

    /**
     * Corrige uma despesa já lançada. A nota fiscal pode ser **anexada depois**
     * (caso comum: a despesa é registrada antes de a nota chegar), substituída
     * ou removida.
     */
    public function updateDespesa(Request $request, Despesa $despesa): RedirectResponse
    {
        $data = $request->validate([
            'data_despesa'       => ['required', 'date'],
            'valor'              => ['required', 'numeric', 'min:0.01'],
            'natureza'           => ['required', Rule::in(array_keys(Despesa::NATUREZAS))],
            'fornecedor'         => ['nullable', 'string', 'max:255'],
            'doc_fornecedor'     => ['nullable', 'string', 'max:18'],
            'descricao'          => ['nullable', 'string', 'max:255'],
            'nota_fiscal_numero' => ['nullable', 'string', 'max:255'],
            'nota_fiscal'        => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'remover_nota'       => ['nullable', 'boolean'],
        ], [
            'nota_fiscal.mimes' => 'A nota fiscal deve ser PDF, JPG ou PNG.',
            'nota_fiscal.max'   => 'A nota fiscal não pode ultrapassar 10 MB.',
        ]);

        $despesa->fill(collect($data)->only([
            'data_despesa', 'valor', 'natureza', 'fornecedor',
            'doc_fornecedor', 'descricao', 'nota_fiscal_numero',
        ])->all());

        // Novo arquivo substitui o anterior; a caixa "remover" apaga o atual.
        if ($request->hasFile('nota_fiscal')) {
            if ($despesa->nota_fiscal_path) {
                Storage::disk('local')->delete($despesa->nota_fiscal_path);
            }
            $file = $request->file('nota_fiscal');
            $despesa->nota_fiscal_path = $file->store('notas-fiscais', 'local');
            $despesa->nota_fiscal_nome = $file->getClientOriginalName();
        } elseif ($request->boolean('remover_nota') && $despesa->nota_fiscal_path) {
            Storage::disk('local')->delete($despesa->nota_fiscal_path);
            $despesa->nota_fiscal_path = null;
            $despesa->nota_fiscal_nome = null;
        }

        $despesa->save();

        return redirect()->route('instrumentos.execucao', $despesa->instrumento)
            ->with('success', 'Despesa atualizada.');
    }

    public function destroyDespesa(Despesa $despesa): RedirectResponse
    {
        $instrumento = $despesa->instrumento;

        if ($despesa->nota_fiscal_path) {
            Storage::disk('local')->delete($despesa->nota_fiscal_path);
        }
        $despesa->delete();

        return redirect()->route('instrumentos.execucao', $instrumento)
            ->with('success', 'Despesa removida.');
    }

    public function downloadNotaFiscal(Despesa $despesa): StreamedResponse
    {
        abort_unless($despesa->nota_fiscal_path && Storage::disk('local')->exists($despesa->nota_fiscal_path), 404);

        return Storage::disk('local')->download($despesa->nota_fiscal_path, $despesa->nota_fiscal_nome);
    }
}
