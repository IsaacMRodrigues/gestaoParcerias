<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Endereço de execução da atividade, obra, evento, serviço ou entrega do bem.
 *
 * É uma lista, e não um campo, porque o modelo 3.1 anota em letras garrafais
 * que pode haver mais de um — uma oficina na sede e a obra no bairro, por
 * exemplo. O que interessa ao fiscal é saber onde ir.
 */
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
