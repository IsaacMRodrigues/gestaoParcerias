<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Item 8 do modelo de Plano de Trabalho: contrapartida não financeira, quando houver. */
class PlanoContrapartida extends Model
{
    protected $table = 'plano_contrapartidas';

    protected $fillable = ['proposta_id', 'manifestacao_id', 'numero', 'descricao', 'quantidade'];
}
