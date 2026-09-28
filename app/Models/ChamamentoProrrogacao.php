<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Uma prorrogação do prazo de inscrições, com os dois anexos que a fundamentam. */
class ChamamentoProrrogacao extends Model
{
    protected $table = 'chamamento_prorrogacoes';

    protected $fillable = [
        'chamamento_id', 'fim_anterior', 'fim_novo',
        'aviso_path', 'aviso_nome', 'publicacao_path', 'publicacao_nome',
        'user_id', 'autor_nome',
    ];

    protected function casts(): array
    {
        return ['fim_anterior' => 'date', 'fim_novo' => 'date'];
    }

    /** Os dois documentos, pela chave usada na rota de download. */
    public const DOCUMENTOS = [
        'aviso'      => 'Aviso de prorrogação',
        'publicacao' => 'Comprovante de publicação da prorrogação',
    ];

    public function chamamento(): BelongsTo
    {
        return $this->belongsTo(Chamamento::class);
    }
}
