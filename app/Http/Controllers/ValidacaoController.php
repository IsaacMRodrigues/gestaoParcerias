<?php

namespace App\Http\Controllers;

use App\Models\ProcessoPeca;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Validação pública da autenticidade de documentos assinados.
 * Acessível sem login (igual aos portais de validação de documentos).
 */
class ValidacaoController extends Controller
{
    public function index(): View
    {
        return view('validacao.index');
    }

    public function verificar(Request $request)
    {
        $request->validate(['codigo' => ['required', 'string']]);

        return redirect()->route('validacao.mostrar', ['codigo' => trim($request->codigo)]);
    }

    public function mostrar(string $codigo): View
    {
        $peca = ProcessoPeca::with(['assinante', 'processo.orgao'])
            ->whereNotNull('assinado_em')
            ->where('codigo_validacao', strtoupper(trim($codigo)))
            ->first();

        return view('validacao.mostrar', compact('peca', 'codigo'));
    }
}
