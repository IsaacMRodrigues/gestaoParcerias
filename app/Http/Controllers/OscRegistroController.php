<?php

namespace App\Http\Controllers;

use App\Http\Requests\OscRegistroRequest;
use App\Models\Osc;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class OscRegistroController extends Controller
{
    public function create(): View
    {
        return view('auth.register-osc');
    }

    public function store(OscRegistroRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $user = User::create([
                'name'     => $request->resp_nome,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'cpf'      => $request->cpf,
                'phone'    => $request->resp_phone,
                'status'   => true,
            ]);

            $user->assignRole('responsavel_legal');

            $osc = Osc::create([
                'user_id'     => $user->id,
                'name'        => $request->osc_name,
                'cnpj'        => $request->cnpj,
                'tipo'        => $request->tipo,
                'email'       => $request->osc_email ?: $request->email,
                'phone'       => $request->osc_phone,
                'resp_nome'   => $request->resp_nome,
                'resp_cpf'    => $request->cpf,
                'resp_email'  => $request->email,
                'resp_phone'  => $request->resp_phone,
                'logradouro'  => $request->logradouro,
                'numero'      => $request->numero,
                'complemento' => $request->complemento,
                'bairro'      => $request->bairro,
                'cidade'      => $request->cidade,
                'estado'      => $request->estado,
                'cep'         => $request->cep,
                'status'      => true,
            ]);

            // Os dois lados do vínculo: oscs.user_id marca quem responde
            // legalmente; users.osc_id é o que dá acesso ao portal (e o que
            // permite à organização ter mais de uma conta).
            $user->update(['osc_id' => $osc->id]);

            Auth::login($user);
        });

        return redirect()->intended(route('portal.index'))
            ->with('success', 'Cadastro realizado! Bem-vindo ao Portal de Parcerias.');
    }
}
