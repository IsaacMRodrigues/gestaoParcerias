<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Movimentação da Seleção entre setores (histórico do Fluxo Seleção).
 */
class SelecaoTramitacao extends Model
{
    protected $table = 'selecao_tramitacoes';

    public const STATUS = [
        'enviado'   => 'Encaminhado',
        'devolvido' => 'Devolvido',
        'concluido' => 'Seleção encerrada',
    ];

    protected $fillable = [
        'chamamento_id', 'de_setor', 'para_setor',
        'enviado_por', 'enviado_em', 'parecer', 'status',
    ];

    protected function casts(): array
    {
        return ['enviado_em' => 'datetime'];
    }

    public function chamamento(): BelongsTo
    {
        return $this->belongsTo(Chamamento::class);
    }

    public function remetente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enviado_por');
    }
}
