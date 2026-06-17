<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Processo extends Model
{
    public const STATUS = [
        'em_planejamento' => 'Em Planejamento',
        'em_tramite'      => 'Em Trâmite',
        'apto'            => 'Apto para Abertura',
        'arquivado'       => 'Arquivado',
    ];

    public const STATUS_COLORS = [
        'em_planejamento' => 'gray',
        'em_tramite'      => 'yellow',
        'apto'            => 'green',
        'arquivado'       => 'red',
    ];

    public const SETORES = [
        'ug'     => 'Unidade Gestora',
        'scp'    => 'SCP',
        'seplan' => 'SEPLAN',
        'spc'    => 'SPC',
    ];

    // Sequência sugerida do trâmite (linha 4 do termo de referência do módulo)
    public const FLUXO = ['ug', 'scp', 'seplan', 'ug', 'spc'];

    public const AREAS_TEMATICAS = [
        'saude'                  => 'Saúde',
        'educacao'               => 'Educação',
        'assistencia_social'     => 'Assistência Social',
        'cultura'                => 'Cultura',
        'esporte'                => 'Esporte',
        'meio_ambiente'          => 'Meio Ambiente',
        'desenvolvimento_economico' => 'Desenvolvimento Econômico',
        'outra'                  => 'Outra',
    ];

    protected $fillable = [
        'numero', 'orgao_id', 'created_by', 'status', 'setor_atual',
    ];

    public function orgao(): BelongsTo
    {
        return $this->belongsTo(Orgao::class);
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function termoReferencia(): HasOne
    {
        return $this->hasOne(TermoReferencia::class);
    }

    public function pecas(): HasMany
    {
        return $this->hasMany(ProcessoPeca::class);
    }

    public function peca(string $tipo): ?ProcessoPeca
    {
        return $this->pecas->firstWhere('tipo', $tipo);
    }

    public function tramitacoes(): HasMany
    {
        return $this->hasMany(Tramitacao::class)->orderBy('enviado_em');
    }

    public function tramitacaoAtual(): ?Tramitacao
    {
        return $this->tramitacoes()->whereNull('recebido_em')->latest('enviado_em')->first();
    }

    /**
     * Gera o próximo número no formato NNNN/AAAA.
     */
    public static function proximoNumero(): string
    {
        $ano = now()->year;
        $ultimo = static::whereYear('created_at', $ano)->count() + 1;

        return sprintf('%04d/%d', $ultimo, $ano);
    }

    /**
     * Alertas automáticos de conformidade (🔴 / 🟢).
     */
    public function alertas(): array
    {
        $alertas = [];
        $tr = $this->termoReferencia;

        if (!$tr || !$tr->dotacao_orcamentaria) {
            $alertas[] = ['nivel' => 'erro', 'texto' => 'Não existe dotação orçamentária informada.'];
        }
        if ($tr && $tr->objeto_resumido && str_word_count($tr->objeto_resumido) < 5) {
            $alertas[] = ['nivel' => 'erro', 'texto' => 'Objeto genérico — descreva de forma específica e mensurável.'];
        }
        if (!$tr || !$tr->indicadores) {
            $alertas[] = ['nivel' => 'erro', 'texto' => 'Meta sem indicador definido.'];
        }
        if (!$tr || !$tr->justificativa) {
            $alertas[] = ['nivel' => 'erro', 'texto' => 'Ausência de justificativa.'];
        }
        if (!$tr || !$tr->valor_total) {
            $alertas[] = ['nivel' => 'erro', 'texto' => 'Valor total não informado.'];
        }

        if (empty($alertas)) {
            $alertas[] = ['nivel' => 'ok', 'texto' => 'Planejamento apto para abertura de processo.'];
        }

        return $alertas;
    }

    public function estaApto(): bool
    {
        foreach ($this->alertas() as $a) {
            if ($a['nivel'] === 'erro') {
                return false;
            }
        }
        return true;
    }
}
