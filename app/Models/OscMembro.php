<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OscMembro extends Model
{
    protected $table = 'osc_membros';

    protected $fillable = [
        'osc_id', 'nome', 'cpf', 'phone', 'email', 'cargo',
    ];

    public function osc(): BelongsTo
    {
        return $this->belongsTo(Osc::class);
    }
}
