<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Um bem móvel adquirido com recurso da parceria (Anexo VII). */
class PrestacaoBem extends Model
{
    protected $table = 'prestacao_bens';

    protected $fillable = ['prestacao_id', 'especificacao', 'quantidade', 'valor_unitario', 'documento'];

    public function prestacao(): BelongsTo
    {
        return $this->belongsTo(PrestacaoContas::class, 'prestacao_id');
    }

    public function total(): float
    {
        return (float) $this->quantidade * (float) $this->valor_unitario;
    }
}
