<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tramitacao extends Model
{
    protected $table = 'tramitacoes';

    public const STATUS = [
        'enviado'  => 'Enviado',
        'recebido' => 'Recebido',
        'devolvido'=> 'Devolvido',
    ];

    protected $fillable = [
        'processo_id', 'de_setor', 'para_setor',
        'enviado_por', 'enviado_em', 'recebido_por', 'recebido_em',
        'parecer', 'status',
    ];

    protected function casts(): array
    {
        return [
            'enviado_em'  => 'datetime',
            'recebido_em' => 'datetime',
        ];
    }

    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class);
    }

    public function remetente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enviado_por');
    }

    public function recebedor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recebido_por');
    }
}
