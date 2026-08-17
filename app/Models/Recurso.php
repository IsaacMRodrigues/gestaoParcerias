<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Recurso administrativo da OSC contra o resultado provisório do Chamamento
 * Público (art. 27 e ss. da Lei 13.019/2014 e prazo do edital).
 */
class Recurso extends Model
{
    protected $table = 'recursos';

    public const RESULTADOS = [
        'provido'   => 'Provido',
        'parcial'   => 'Parcialmente provido',
        'improvido' => 'Improvido',
    ];

    public const RESULTADO_COLORS = [
        'provido'   => 'brand',
        'parcial'   => 'accent',
        'improvido' => 'red',
    ];

    protected $fillable = [
        'chamamento_id', 'osc_id', 'proposta_id',
        'fundamentacao', 'arquivo_path', 'arquivo_nome', 'tamanho', 'mime_type',
        'protocolado_por', 'protocolado_em',
        'resultado', 'resposta', 'respondido_por', 'respondido_em', 'codigo_validacao',
    ];

    protected function casts(): array
    {
        return [
            'protocolado_em' => 'datetime',
            'respondido_em'  => 'datetime',
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

    public function respondente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'respondido_por');
    }

    public function respondido(): bool
    {
        return !is_null($this->respondido_em);
    }

    public function temArquivo(): bool
    {
        return !is_null($this->arquivo_path);
    }

    public function resultadoLabel(): string
    {
        return self::RESULTADOS[$this->resultado] ?? '—';
    }

    public function tamanhoFormatado(): string
    {
        if (!$this->tamanho) {
            return '—';
        }
        $kb = $this->tamanho / 1024;

        return $kb > 1024 ? number_format($kb / 1024, 1) . ' MB' : number_format($kb, 0) . ' KB';
    }

    /** Código de validação da resposta assinada. */
    public static function gerarCodigoValidacao(): string
    {
        do {
            $codigo = strtoupper(Str::random(4) . '-' . Str::random(4) . '-' . Str::random(2));
        } while (static::where('codigo_validacao', $codigo)->exists());

        return $codigo;
    }
}
