<?php

namespace App\Models;

use App\Models\Concerns\ImpedeExclusaoComVinculos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Orgao extends Model
{
    use ImpedeExclusaoComVinculos;

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

    /** É esta relação que o banco protege com RESTRICT (programas.orgao_id). */
    public function programas(): HasMany
    {
        return $this->hasMany(Programa::class);
    }

    protected function vinculosBloqueantes(): array
    {
        return [
            'programas' => ['programa', 'programas'],
            'processos' => ['processo', 'processos'],
        ];
    }

    protected function fraseDeBloqueio(): string
    {
        return 'Este órgão não pode ser excluído';
    }
}
