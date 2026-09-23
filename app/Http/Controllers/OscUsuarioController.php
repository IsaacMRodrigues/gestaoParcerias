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
 * Quem indica a pessoa é a entidade; quem abre a porta é a Prefeitura. O
 * cadastro nasce pendente e vai para a mesma fila das outras contas, decidida
 * pelo TI/Administrador ou pela SCP. O alcance é contido por natureza — um
 * membro só enxerga a OSC a que pertence.
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
            'osc'     => $osc,
            'perfis'  => User::PERFIS_OSC,
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
            // para conceder à conta nova um perfil da Administração.
            'perfis'    => ['nullable', 'array'],
            'perfis.*'  => ['string', Rule::in(array_keys(User::PERFIS_OSC))],
            'funcoes'   => ['nullable', 'array'],
            'funcoes.*' => ['string', Rule::in(array_keys(User::FUNCOES_OSC))],
        ], [
            'name.required'     => 'Informe o nome do integrante.',
            'email.unique'      => 'Já existe uma conta com este e-mail.',
            'password.required' => 'Defina uma senha inicial para o integrante.',
            'perfis.*.in'       => 'Perfil fora dos que você pode conceder.',
            'funcoes.*.in'      => 'Função fora das que você pode conceder.',
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
            // Nasce pendente: quem responde pela entidade indica a pessoa, mas
            // quem abre a porta do sistema é a Prefeitura (TI/Administrador ou
            // SCP), como já acontece com servidor e equipe de setor. O login
            // barra pendente — ver User::podeAutenticar().
            'approval_status' => 'pendente',
            'created_by'      => $dono->id,
            'solicitacao_obs' => $request->cargo,
        ]);

        // O papel 'membro_osc' diz de quem a pessoa é ("equipe desta OSC") e
        // nunca falta; os demais perfis dizem o que ela é na organização e saem
        // impressos como papel de assinatura.
        $usuario->syncRoles($this->perfisMarcados($request));

        // As funções vêm marcadas no formulário — quem abre a conta raramente
        // sabe de antemão o que a pessoa vai pegar —, mas quem cadastra pode
        // desmarcar ali mesmo. O que vincula juridicamente a organização —
        // submeter, recorrer, contra-assinar — nunca esteve aqui: é do
        // responsável legal.
        $usuario->syncPermissions($request->input('funcoes', []));

        return redirect()->route('portal.usuarios.index')->with('success',
            "Cadastro de {$usuario->name} enviado para aprovação da Prefeitura. "
            .'Repasse a senha definida: ela vale a partir da liberação.');
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

    /**
     * Perfis a gravar: os marcados, sempre com 'membro_osc' junto.
     *
     * O papel de equipe não é opcional — é ele que diz que a conta pertence a
     * esta OSC e não à Administração. Um formulário devolvido sem ele (caixa
     * desmarcada no navegador, POST forjado) deixaria a pessoa sem papel
     * nenhum, e uma conta sem papel não é de lado nenhum.
     */
    private function perfisMarcados(Request $request): array
    {
        return array_values(array_unique([
            'membro_osc',
            ...$request->input('perfis', []),
        ]));
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
