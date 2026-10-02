<?php

namespace App\Http\Controllers;

use App\Support\CaixaDeEntrada;
use Illuminate\View\View;

/** Caixa de entrada: fora dos grupos de permissão, porque depende da lotação, não de um módulo. */
class CaixaController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();
        abort_unless($user->setor, 403, 'Seu usuário não está vinculado a nenhum setor.');

        return view('caixa', [
            'caixa' => CaixaDeEntrada::para($user),
            'setor' => $user->setorLabel(),
        ]);
    }
}
