<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

/**
 * Gestão de subusuários pela Unidade Gestora. A UG cadastra usuários da sua
 * Secretaria, que ficam PENDENTES até o administrador do sistema aprovar e
 * atribuir os perfis.
 */
class SubusuarioController extends Controller
{
    public function index(): View
    {
        $subusuarios = auth()->user()->subusuarios()
            ->with('roles')
            ->orderByDesc('created_at')
            ->get();

        return view('subusuarios.index', compact('subusuarios'));
    }

    public function create(): View
    {
        abort_if(! auth()->user()->orgao_id, 403,
            'Seu usuário não está vinculado a uma Secretaria/Unidade Gestora.');

        // Quem cadastra escolhe os perfis: é quem sabe o que a pessoa vai
        // fazer no setor. O administrador deixa de adivinhar isso na aprovação.
        $perfis = auth()->user()->perfisQuePodeConceder();

        return view('subusuarios.create', compact('perfis'));
    }

    public function store(Request $request): RedirectResponse
    {
        $ug = auth()->user();

        abort_if(! $ug->orgao_id, 403,
            'Seu usuário não está vinculado a uma Secretaria/Unidade Gestora.');

        $permitidos = $ug->perfisQuePodeConceder();

        $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'email'           => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'matricula'       => ['required', 'string', 'max:50', 'unique:users,matricula'],
            'cpf'             => ['nullable', 'string', 'max:14', 'unique:users,cpf'],
            'phone'           => ['nullable', 'string', 'max:20'],
            'password'        => ['required', 'confirmed', Rules\Password::defaults()],
            'solicitacao_obs' => ['required', 'string', 'max:1000'],
            // A lista permitida vem do servidor, não do formulário: sem isso
            // bastaria forjar o POST para conceder 'administrador_setorial'.
            'perfis'          => ['required', 'array', 'min:1'],
            'perfis.*'        => ['string', Rule::in(array_keys($permitidos))],
        ], [
            'matricula.required'       => 'Informe a matrícula do servidor.',
            'solicitacao_obs.required' => 'Informe a função / observação do usuário.',
            'perfis.required'          => 'Escolha ao menos um perfil para o usuário.',
            'perfis.*.in'              => 'Perfil fora do que você pode conceder.',
        ]);

        $usuario = User::create([
            'name'            => $request->name,
            'email'           => $request->email,
            'matricula'       => $request->matricula,
            'cpf'             => $request->cpf,
            'phone'           => $request->phone,
            'setor'           => $ug->setor,      // herda o setor da UG
            'orgao_id'        => $ug->orgao_id,    // herda a Secretaria/UG
            'password'        => Hash::make($request->password),
            'status'          => true,
            'approval_status' => 'pendente',
            'created_by'      => $ug->id,
            'solicitacao_obs' => $request->solicitacao_obs,
        ]);

        // Os perfis já ficam no usuário, mas ele segue 'pendente' e não
        // autentica (podeAutenticar exige aprovado + ativo). Assim a tela de
        // aprovação mostra a escolha de quem cadastrou, em vez de o
        // administrador ter de adivinhar a função da pessoa.
        $usuario->syncRoles($request->perfis);

        return redirect()->route('subusuarios.index')->with('success',
            'Subusuário criado com os perfis escolhidos. Aguarde a aprovação do administrador para liberar o acesso.');
    }
}
