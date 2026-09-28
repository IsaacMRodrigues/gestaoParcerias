<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Um cancelamento ou uma reabertura de chamamento, com o motivo e quem fez. */
class ChamamentoCancelamento extends Model
{
    protected $table = 'chamamento_cancelamentos';

    protected $fillable = ['chamamento_id', 'acao', 'motivo', 'user_id', 'autor_nome'];

    public function chamamento(): BelongsTo
    {
        return $this->belongsTo(Chamamento::class);
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
