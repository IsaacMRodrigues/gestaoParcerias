<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EtapaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'numero'      => ['required', 'integer', 'min:1'],
            'descricao'   => ['required', 'string', 'max:255'],
            'responsavel' => ['nullable', 'string', 'max:255'],
            'data_inicio' => ['nullable', 'date'],
            'data_fim'    => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'recursos'    => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'numero'    => 'número',
            'descricao' => 'descrição',
        ];
    }
}
