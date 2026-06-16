<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Documento extends Model
{
    public const TIPOS = [
        'estatuto'       => 'Estatuto Social',
        'cnpj'           => 'Cartão CNPJ',
        'certidao'       => 'Certidão (INSS/FGTS/Débitos)',
        'plano_trabalho' => 'Plano de Trabalho',
        'ata'            => 'Ata de Eleição da Diretoria',
        'outro'          => 'Outro Documento',
    ];

    protected $fillable = [
        'proposta_id', 'uploaded_by', 'nome_original', 'path', 'tipo', 'tamanho', 'mime_type',
    ];

    public function proposta(): BelongsTo
    {
        return $this->belongsTo(Proposta::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function tamanhoFormatado(): string
    {
        $kb = $this->tamanho / 1024;
        return $kb > 1024
            ? number_format($kb / 1024, 1) . ' MB'
            : number_format($kb, 0) . ' KB';
    }
}
