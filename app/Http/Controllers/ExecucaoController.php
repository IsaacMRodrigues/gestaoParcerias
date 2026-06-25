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
