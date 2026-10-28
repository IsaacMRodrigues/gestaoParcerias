<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Orgao;
use App\Models\User;
use App\Support\TermosDeUso;
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
        // Uma lista só: os perfis exclusivos de um setor aparecem sempre, com o aviso, e marcá-los escolhe o setor.
        $perfis = $setores->keys()->flatMap(fn ($s) => User::perfisDoAutoCadastro($s))
            ->sortBy(fn ($r, $slug) => $slug === 'responsavel_unidade_gestora' ? 0 : 1)->all();
        $exclusivos = collect(User::PERFIS_EXCLUSIVOS)->only(array_keys($perfis))->all();

        return view('auth.register', compact('setores', 'orgaos', 'perfis', 'exclusivos'));
    }

    /**
     * Cria o cadastro pendente com os perfis que a pessoa pediu no seu setor; a SCP (ou o
     * administrador) confere a identidade e o pedido e libera o acesso.
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
            'matricula'       => ['nullable', 'string', 'max:30', 'unique:users,matricula'],
            'setor'           => ['required', Rule::in($setoresValidos)],
            'orgao_id'        => ['nullable', 'required_if:setor,ug', 'exists:orgaos,id'],
            'perfis'          => ['required', 'array', 'min:1'],
            'perfis.*'        => [Rule::in(array_keys(User::perfisDoAutoCadastro((string) $request->input('setor'))))],
            'password'        => ['required', 'confirmed', Rules\Password::defaults()],
            'solicitacao_obs' => ['nullable', 'string', 'max:1000'],
            'aceite_termos'   => ['accepted'],
        ], [
            'aceite_termos.accepted' => 'Para se cadastrar, leia e aceite os Termos de Uso.',
            'orgao_id.required_if' => 'Selecione a Secretaria/Unidade Gestora.',
            'perfis.required'      => 'Escolha ao menos um perfil: é o que você vai fazer no sistema.',
            'perfis.*.in'          => 'Um dos perfis escolhidos não pode ser pedido neste setor.',
        ]);

        if ($conflito = User::conflitoDeEncargos($request->input('perfis'))) {
            throw ValidationException::withMessages(['perfis' => $conflito]);
        }

        $user = User::create([
            'name'            => $request->name,
            'email'           => $request->email,
            'cpf'             => $request->cpf,
            'phone'           => $request->phone,
            'matricula'       => $request->matricula,
            'setor'           => $request->setor,
            'orgao_id'        => $request->orgao_id,
            'password'        => Hash::make($request->password),
            'status'          => true,
            'approval_status' => 'pendente',
            'solicitacao_obs' => $request->solicitacao_obs,
        ]);
        // Pendente, os perfis não abrem nada: podeAutenticar() exige a aprovação.
        $user->syncRoles($request->input('perfis'));
        TermosDeUso::registrarAceite($user, 'cadastro');

        return redirect()->route('login')->with('status',
            'Cadastro enviado! Seu acesso será liberado depois que o Setor de Convênios e Parcerias (SCP) conferir os seus dados.');
    }
}
