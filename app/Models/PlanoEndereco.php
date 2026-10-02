<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Endereço de execução, do esquema anterior do plano; as linhas antigas seguem com o plano. */
class PlanoEndereco extends Model
{
    protected $table = 'plano_enderecos';

    protected $fillable = ['proposta_id', 'manifestacao_id', 'descricao', 'endereco'];

    public function proposta(): BelongsTo
    {
        return $this->belongsTo(Proposta::class);
    }

    public function manifestacao(): BelongsTo
    {
        return $this->belongsTo(ManifestacaoInteresse::class, 'manifestacao_id');
    }
}
