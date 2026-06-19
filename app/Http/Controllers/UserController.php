<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::with('roles')->orderBy('name')->paginate(15);

        return view('usuarios.index', compact('users'));
    }

    public function create(): View
    {
        $roles = Role::where('name', '!=', 'responsavel_legal')->orderBy('name')->get();

        return view('usuarios.create', compact('roles'));
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'cpf'      => $request->cpf,
            'phone'    => $request->phone,
            'setor'    => $request->setor,
            'status'   => $request->boolean('status', true),
            'password' => bcrypt($request->password),
        ]);

        $user->syncRoles($request->roles);

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuário cadastrado com sucesso.');
    }

    public function edit(User $usuario): View
    {
        $roles = Role::where('name', '!=', 'responsavel_legal')->orderBy('name')->get();

        return view('usuarios.edit', compact('usuario', 'roles'));
    }

    public function update(UserRequest $request, User $usuario): RedirectResponse
    {
        $data = [
            'name'   => $request->name,
            'email'  => $request->email,
            'cpf'    => $request->cpf,
            'phone'  => $request->phone,
            'setor'  => $request->setor,
            'status' => $request->boolean('status', true),
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
        $usuario->delete();

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuário removido com sucesso.');
    }
}
