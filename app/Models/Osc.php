<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Osc extends Model
{
    public const TIPOS = [
        'associacao'  => 'Associação',
        'fundacao'    => 'Fundação',
        'cooperativa' => 'Cooperativa',
        'oscip'       => 'OSCIP',
        'os'          => 'Organização Social (OS)',
        'outro'       => 'Outro',
    ];

    protected $fillable = [
        'name', 'tipo', 'cnpj', 'email', 'phone',
        'cep', 'logradouro', 'numero', 'complemento', 'bairro', 'cidade', 'estado',
        'resp_nome', 'resp_cpf', 'resp_email', 'resp_phone',
        'status',
    ];

    protected function casts(): array
    {
        return ['status' => 'boolean'];
    }
}
