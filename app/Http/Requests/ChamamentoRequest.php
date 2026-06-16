<?php

namespace App\Http\Requests;

use App\Models\Chamamento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChamamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'numero'                => ['nullable', 'string', 'max:50'],
            'titulo'                => ['required', 'string', 'max:255'],
            'objeto'                => ['required', 'string'],
            'tipo'                  => ['required', Rule::in(array_keys(Chamamento::TIPOS))],
            'valor_disponivel'      => ['nullable', 'numeric', 'min:0'],
            'data_publicacao'       => ['nullable', 'date'],
            'data_inicio_inscricao' => ['nullable', 'date'],
            'data_fim_inscricao'    => ['nullable', 'date', 'after_or_equal:data_inicio_inscricao'],
            'data_resultado'        => ['nullable', 'date'],
            'requisitos'            => ['nullable', 'string'],
            'status'                => ['required', Rule::in(array_keys(Chamamento::STATUS))],
        ];
    }

    public function attributes(): array
    {
        return [
            'titulo'                => 'título',
            'objeto'                => 'objeto',
            'tipo'                  => 'tipo',
            'valor_disponivel'      => 'valor disponível',
            'data_publicacao'       => 'data de publicação',
            'data_inicio_inscricao' => 'data de início das inscrições',
            'data_fim_inscricao'    => 'data de fim das inscrições',
            'data_resultado'        => 'data do resultado',
        ];
    }
}
