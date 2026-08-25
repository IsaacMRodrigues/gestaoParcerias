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
 * Cadastro da equipe do setor pela própria chefia.
 *
 * Nasceu só para a Unidade Gestora; SCP, SEPLAN, PJ e Gabinete dependiam do
 * administrador criar cada conta — quem conhece a equipe não era quem
 * cadastrava. Agora vale para qualquer setor, por meio da permissão
 * `usuarios_setor` (perfil `chefe_setor`), e o administrador segue sendo quem
 * libera: o usuário nasce PENDENTE e não autentica antes da aprovação.
 *
 * Cada chefia cadastra apenas o próprio setor — o usuário criado herda setor e
 * órgão de quem o cadastrou, sem campo no formulário para escolher outro.
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
        // A trava é a lotação, não o órgão: fora da UG ninguém tem orgao_id
        // (SCP, SEPLAN, PJ e Gabinete são transversais) e a exigência de órgão
        // trancava a tela justamente para os setores que ela veio atender.
        abort_if(! auth()->user()->setor, 403,
            'Seu usuário não está lotado em nenhum setor.');

        // Quem cadastra escolhe os perfis: é quem sabe o que a pessoa vai
        // fazer no setor. O administrador deixa de adivinhar isso na aprovação.
        $perfis = auth()->user()->perfisQuePodeConceder();

        return view('subusuarios.create', compact('perfis'));
    }

    public function store(Request $request): RedirectResponse
    {
        $chefe = auth()->user();

        abort_if(! $chefe->setor, 403,
            'Seu usuário não está lotado em nenhum setor.');

        $permitidos = $chefe->perfisQuePodeConceder();

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
            'setor'           => $chefe->setor,     // herda o setor de quem cadastra
            'orgao_id'        => $chefe->orgao_id,  // e a Secretaria, quando houver
            'password'        => Hash::make($request->password),
            'status'          => true,
            'approval_status' => 'pendente',
            'created_by'      => $chefe->id,
            'solicitacao_obs' => $request->solicitacao_obs,
        ]);

        // Os perfis já ficam no usuário, mas ele segue 'pendente' e não
        // autentica (podeAutenticar exige aprovado + ativo). Assim a tela de
        // aprovação mostra a escolha de quem cadastrou, em vez de o
        // administrador ter de adivinhar a função da pessoa.
        $usuario->syncRoles($request->perfis);

        return redirect()->route('subusuarios.index')->with('success',
            'Usuário criado com os perfis escolhidos. Aguarde a aprovação do administrador para liberar o acesso.');
    }
}
