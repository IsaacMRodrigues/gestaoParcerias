<?php

namespace App\Http\Controllers;

use App\Models\Instrumento;
use App\Models\OrdemPagamento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class OrdemPagamentoController extends Controller
{
    public function create(Request $request, Instrumento $instrumento): RedirectResponse
    {
        abort_unless($instrumento->status === 'vigente', 403,
            'Ordens de pagamento só podem ser emitidas em instrumentos vigentes.');

        // A OP Global é peça da Celebração (etapas 18 a 20); aqui, só as parciais de cada parcela.
        $tipo = 'parcial';

        $numero = (int) $instrumento->ordensPagamento()->max('numero') + 1;

        $op = $instrumento->ordensPagamento()->create([
            'numero'   => $numero,
            'tipo'     => $tipo,
            'conteudo' => OrdemPagamento::conteudoInicial($instrumento->loadMissing('proposta.osc'), $numero, auth()->user()?->name, $tipo),
        ]);

        return redirect()->route('ordens-pagamento.edit', $op)
            ->with('success', 'Ordem de pagamento criada. Preencha os dados e assine.');
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

        $quem = auth()->user()->identidadeParaAssinatura();

        $ordem->update([
            'assinado_por'     => auth()->id(),
            'assinado_em'      => now(),
            'assinante_nome'   => $quem['nome'],
            'assinante_cargo'  => $quem['cargo'],
            'codigo_validacao' => $ordem->codigo_validacao ?: OrdemPagamento::gerarCodigoValidacao(),
        ]);

        return redirect()->route('ordens-pagamento.edit', $ordem)
            ->with('success', 'Ordem de pagamento assinada eletronicamente.');
    }

    public function imprimir(OrdemPagamento $ordem): View
    {
        abort_unless(auth()->user()->can('ordem_pagamento') || auth()->user()->baixaTodosOsDocumentos(), 403);
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
