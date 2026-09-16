<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Uma movimentação da prestação de contas entre setores. */
class PrestacaoTramitacao extends Model
{
    protected $table = 'prestacao_tramitacoes';

    protected $fillable = ['prestacao_id', 'de_setor', 'para_setor', 'enviado_por', 'enviado_em', 'parecer', 'status'];

    protected function casts(): array
    {
        return ['enviado_em' => 'datetime'];
    }

    public function prestacao(): BelongsTo
    {
        return $this->belongsTo(PrestacaoContas::class, 'prestacao_id');
    }

    public function remetente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enviado_por');
    }
}
