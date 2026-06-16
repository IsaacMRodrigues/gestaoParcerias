<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'user_id',
        'name', 'tipo', 'cnpj', 'email', 'phone',
        'cep', 'logradouro', 'numero', 'complemento', 'bairro', 'cidade', 'estado',
        'resp_nome', 'resp_cpf', 'resp_email', 'resp_phone',
        'status',
    ];

    protected function casts(): array
    {
        return ['status' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function propostas(): HasMany
    {
        return $this->hasMany(Proposta::class);
    }
}
