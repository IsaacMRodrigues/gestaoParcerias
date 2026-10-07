<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Recurso da OSC contra o resultado provisório do Chamamento Público (art. 27 da Lei 13.019/2014). */
class Recurso extends Model
{
    protected $table = 'recursos';

    protected $fillable = [
        'chamamento_id', 'osc_id', 'proposta_id',
        'fundamentacao', 'arquivo_path', 'arquivo_nome', 'tamanho', 'mime_type',
        'protocolado_por', 'protocolado_em',
    ];

    protected function casts(): array
    {
        return [
            'protocolado_em' => 'datetime',
        ];
    }

    public function chamamento(): BelongsTo
    {
        return $this->belongsTo(Chamamento::class);
    }

    public function osc(): BelongsTo
    {
        return $this->belongsTo(Osc::class);
    }

    public function proposta(): BelongsTo
    {
        return $this->belongsTo(Proposta::class);
    }

    /** Membro da Comissão de Seleção da Secretaria do chamamento, que lê o recurso. */
    public function comissaoPodeVer(?User $user): bool
    {
        return $user !== null
            && $user->hasRole('comissao_selecao')
            && (bool) $this->proposta?->visivelPara($user);
    }

    public function temArquivo(): bool
    {
        return !is_null($this->arquivo_path);
    }

    public function tamanhoFormatado(): string
    {
        if (!$this->tamanho) {
            return '—';
        }
        $kb = $this->tamanho / 1024;

        return $kb > 1024 ? number_format($kb / 1024, 1) . ' MB' : number_format($kb, 0) . ' KB';
    }
}
