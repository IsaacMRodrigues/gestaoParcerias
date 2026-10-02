<?php

namespace App\Http\Controllers;

use App\Models\Chamado;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * "Esqueci minha senha" abre um chamado de Acesso; o suporte confirma a identidade e define
 * uma senha provisória. Única porta do suporte sem login, por isso: limite por IP na rota,
 * campo-isca, a mesma resposta exista a conta ou não, e pedido repetido no chamado aberto.
 */
class PedidoDeSenhaController extends Controller
{
    public function create(): View
    {
        return view('auth.esqueci-a-senha');
    }

    public function store(Request $request): RedirectResponse
    {
        $resposta = redirect()->route('login')->with('status',
            'Pedido registrado. A equipe de suporte vai confirmar sua identidade pelo contato '
            .'informado e passar uma senha provisória, que você troca no primeiro acesso.');

        // Campo-isca: invisível na tela, só um robô o preenche. Recebe a mesma
        // resposta, para não aprender que foi barrado.
        if (filled($request->input('site'))) {
            return $resposta;
        }

        $dados = $request->validate([
            'identificacao' => ['required', 'string', 'max:255'],
            'nome'          => ['required', 'string', 'max:120'],
            'contato'       => ['required', 'string', 'max:120'],
            'mensagem'      => ['nullable', 'string', 'max:1000'],
        ], [
            'identificacao.required' => 'Informe o e-mail ou o nome de usuário com que você entra.',
            'nome.required'          => 'Informe seu nome.',
            'contato.required'       => 'Informe um telefone ou e-mail para a equipe confirmar que é você.',
        ]);

        $identificacao = mb_strtolower(trim($dados['identificacao']));
        $conta = User::where('email', $identificacao)->orWhere('login', $identificacao)->first();

        $texto = "Pedido de nova senha feito na tela de entrada, sem login.\n"
            ."Conta informada: {$dados['identificacao']}\n"
            ."Contato para confirmação: {$dados['contato']}"
            .(filled($dados['mensagem'] ?? null) ? "\n\n{$dados['mensagem']}" : '');

        DB::transaction(function () use ($dados, $conta, $texto) {
            // Pedido repetido para a mesma conta: continua o chamado em aberto,
            // em vez de empilhar outro igual na fila.
            $chamado = $conta
                ? Chamado::emAberto()->where('categoria', 'acesso')->where('conta_id', $conta->id)->first()
                : null;

            $chamado ??= Chamado::create([
                'numero'        => Chamado::proximoNumero(),
                'user_id'       => null,
                'conta_id'      => $conta?->id,
                'contato'       => $dados['contato'],
                'autor_nome'    => $dados['nome'],
                'autor_vinculo' => 'Pedido sem login',
                'categoria'     => 'acesso',
                'assunto'       => 'Nova senha — ' . mb_substr($dados['identificacao'], 0, 120),
                'status'        => 'aberto',
                'origem_url'    => route('password.request', absolute: false),
            ]);

            $chamado->mensagens()->create([
                'user_id'       => null,
                'autor_nome'    => $dados['nome'],
                'autor_vinculo' => 'Pedido sem login',
                'mensagem'      => $texto,
                'interna'       => false,
            ]);
        });

        return $resposta;
    }
}
