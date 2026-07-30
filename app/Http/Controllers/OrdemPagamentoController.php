<?php

namespace App\Http\Controllers;

use App\Models\Instrumento;
use App\Models\OrdemPagamento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrdemPagamentoController extends Controller
{
    public function create(Request $request, Instrumento $instrumento): RedirectResponse
    {
        abort_unless($instrumento->status === 'vigente', 403,
            'Ordens de pagamento só podem ser emitidas em instrumentos vigentes.');

        $tipo = $request->input('tipo') === 'global' ? 'global' : 'parcial';

        // O empenho global é solicitado uma única vez por instrumento.
        if ($tipo === 'global' && $instrumento->ordensPagamento()->where('tipo', 'global')->exists()) {
            return back()->with('info', 'Este instrumento já possui uma Ordem de Pagamento Global.');
        }

        $numero = (int) $instrumento->ordensPagamento()->max('numero') + 1;

        $op = $instrumento->ordensPagamento()->create([
            'numero'   => $numero,
            'tipo'     => $tipo,
            'conteudo' => OrdemPagamento::conteudoInicial($instrumento->loadMissing('proposta.osc'), $numero, auth()->user()?->name, $tipo),
        ]);

        return redirect()->route('ordens-pagamento.edit', $op)
            ->with('success', 'Ordem de pagamento criada. Preencha os dados, anexe o comprovante bancário e assine.');
    }

    public function edit(OrdemPagamento $ordem): View
    {
        $ordem->load('instrumento.proposta.osc', 'assinante');

        return view('ordens-pagamento.edit', ['op' => $ordem, 'instrumento' => $ordem->instrumento]);
    }

    public function update(Request $request, OrdemPagamento $ordem): RedirectResponse
    {
        abort_if($ordem->assinado(), 403, 'A ordem de pagamento já está assinada e não pode ser editada.');

        $data = $request->validate([
            'favorecido'   => ['nullable', 'string', 'max:255'],
            'valor'        => ['nullable', 'numeric', 'min:0'],
            'data_emissao' => ['nullable', 'date'],
            'conteudo'     => ['nullable', 'string'],
        ]);

        $ordem->update($data);

        return redirect()->route('ordens-pagamento.edit', $ordem)
            ->with('success', 'Ordem de pagamento salva.');
    }

    public function assinar(OrdemPagamento $ordem): RedirectResponse
    {
        abort_if($ordem->assinado(), 403, 'Esta ordem de pagamento já está assinada.');
        abort_if(empty($ordem->conteudo), 422, 'Preencha o documento antes de assinar.');

        $ordem->update([
            'assinado_por'     => auth()->id(),
            'assinado_em'      => now(),
            'codigo_validacao' => $ordem->codigo_validacao ?: OrdemPagamento::gerarCodigoValidacao(),
        ]);

        return redirect()->route('ordens-pagamento.edit', $ordem)
            ->with('success', 'Ordem de pagamento assinada eletronicamente.');
    }

    public function uploadDadosBancarios(Request $request, OrdemPagamento $ordem): RedirectResponse
    {
        $request->validate([
            'arquivo' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        if ($ordem->dados_bancarios_path) {
            Storage::disk('local')->delete($ordem->dados_bancarios_path);
        }

        $file = $request->file('arquivo');
        $ordem->update([
            'dados_bancarios_path' => $file->store('ordens-pagamento', 'local'),
            'dados_bancarios_nome' => $file->getClientOriginalName(),
        ]);

        return back()->with('success', 'Dados bancários anexados.');
    }

    public function downloadDadosBancarios(OrdemPagamento $ordem): StreamedResponse
    {
        abort_unless($ordem->dados_bancarios_path && Storage::disk('local')->exists($ordem->dados_bancarios_path), 404);

        return Storage::disk('local')->download($ordem->dados_bancarios_path, $ordem->dados_bancarios_nome);
    }

    public function imprimir(OrdemPagamento $ordem): View
    {
        $ordem->load('instrumento', 'assinante.roles', 'assinante.orgao');

        $qrValidacao = null;
        if ($ordem->assinado() && $ordem->codigo_validacao) {
            $qrValidacao = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                ->size(110)->margin(0)
                ->generate(route('validacao.mostrar', $ordem->codigo_validacao));
        }

        return view('ordens-pagamento.impressao', ['op' => $ordem, 'instrumento' => $ordem->instrumento, 'qrValidacao' => $qrValidacao]);
    }

    public function destroy(OrdemPagamento $ordem): RedirectResponse
    {
        $instrumento = $ordem->instrumento;

        if ($ordem->dados_bancarios_path) {
            Storage::disk('local')->delete($ordem->dados_bancarios_path);
        }
        $ordem->delete();

        return redirect()->route('instrumentos.show', $instrumento)
            ->with('success', 'Ordem de pagamento removida.');
    }
}
