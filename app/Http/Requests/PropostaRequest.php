<?php

namespace App\Http\Requests;

use App\Models\Proposta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PropostaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'chamamento_id'       => ['required', 'exists:chamamentos,id'],
            'osc_id'              => ['required', 'exists:oscs,id'],
            'titulo'              => ['required', 'string', 'max:255'],
            'objeto'              => ['required', 'string'],
            'justificativa'       => ['nullable', 'string'],
            'valor_solicitado'    => ['required', 'numeric', 'min:0'],
            'valor_proprio'       => ['nullable', 'numeric', 'min:0'],
            'data_inicio_prevista'=> ['nullable', 'date'],
            'data_fim_prevista'   => ['nullable', 'date', 'after_or_equal:data_inicio_prevista'],
            'status'              => ['required', Rule::in(array_keys(Proposta::STATUS))],
        ];
    }

    public function attributes(): array
    {
        return [
            'chamamento_id'        => 'chamamento',
            'osc_id'               => 'OSC',
            'titulo'               => 'título',
            'objeto'               => 'objeto',
            'valor_solicitado'     => 'valor solicitado',
            'valor_proprio'        => 'contrapartida',
            'data_inicio_prevista' => 'data de início prevista',
            'data_fim_prevista'    => 'data de fim prevista',
        ];
    }
}
