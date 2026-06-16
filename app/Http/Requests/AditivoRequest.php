<?php

namespace App\Http\Requests;

use App\Models\Aditivo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AditivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'numero'           => ['required', 'integer', 'min:1'],
            'tipo'             => ['required', Rule::in(array_keys(Aditivo::TIPOS))],
            'descricao'        => ['required', 'string'],
            'data_assinatura'  => ['nullable', 'date'],
            'nova_data_fim'    => ['nullable', 'date'],
            'valor_adicional'  => ['nullable', 'numeric', 'min:0'],
            'status'           => ['required', Rule::in(array_keys(Aditivo::STATUS))],
        ];
    }

    public function attributes(): array
    {
        return [
            'numero'          => 'número',
            'tipo'            => 'tipo',
            'descricao'       => 'descrição',
            'data_assinatura' => 'data de assinatura',
            'nova_data_fim'   => 'nova data de fim',
            'valor_adicional' => 'valor adicional',
        ];
    }
}
