<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Movimentação da Celebração entre setores (histórico do Fluxo Celebração).
 */
class CelebracaoTramitacao extends Model
{
    protected $table = 'celebracao_tramitacoes';

    public const STATUS = [
        'enviado'   => 'Encaminhado',
        'devolvido' => 'Devolvido',
        'concluido' => 'Celebração concluída',
    ];

    protected $fillable = [
        'proposta_id', 'de_setor', 'para_setor',
        'enviado_por', 'enviado_em', 'parecer', 'status',
    ];

    protected function casts(): array
    {
        return ['enviado_em' => 'datetime'];
    }

    public function proposta(): BelongsTo
    {
        return $this->belongsTo(Proposta::class);
    }

    public function remetente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enviado_por');
    }
}
