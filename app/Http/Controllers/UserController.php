<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::with('roles')->orderBy('name')->paginate(15);
        $pendentesCount = User::pendentes()->count();

        return view('usuarios.index', compact('users', 'pendentesCount'));
    }

    /**
     * Cadastros aguardando aprovação (auto-cadastro de servidores e subusuários da UG).
     */
    public function pendentes(): View
    {
        $pendentes = User::pendentes()
            ->with(['orgao', 'criadoPor', 'roles'])
            ->orderBy('created_at')
            ->paginate(15);

        // A tela virou aprovar/recusar: não monta mais formulário de perfis
        // nem de lotação, então não precisa das listas.
        return view('usuarios.pendentes', compact('pendentes'));
    }

    /**
     * Aprova o cadastro: define os perfis (e confirma setor/UG) e libera o acesso.
     */
    /**
     * Aprovar é decidir sobre o cadastro, não montá-lo.
     *
     * Os perfis são escolhidos por quem cadastra — o responsável do setor, que
     * sabe a função da pessoa — e o setor/órgão vêm do próprio cadastro. Aqui
     * ficou só aprovar ou recusar. Quando não há perfil indicado (auto-cadastro,
     * que não passa por chefe), a conta é liberada sem acesso a módulo algum e
     * os perfis se definem em Cadastros → Usuários, onde eles moram.
     */
    public function aprovar(Request $request, User $usuario): RedirectResponse
    {
        abort_unless($usuario->isPendente(), 422, 'Este cadastro não está pendente.');

        // Invariante que a tela antiga garantia ao atribuir os perfis: perfil
        // exclusivo exige o setor correspondente. Continua valendo — só que
        // agora como conferência do que já foi escolhido, não como formulário.
        foreach ($usuario->roles->pluck('name') as $role) {
            $exigido = User::PERFIS_EXCLUSIVOS[$role] ?? null;
            if ($exigido && $usuario->setor !== $exigido) {
                $label = User::$roleLabels[$role] ?? $role;
                $lot   = User::LOTACOES[$exigido] ?? $exigido;
                return back()->withErrors(['roles' =>
                    "O perfil \"{$label}\" é exclusivo do setor \"{$lot}\", e este cadastro está em "
                    .'"'.($usuario->setorLabel() ?: '—').'". Ajuste em Cadastros → Usuários antes de aprovar.']);
            }
        }

        $usuario->update([
            'status'           => true,
            'approval_status'  => 'aprovado',
            'approved_at'      => now(),
            'approved_by'      => auth()->id(),
            'rejeitado_motivo' => null,
        ]);

        return back()->with('success', "Acesso de {$usuario->name} aprovado.");
    }

    /**
     * Recusa o cadastro (com motivo) — mantém o registro e bloqueia o login.
     */
    public function recusar(Request $request, User $usuario): RedirectResponse
    {
        abort_unless($usuario->isPendente(), 422, 'Este cadastro não está pendente.');

        $data = $request->validate([
            'rejeitado_motivo' => ['required', 'string', 'max:500'],
        ], [
            'rejeitado_motivo.required' => 'Informe o motivo da recusa.',
        ]);

        $usuario->update([
            'approval_status'  => 'recusado',
            'rejeitado_motivo' => $data['rejeitado_motivo'],
            'status'           => false,
            'approved_at'      => now(),
            'approved_by'      => auth()->id(),
        ]);

        return back()->with('success', "Cadastro de {$usuario->name} recusado.");
    }

    public function create(): View
    {
        $roles = Role::whereNotIn('name', User::PAPEIS_OSC)->orderBy('name')->get();

        return view('usuarios.create', compact('roles'));
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'cpf'       => $request->cpf,
            'matricula' => $request->matricula,
            'phone'     => $request->phone,
            'setor'     => $request->setor,
            'orgao_id' => $request->orgao_id,
            'status'   => $request->boolean('status', true),
            'password' => bcrypt($request->password),
        ]);

        $user->syncRoles($request->roles);

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuário cadastrado com sucesso.');
    }

    public function edit(User $usuario): View
    {
        $roles = Role::whereNotIn('name', User::PAPEIS_OSC)->orderBy('name')->get();

        return view('usuarios.edit', compact('usuario', 'roles'));
    }

    public function update(UserRequest $request, User $usuario): RedirectResponse
    {
        $data = [
            'name'      => $request->name,
            'email'     => $request->email,
            'cpf'       => $request->cpf,
            'matricula' => $request->matricula,
            'phone'     => $request->phone,
            'setor'     => $request->setor,
            'orgao_id'  => $request->orgao_id,
            'status'    => $request->boolean('status', true),
        ];

        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->password);
        }

        $usuario->update($data);
        $usuario->syncRoles($request->roles);

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuário atualizado com sucesso.');
    }

    public function destroy(User $usuario): RedirectResponse
    {
        if ($bloqueio = $this->bloqueioDeExclusao($usuario)) {
            return $bloqueio;
        }

        $usuario->delete();

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuário removido com sucesso.');
    }
}
