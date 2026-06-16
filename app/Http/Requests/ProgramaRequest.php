<?php

namespace App\Http\Requests;

use App\Models\Programa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProgramaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'orgao_id'    => ['required', 'exists:orgaos,id'],
            'name'        => ['required', 'string', 'max:255'],
            'sigla'       => ['nullable', 'string', 'max:30'],
            'tipo'        => ['required', Rule::in(array_keys(Programa::TIPOS))],
            'objetivo'    => ['nullable', 'string'],
            'valor_total' => ['nullable', 'numeric', 'min:0'],
            'data_inicio' => ['nullable', 'date'],
            'data_fim'    => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'status'      => ['required', Rule::in(array_keys(Programa::STATUS))],
        ];
    }

    public function attributes(): array
    {
        return [
            'orgao_id'    => 'órgão',
            'name'        => 'nome',
            'tipo'        => 'tipo',
            'valor_total' => 'valor total',
            'data_inicio' => 'data de início',
            'data_fim'    => 'data de fim',
        ];
    }
}
