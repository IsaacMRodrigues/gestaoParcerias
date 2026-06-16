<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MetaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'numero'            => ['required', 'integer', 'min:1'],
            'descricao'         => ['required', 'string', 'max:255'],
            'indicador'         => ['nullable', 'string', 'max:255'],
            'meta_quantitativa' => ['nullable', 'string', 'max:255'],
            'data_inicio'       => ['nullable', 'date'],
            'data_fim'          => ['nullable', 'date', 'after_or_equal:data_inicio'],
        ];
    }

    public function attributes(): array
    {
        return [
            'numero'            => 'número',
            'descricao'         => 'descrição',
            'meta_quantitativa' => 'meta quantitativa',
            'data_inicio'       => 'data de início',
            'data_fim'          => 'data de fim',
        ];
    }
}
