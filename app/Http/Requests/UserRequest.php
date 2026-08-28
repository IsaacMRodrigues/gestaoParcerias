<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('usuario')?->id;

        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            // Nome de usuário: alternativa ao e-mail na tela de entrada. Sem
            // espaço nem acento, para não virar duas grafias do mesmo acesso.
            'login'    => ['nullable', 'string', 'max:50', 'regex:/^[a-z0-9._-]+$/', Rule::unique('users', 'login')->ignore($userId)],
            'cpf'      => ['nullable', 'string', 'max:14', Rule::unique('users', 'cpf')->ignore($userId)],
            'matricula' => ['nullable', 'string', 'max:50', Rule::unique('users', 'matricula')->ignore($userId)],
            'phone'    => ['nullable', 'string', 'max:20'],
            'password' => [$this->isMethod('POST') ? 'required' : 'nullable', 'string', 'min:6', 'confirmed'],
            'roles'    => ['required', 'array', 'min:1'],
            'roles.*'  => ['string', 'exists:roles,name'],
            'setor'    => ['nullable', Rule::in(array_keys(User::LOTACOES))],
            'orgao_id' => ['nullable', 'exists:orgaos,id'],
            'status'   => ['boolean'],
        ];
    }

    /**
     * Trava dos perfis "exclusivos": só podem ser atribuídos a quem é lotado no setor.
     */
    public function after(): array
    {
        return [
            function ($validator) {
                $setor = $this->input('setor');
                foreach ((array) $this->input('roles', []) as $role) {
                    $exigido = User::PERFIS_EXCLUSIVOS[$role] ?? null;
                    if ($exigido && $setor !== $exigido) {
                        $label = User::$roleLabels[$role] ?? $role;
                        $lot   = User::LOTACOES[$exigido] ?? $exigido;
                        $validator->errors()->add('roles',
                            "O perfil \"{$label}\" é exclusivo do setor \"{$lot}\" — selecione esse setor para atribuí-lo.");
                    }
                }

                // Encargo designado por portaria (gestor, comissões): quem o
                // recebe é servidor do setor que publica o ato, e continua
                // lotado nele — ver User::PERFIS_DE_DESIGNACAO.
                foreach ((array) $this->input('roles', []) as $role) {
                    $designa = User::setorQueDesigna($role);
                    if ($designa && $setor !== $designa) {
                        $label = User::$roleLabels[$role] ?? $role;
                        $lot   = User::LOTACOES[$designa] ?? $designa;
                        $validator->errors()->add('roles',
                            "O perfil \"{$label}\" é designado por portaria d{$this->artigo($lot)} \"{$lot}\" "
                            .'e acompanha a lotação dela — selecione esse setor para atribuí-lo.');
                    }
                }

                // Chefia serve para cadastrar a equipe do setor, e o usuário
                // criado herda o setor de quem cadastra: sem lotação, o perfil
                // seria concedido para uma tela que não abre.
                if (in_array('chefe_setor', (array) $this->input('roles', []), true) && !$setor) {
                    $validator->errors()->add('roles',
                        'O perfil "Chefe de Setor" exige lotação: informe o setor que essa pessoa chefia.');
                }
            },
        ];
    }

    /** "da Unidade Gestora" x "do Gabinete": só para a frase não sair torta. */
    private function artigo(string $lotacao): string
    {
        return str_starts_with($lotacao, 'Secretaria') || str_starts_with($lotacao, 'Unidade')
            || str_starts_with($lotacao, 'Comissão') || str_starts_with($lotacao, 'Procuradoria')
            ? 'a' : 'o';
    }

    public function messages(): array
    {
        return [
            'login.regex' => 'O nome de usuário aceita apenas letras minúsculas, números, ponto, hífen e sublinhado.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name'     => 'nome',
            'email'    => 'e-mail',
            'login'    => 'nome de usuário',
            'cpf'      => 'CPF',
            'matricula' => 'matrícula',
            'phone'    => 'telefone',
            'password' => 'senha',
            'roles'    => 'perfis',
            'setor'    => 'setor',
            'status'   => 'status',
        ];
    }
}
