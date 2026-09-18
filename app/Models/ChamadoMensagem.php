<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Uma fala dentro do chamado — a abertura ou uma resposta.
 *
 * A nota interna existe para a equipe combinar entre si sem precisar de outro
 * canal; quem abriu o chamado não a vê em lugar nenhum.
 */
class ChamadoMensagem extends Model
{
    protected $table = 'chamado_mensagens';

    protected $fillable = [
        'chamado_id', 'user_id', 'autor_nome', 'autor_vinculo', 'mensagem', 'interna',
        'arquivo_path', 'arquivo_nome', 'arquivo_mime', 'arquivo_tamanho',
    ];

    protected function casts(): array
    {
        return ['interna' => 'boolean'];
    }

    public function chamado(): BelongsTo
    {
        return $this->belongsTo(Chamado::class);
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function temArquivo(): bool
    {
        return !is_null($this->arquivo_path);
    }

    public function tamanhoLegivel(): string
    {
        $bytes = (int) $this->arquivo_tamanho;

        return $bytes >= 1048576
            ? number_format($bytes / 1048576, 1, ',', '.') . ' MB'
            : number_format(max($bytes, 1) / 1024, 0, ',', '.') . ' KB';
    }
}
