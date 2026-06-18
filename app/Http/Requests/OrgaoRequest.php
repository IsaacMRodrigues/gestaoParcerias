<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrgaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $orgaoId = $this->route('orgao')?->id;

        return [
            'codigo'      => ['nullable', 'string', 'size:4', Rule::unique('orgaos', 'codigo')->ignore($orgaoId)],
            'name'        => ['required', 'string', 'max:255'],
            'sigla'       => ['nullable', 'string', 'max:20'],
            'cnpj'        => ['nullable', 'string', 'max:18', Rule::unique('orgaos', 'cnpj')->ignore($orgaoId)],
            'email'       => ['nullable', 'email', 'max:255'],
            'phone'       => ['nullable', 'string', 'max:20'],
            'cep'         => ['nullable', 'string', 'max:9'],
            'logradouro'  => ['nullable', 'string', 'max:255'],
            'numero'      => ['nullable', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:100'],
            'bairro'      => ['nullable', 'string', 'max:100'],
            'cidade'      => ['nullable', 'string', 'max:100'],
            'estado'      => ['nullable', 'string', 'size:2'],
            'status'      => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'codigo' => 'código',
            'name'   => 'nome',
            'sigla'  => 'sigla',
            'cnpj'   => 'CNPJ',
            'email'  => 'e-mail',
            'phone'  => 'telefone',
            'estado' => 'estado (UF)',
        ];
    }
}
