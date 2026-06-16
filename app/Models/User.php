<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'cpf', 'phone', 'status', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    public static array $roleLabels = [
        'representante_legal'              => 'Representante Legal',
        'secretario_unidade_gestora'       => 'Secretário da Unidade Gestora',
        'gestor_parceria'                  => 'Gestor da Parceria',
        'comissao_avaliacao_monitoramento' => 'Comissão de Avaliação e Monitoramento',
        'comissao_selecao'                 => 'Comissão de Seleção',
        'procuradoria_juridica'            => 'Procuradoria Jurídica',
        'controle_interno'                 => 'Controle Interno',
        'cadastrador_proposta'             => 'Cadastrador de Proposta',
        'cadastrador_prestacao_contas'     => 'Cadastrador de Prestação de Contas',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'status'            => 'boolean',
        ];
    }
}
