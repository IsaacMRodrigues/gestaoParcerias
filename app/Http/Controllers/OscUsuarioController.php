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
 * Equipe da OSC: o responsável legal cadastra as contas da própria organização. O cadastro
 * nasce pendente e é aprovado pela Prefeitura; o membro só enxerga a sua OSC.
 */
class OscUsuarioController extends Controller
{
    public function index(): View
    {
        $osc = $this->oscDoResponsavel();

        $usuarios = $osc->usuarios()
            ->with('roles', 'permissions')
            ->orderByDesc('id')
            ->get();

        return view('portal.usuarios.index', [
            'osc'      => $osc,
            'usuarios' => $usuarios,
            'funcoes'  => User::FUNCOES_OSC,
            'perfis'   => User::PERFIS_OSC,
        ]);
    }

    public function create(): View
    {
        $osc = $this->oscDoResponsavel();

        return view('portal.usuarios.create', [
            'osc'    => $osc,
            'perfis' => User::PERFIS_OSC,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $osc  = $this->oscDoResponsavel();
        $dono = $request->user();

        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'cpf'      => ['nullable', 'string', 'max:14', 'unique:users,cpf'],
            'phone'    => ['nullable', 'string', 'max:20'],
            'cargo'    => ['nullable', 'string', 'max:100'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            // A lista permitida vem do servidor: sem isto bastaria forjar o POST
            // para conceder à conta nova um perfil da Administração.
            'perfis'    => ['nullable', 'array'],
            'perfis.*'  => ['string', Rule::in(array_keys(User::PERFIS_OSC))],
        ], [
            'name.required'     => 'Informe o nome do integrante.',
            'email.unique'      => 'Já existe uma conta com este e-mail.',
            'password.required' => 'Defina uma senha inicial para o integrante.',
            'perfis.*.in'       => 'Perfil fora dos que você pode conceder.',
        ]);

        $usuario = User::create([
            'name'            => $request->name,
            'email'           => $request->email,
            'cpf'             => $request->cpf,
            'phone'           => $request->phone,
            'osc_id'          => $osc->id,
            'password'        => Hash::make($request->password),
            // O responsável legal conhece a senha que definiu: a pessoa troca
            // no primeiro acesso (ver ExigeTrocaDeSenha).
            'deve_trocar_senha' => true,
            'status'          => true,
            // Nasce pendente: a Prefeitura aprova (o login barra pendente, ver User::podeAutenticar).
            'approval_status' => 'pendente',
            'created_by'      => $dono->id,
            'solicitacao_obs' => $request->cargo,
        ]);

        // 'membro_osc' diz de quem a pessoa é e nunca falta; os demais perfis saem na assinatura.
        $usuario->syncRoles($this->perfisMarcados($request));

        // Entra com todas as funções; restringir é pelo "Alterar" da lista da equipe. O que vincula
        // a organização (submeter, recorrer) é do responsável legal.
        $usuario->syncPermissions(array_keys(User::FUNCOES_OSC));

        return redirect()->route('portal.usuarios.index')->with('success',
            "Cadastro de {$usuario->name} enviado para aprovação da Prefeitura. "
            .'Repasse a senha definida: ela vale a partir da liberação.');
    }

    /** Troca as funções de um integrante. */
    public function funcoes(Request $request, User $usuario): RedirectResponse
    {
        $osc = $this->oscDoResponsavel();

        abort_unless($usuario->osc_id === $osc->id, 403);

        // O responsável legal responde pela entidade: as funções dele vêm do
        // papel e não estão em disputa, nem pelas mãos dele mesmo.
        abort_if($usuario->id === $osc->user_id, 403,
            'As funções do responsável legal não são alteráveis.');

        $request->validate([
            'funcoes'   => ['nullable', 'array'],
            'funcoes.*' => ['string', Rule::in(array_keys(User::FUNCOES_OSC))],
            'perfis'    => ['nullable', 'array'],
            'perfis.*'  => ['string', Rule::in(array_keys(User::PERFIS_OSC))],
        ], [
            'funcoes.*.in' => 'Função fora das que você pode conceder.',
            'perfis.*.in'  => 'Perfil fora dos que você pode conceder.',
        ]);

        $usuario->syncRoles($this->perfisMarcados($request));
        $usuario->syncPermissions($request->input('funcoes', []));

        return back()->with('success', "Perfil e funções de {$usuario->name} atualizados.");
    }

    /** Perfis a gravar: os marcados, sempre com 'membro_osc' (sem ele a conta não é de lado nenhum). */
    private function perfisMarcados(Request $request): array
    {
        return array_values(array_unique([
            'membro_osc',
            ...$request->input('perfis', []),
        ]));
    }

    /** Liga/desliga o acesso: a alternativa à exclusão (a conta segue respondendo pelo que fez). */
    public function alternarAcesso(User $usuario): RedirectResponse
    {
        $osc  = $this->oscDoResponsavel();
        $dono = request()->user();

        abort_unless($usuario->osc_id === $osc->id, 403);

        abort_if($usuario->id === $dono->id, 403,
            'Você não pode desativar o próprio acesso de responsável legal.');

        $usuario->update(['status' => ! $usuario->status]);

        return back()->with('success', $usuario->status
            ? "Acesso de {$usuario->name} reativado."
            : "Acesso de {$usuario->name} suspenso.");
    }

    /** A OSC de quem é o titular do cadastro (oscs.user_id): só ele cadastra a equipe. */
    private function oscDoResponsavel(): \App\Models\Osc
    {
        $user = request()->user();

        abort_unless($user->ehResponsavelLegalOsc(), 403,
            'Apenas o responsável legal da OSC administra os acessos da organização.');

        return $user->osc;
    }
}
