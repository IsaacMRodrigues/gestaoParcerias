<?php

namespace App\Http\Controllers;

use App\Support\CaixaDeEntrada;
use Illuminate\View\View;

/**
 * Caixa de entrada do setor — o que está parado esperando você.
 *
 * Fica fora dos grupos de `permission:` de propósito: o que define a caixa é a
 * LOTAÇÃO do usuário, não a permissão de um módulo. Amarrada a
 * `permission:planejamento`, como estava, ela excluía setores que participam do
 * fluxo por outros trâmites — o Responsável pela Publicação (só `chamamentos`)
 * nem conseguia abrir a tela. Quem filtra o conteúdo é a própria
 * CaixaDeEntrada, que só consulta os trâmites que o usuário pode ver.
 */
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
