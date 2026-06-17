<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('usuario')?->id;

        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'cpf'      => ['nullable', 'string', 'max:14', Rule::unique('users', 'cpf')->ignore($userId)],
            'phone'    => ['nullable', 'string', 'max:20'],
            'password' => [$this->isMethod('POST') ? 'required' : 'nullable', 'string', 'min:8', 'confirmed'],
            'role'     => ['required', 'string', 'exists:roles,name'],
            'setor'    => ['nullable', Rule::in(array_keys(\App\Models\Processo::SETORES))],
            'status'   => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'     => 'nome',
            'email'    => 'e-mail',
            'cpf'      => 'CPF',
            'phone'    => 'telefone',
            'password' => 'senha',
            'role'     => 'perfil',
            'status'   => 'status',
        ];
    }
}
