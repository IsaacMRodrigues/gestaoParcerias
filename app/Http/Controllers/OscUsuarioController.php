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
 * Equipe da OSC: o responsável legal cadastra as contas da própria organização.
 *
 * OSC é organização, não pessoa — quem escreve o projeto raramente é quem
 * responde juridicamente por ele. Até aqui existia uma conta só por entidade,
 * e a saída prática era compartilhar a senha do responsável legal: todo mundo
 * atuando sob a mesma identidade, sem rastro de quem fez o quê.
 *
 * Os acessos valem na hora, sem passar pela Prefeitura. O município não
 * gerencia o quadro de pessoal de uma entidade privada, e o alcance é contido
 * por natureza — um membro só enxerga a OSC a que pertence.
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
        ]);
    }

    public function create(): View
    {
        $osc = $this->oscDoResponsavel();

        return view('portal.usuarios.create', [
            'osc'     => $osc,
            'funcoes' => User::FUNCOES_OSC,
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
            // para conceder à conta nova uma permissão da Prefeitura.
            'funcoes'   => ['nullable', 'array'],
            'funcoes.*' => ['string', Rule::in(array_keys(User::FUNCOES_OSC))],
        ], [
            'name.required'     => 'Informe o nome do integrante.',
            'email.unique'      => 'Já existe uma conta com este e-mail.',
            'password.required' => 'Defina uma senha inicial para o integrante.',
            'funcoes.*.in'      => 'Função fora das que você pode conceder.',
        ]);

        $usuario = User::create([
            'name'            => $request->name,
            'email'           => $request->email,
            'cpf'             => $request->cpf,
            'phone'           => $request->phone,
            'osc_id'          => $osc->id,
            'password'        => Hash::make($request->password),
            'status'          => true,
            // Vale na hora: quem responde pela entidade é quem cadastra.
            'approval_status' => 'aprovado',
            'approved_at'     => now(),
            'approved_by'     => $dono->id,
            'created_by'      => $dono->id,
            'solicitacao_obs' => $request->cargo,
        ]);

        // O papel diz de quem a pessoa é ("equipe desta OSC"); as funções dizem o
        // que ela faz, e vão na conta, não no papel — cada integrante tem a sua
        // combinação, e criar um papel por combinação não terminaria nunca.
        $usuario->assignRole('membro_osc');
        $usuario->syncPermissions($request->input('funcoes', []));

        return redirect()->route('portal.usuarios.index')->with('success',
            "Acesso criado para {$usuario->name}. Já pode entrar com o e-mail e a senha definida.");
    }

    /**
     * Troca as funções de um integrante.
     *
     * Quem faz o quê muda com o tempo — alguém sai da equipe do projeto, outro
     * assume as certidões. Sem esta tela, o engano do cadastro seria definitivo
     * e a saída viraria criar outra conta para a mesma pessoa, que é justamente
     * o que a equipe da OSC veio evitar.
     */
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
        ], [
            'funcoes.*.in' => 'Função fora das que você pode conceder.',
        ]);

        $usuario->syncPermissions($request->input('funcoes', []));

        return back()->with('success', "Funções de {$usuario->name} atualizadas.");
    }

    /**
     * Liga/desliga o acesso. É a alternativa à exclusão: a conta continua
     * respondendo pelo que assinou e enviou, mas para de entrar.
     */
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

    /**
     * A tela é do responsável legal, não de toda a OSC: quem ele cadastra não
     * cadastra outros. A rota já exige o papel; aqui vale a titularidade do
     * cadastro (oscs.user_id), que é o fato, não a atribuição de papel.
     */
    private function oscDoResponsavel(): \App\Models\Osc
    {
        $user = request()->user();

        abort_unless($user->ehResponsavelLegalOsc(), 403,
            'Apenas o responsável legal da OSC administra os acessos da organização.');

        return $user->osc;
    }
}
