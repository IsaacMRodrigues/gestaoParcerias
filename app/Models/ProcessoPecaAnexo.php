<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessoPecaAnexo extends Model
{
    protected $table = 'processo_peca_anexos';

    protected $fillable = [
        'processo_peca_id', 'arquivo_path', 'arquivo_nome', 'tamanho', 'mime_type', 'enviado_por',
    ];

    public function peca(): BelongsTo
    {
        return $this->belongsTo(ProcessoPeca::class, 'processo_peca_id');
    }

    public function remetente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enviado_por');
    }

    /** Tamanho legível (ex.: "1,2 MB"). */
    public function tamanhoLegivel(): string
    {
        $bytes = (int) $this->tamanho;
        if ($bytes <= 0) return '—';

        foreach (['B', 'KB', 'MB', 'GB'] as $unidade) {
            if ($bytes < 1024) {
                return number_format($bytes, $unidade === 'B' ? 0 : 1, ',', '.') . ' ' . $unidade;
            }
            $bytes /= 1024;
        }

        return number_format($bytes, 1, ',', '.') . ' TB';
    }
}
