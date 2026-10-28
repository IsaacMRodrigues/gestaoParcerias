<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Um PDF de documento assinado que o sistema gerou, pelo hash do arquivo: quem recebe o PDF confere em /validar. */
class DocumentoEmitido extends Model
{
    protected $table = 'documentos_emitidos';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['emitido_em' => 'datetime', 'codigos' => 'array'];
    }

    public function documento(): ?Model
    {
        $classe = AssinaturaEvento::DOCUMENTOS[$this->documento_type] ?? null;

        return $classe ? $classe::find($this->documento_id) : null;
    }
}
