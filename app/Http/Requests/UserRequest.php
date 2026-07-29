<?php

namespace App\Http\Requests;

use App\Models\User;
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
            'matricula' => ['nullable', 'string', 'max:50', Rule::unique('users', 'matricula')->ignore($userId)],
            'phone'    => ['nullable', 'string', 'max:20'],
            'password' => [$this->isMethod('POST') ? 'required' : 'nullable', 'string', 'min:6', 'confirmed'],
            'roles'    => ['required', 'array', 'min:1'],
            'roles.*'  => ['string', 'exists:roles,name'],
            'setor'    => ['nullable', Rule::in(array_keys(User::LOTACOES))],
            'orgao_id' => ['nullable', 'exists:orgaos,id'],
            'status'   => ['boolean'],
        ];
    }

    /**
     * Trava dos perfis "exclusivos": só podem ser atribuídos a quem é lotado no setor.
     */
    public function after(): array
    {
        return [
            function ($validator) {
                $setor = $this->input('setor');
                foreach ((array) $this->input('roles', []) as $role) {
                    $exigido = User::PERFIS_EXCLUSIVOS[$role] ?? null;
                    if ($exigido && $setor !== $exigido) {
                        $label = User::$roleLabels[$role] ?? $role;
                        $lot   = User::LOTACOES[$exigido] ?? $exigido;
                        $validator->errors()->add('roles',
                            "O perfil \"{$label}\" é exclusivo do setor \"{$lot}\" — selecione esse setor para atribuí-lo.");
                    }
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'name'     => 'nome',
            'email'    => 'e-mail',
            'cpf'      => 'CPF',
            'matricula' => 'matrícula',
            'phone'    => 'telefone',
            'password' => 'senha',
            'roles'    => 'perfis',
            'setor'    => 'setor',
            'status'   => 'status',
        ];
    }
}
