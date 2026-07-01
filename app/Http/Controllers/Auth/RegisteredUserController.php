<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Orgao;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Formulário de solicitação de acesso (servidores internos).
     */
    public function create(): View
    {
        // OSC tem fluxo próprio pelo portal; aqui é só para servidores internos.
        $setores = collect(User::LOTACOES)->except('osc');
        $orgaos  = Orgao::where('status', true)->orderBy('name')->get();

        return view('auth.register', compact('setores', 'orgaos'));
    }

    /**
     * Cria o cadastro como PENDENTE (sem perfil e sem login) — o administrador
     * libera o acesso e atribui os perfis na aprovação.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $setoresValidos = array_keys(collect(User::LOTACOES)->except('osc')->all());

        $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'email'           => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'cpf'             => ['nullable', 'string', 'max:14', 'unique:users,cpf'],
            'phone'           => ['nullable', 'string', 'max:20'],
            'setor'           => ['required', Rule::in($setoresValidos)],
            'orgao_id'        => ['nullable', 'required_if:setor,ug', 'exists:orgaos,id'],
            'password'        => ['required', 'confirmed', Rules\Password::defaults()],
            'solicitacao_obs' => ['nullable', 'string', 'max:1000'],
        ], [
            'orgao_id.required_if' => 'Selecione a Secretaria/Unidade Gestora.',
        ]);

        User::create([
            'name'            => $request->name,
            'email'           => $request->email,
            'cpf'             => $request->cpf,
            'phone'           => $request->phone,
            'setor'           => $request->setor,
            'orgao_id'        => $request->orgao_id,
            'password'        => Hash::make($request->password),
            'status'          => true,
            'approval_status' => 'pendente',
            'solicitacao_obs' => $request->solicitacao_obs,
        ]);

        return redirect()->route('login')->with('status',
            'Cadastro enviado! Seu acesso será liberado após a aprovação do administrador.');
    }
}
