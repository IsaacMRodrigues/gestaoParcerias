<?php

namespace App\Http\Requests;

use App\Models\Parecer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ParecerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo'                 => ['required', Rule::in(array_keys(Parecer::TIPOS))],
            'resultado'            => ['required', Rule::in(array_keys(Parecer::RESULTADOS))],
            'texto'                => ['required', 'string'],
            'responsavel'          => ['nullable', 'string', 'max:255'],
            'data_parecer'         => ['required', 'date'],
            'observacoes'          => ['nullable', 'string'],
            'diligencia_descricao' => ['required_if:resultado,diligencia', 'nullable', 'string'],
            'diligencia_prazo'     => ['required_if:resultado,diligencia', 'nullable', 'date', 'after:today'],
        ];
    }

    public function attributes(): array
    {
        return [
            'tipo'                 => 'tipo de parecer',
            'resultado'            => 'resultado',
            'texto'                => 'texto do parecer',
            'data_parecer'         => 'data do parecer',
            'diligencia_descricao' => 'descrição da diligência',
            'diligencia_prazo'     => 'prazo da diligência',
        ];
    }
}
