<?php

namespace App\Http\Requests;

use App\Models\Osc;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class OscRegistroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Dados do representante (vira o usuário)
            'resp_nome'  => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255', 'unique:users,email'],
            'cpf'        => ['required', 'string', 'max:14', 'unique:users,cpf'],
            'resp_phone' => ['nullable', 'string', 'max:20'],
            'password'   => ['required', 'confirmed', Password::min(8)],

            // Dados da OSC
            'osc_name'   => ['required', 'string', 'max:255'],
            'cnpj'       => ['required', 'string', 'max:18', 'unique:oscs,cnpj'],
            'tipo'       => ['required', \Illuminate\Validation\Rule::in(array_keys(Osc::TIPOS))],
            'osc_email'  => ['nullable', 'email', 'max:255'],
            'osc_phone'  => ['nullable', 'string', 'max:20'],

            // Endereço
            'cep'        => ['nullable', 'string', 'max:9'],
            'logradouro' => ['nullable', 'string', 'max:255'],
            'numero'     => ['nullable', 'string', 'max:20'],
            'complemento'=> ['nullable', 'string', 'max:100'],
            'bairro'     => ['nullable', 'string', 'max:100'],
            'cidade'     => ['required', 'string', 'max:100'],
            'estado'     => ['required', 'string', 'size:2'],
        ];
    }

    public function attributes(): array
    {
        return [
            'resp_nome'  => 'nome do responsável',
            'email'      => 'e-mail',
            'cpf'        => 'CPF',
            'osc_name'   => 'nome da OSC',
            'cnpj'       => 'CNPJ',
            'tipo'       => 'tipo de organização',
            'cidade'     => 'cidade',
            'estado'     => 'estado',
        ];
    }
}
