<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Parcela do cronograma de desembolso (item 11): por meta e parcela. Ano e mês só nas antigas. */
class PlanoDesembolso extends Model
{
    protected $table = 'plano_desembolsos';

    protected $fillable = ['proposta_id', 'manifestacao_id', 'meta_id', 'parcela', 'valor', 'ano', 'mes'];

    protected function casts(): array
    {
        return ['valor' => 'decimal:2', 'parcela' => 'integer'];
    }

    public function proposta(): BelongsTo
    {
        return $this->belongsTo(Proposta::class);
    }

    public function manifestacao(): BelongsTo
    {
        return $this->belongsTo(ManifestacaoInteresse::class, 'manifestacao_id');
    }

    public function meta(): BelongsTo
    {
        return $this->belongsTo(Meta::class);
    }

    /** "1ª par", como no cabeçalho do modelo. */
    public static function rotuloParcela(int $n): string
    {
        return $n . 'ª par';
    }
}
