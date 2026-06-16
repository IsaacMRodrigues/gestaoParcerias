<?php

namespace App\Http\Requests;

use App\Models\Osc;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OscRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $oscId = $this->route('osc')?->id;

        return [
            'name'        => ['required', 'string', 'max:255'],
            'tipo'        => ['nullable', Rule::in(array_keys(Osc::TIPOS))],
            'cnpj'        => ['required', 'string', 'max:18', Rule::unique('oscs', 'cnpj')->ignore($oscId)],
            'email'       => ['nullable', 'email', 'max:255'],
            'phone'       => ['nullable', 'string', 'max:20'],
            'cep'         => ['nullable', 'string', 'max:9'],
            'logradouro'  => ['nullable', 'string', 'max:255'],
            'numero'      => ['nullable', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:100'],
            'bairro'      => ['nullable', 'string', 'max:100'],
            'cidade'      => ['nullable', 'string', 'max:100'],
            'estado'      => ['nullable', 'string', 'size:2'],
            'resp_nome'   => ['nullable', 'string', 'max:255'],
            'resp_cpf'    => ['nullable', 'string', 'max:14'],
            'resp_email'  => ['nullable', 'email', 'max:255'],
            'resp_phone'  => ['nullable', 'string', 'max:20'],
            'status'      => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'       => 'nome',
            'tipo'       => 'tipo',
            'cnpj'       => 'CNPJ',
            'email'      => 'e-mail',
            'phone'      => 'telefone',
            'estado'     => 'estado (UF)',
            'resp_nome'  => 'nome do responsável',
            'resp_cpf'   => 'CPF do responsável',
            'resp_email' => 'e-mail do responsável',
            'resp_phone' => 'telefone do responsável',
        ];
    }
}
