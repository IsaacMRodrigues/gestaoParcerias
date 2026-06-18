<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Orgao extends Model
{
    protected $table = 'orgaos';

    protected $fillable = [
        'codigo', 'name', 'sigla', 'cnpj', 'email', 'phone',
        'cep', 'logradouro', 'numero', 'complemento', 'bairro', 'cidade', 'estado',
        'status',
    ];

    protected function casts(): array
    {
        return ['status' => 'boolean'];
    }

    public function processos(): HasMany
    {
        return $this->hasMany(Processo::class);
    }
}
