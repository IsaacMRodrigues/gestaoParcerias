<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Uma movimentação do pedido de alteração entre setores. */
class AlteracaoTramitacao extends Model
{
    protected $table = 'alteracao_tramitacoes';

    protected $fillable = ['alteracao_id', 'de_setor', 'para_setor', 'enviado_por', 'enviado_em', 'parecer', 'status'];

    protected function casts(): array
    {
        return ['enviado_em' => 'datetime'];
    }

    public function alteracao(): BelongsTo
    {
        return $this->belongsTo(Alteracao::class);
    }

    public function remetente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enviado_por');
    }
}
