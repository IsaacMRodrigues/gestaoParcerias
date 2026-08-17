<?php

namespace App\Models;

use App\Models\Concerns\ImpedeExclusaoComVinculos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Osc extends Model
{
    use ImpedeExclusaoComVinculos;

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
        'name', 'tipo', 'cnpj', 'data_abertura', 'cnae_primario', 'cnae_secundario', 'email', 'phone',
        'cep', 'logradouro', 'numero', 'complemento', 'bairro', 'cidade', 'estado',
        'resp_nome', 'resp_cpf', 'resp_email', 'resp_phone',
        'resp_cep', 'resp_logradouro', 'resp_numero', 'resp_complemento', 'resp_bairro', 'resp_cidade', 'resp_estado',
        'anexo_cartao_cnpj', 'resp_anexo_cpf', 'resp_anexo_comprovante', 'resp_anexo_ata',
        'status',
    ];

    /** Anexos do cadastro: campo => rótulo. */
    public const ANEXOS = [
        'anexo_cartao_cnpj'      => 'Cartão CNPJ',
        'resp_anexo_cpf'         => 'CPF do representante',
        'resp_anexo_comprovante' => 'Comprovante de endereço do representante',
        'resp_anexo_ata'         => 'Ata da atual diretoria',
    ];

    protected function casts(): array
    {
        return [
            'status'        => 'boolean',
            'data_abertura' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function propostas(): HasMany
    {
        return $this->hasMany(Proposta::class);
    }

    public function membros(): HasMany
    {
        return $this->hasMany(OscMembro::class);
    }

    protected function vinculosBloqueantes(): array
    {
        return [
            'propostas' => ['proposta', 'propostas'],
        ];
    }

    protected function fraseDeBloqueio(): string
    {
        return 'Esta OSC não pode ser excluída';
    }
}
