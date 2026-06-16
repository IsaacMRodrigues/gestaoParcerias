<?php

namespace App\Http\Requests;

use App\Models\Instrumento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InstrumentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'numero'              => ['required', 'string', 'max:50'],
            'tipo'                => ['required', Rule::in(array_keys(Instrumento::TIPOS))],
            'objeto'              => ['required', 'string'],
            'valor_repasse'       => ['required', 'numeric', 'min:0'],
            'valor_proprio'       => ['nullable', 'numeric', 'min:0'],
            'data_assinatura'     => ['nullable', 'date'],
            'data_inicio'         => ['required', 'date'],
            'data_fim'            => ['required', 'date', 'after:data_inicio'],
            'publicado_doe'       => ['boolean'],
            'data_publicacao_doe' => ['nullable', 'date'],
            'numero_doe'          => ['nullable', 'string', 'max:100'],
            'status'              => ['required', Rule::in(array_keys(Instrumento::STATUS))],
        ];
    }

    public function attributes(): array
    {
        return [
            'numero'              => 'número',
            'tipo'                => 'tipo',
            'objeto'              => 'objeto',
            'valor_repasse'       => 'valor de repasse',
            'valor_proprio'       => 'contrapartida',
            'data_assinatura'     => 'data de assinatura',
            'data_inicio'         => 'data de início',
            'data_fim'            => 'data de fim',
            'data_publicacao_doe' => 'data de publicação no DOE',
            'numero_doe'          => 'número do DOE',
        ];
    }
}
