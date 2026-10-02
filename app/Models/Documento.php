<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Documento extends Model
{
    /** Tipos de anexo da organização, conforme o checklist de habilitação. */
    public const TIPOS = [
        'estatuto'             => 'Estatuto Social',
        'cnpj'                 => 'Cartão CNPJ',
        'certidao'             => 'Certidão de regularidade (INSS/FGTS/Débitos)',
        'plano_trabalho'       => 'Plano de Trabalho',
        'ata'                  => 'Ata de Eleição da Diretoria',
        'oficio_pedido'        => 'Memorando do pedido',
        'experiencia_previa'   => 'Comprovante de experiência prévia',
        'relacao_dirigentes'   => 'Relação nominal dos dirigentes',
        'docs_presidente'      => 'RG, CPF e comprovante de residência do presidente',
        'balanco'              => 'Balanço patrimonial',
        'planilha_pessoal'     => 'Planilha de despesas de pessoal',
        'relatorio_fotografico' => 'Relatório fotográfico',
        'planilha_orcamentaria' => 'Planilha orçamentária de custos',
        'extrato'              => 'Extrato bancário',
        'comprovante_conta'    => 'Comprovante de abertura de conta',
        'outro'                => 'Outro Documento',
    ];

    /** Tipos que ficam em "Arquivos da OSC": não se pedem mais na proposta (os já anexados seguem). */
    public const NA_AREA_DA_OSC = ['estatuto', 'ata', 'certidao'];

    /** Os tipos que ainda se anexam na proposta ou na manifestação. */
    public static function tiposParaAnexar(): array
    {
        return array_diff_key(self::TIPOS, array_flip(self::NA_AREA_DA_OSC));
    }

    /**
     * Situação da conferência pelo município. A OSC envia; o município decide.
     */
    public const ANALISE = [
        'pendente' => 'Aguardando análise',
        'aprovado' => 'Aprovado',
        'recusado' => 'Recusado',
    ];

    /** Ver Processo::STATUS_COLORS: laranja espera alguém, verde ok, vermelho não. */
    public const ANALISE_COLORS = [
        'pendente' => 'accent',
        'aprovado' => 'brand',
        'recusado' => 'red',
    ];

    protected $fillable = [
        // Como as metas: o documento pode chegar pela manifestação de interesse,
        // antes de existir proposta, e passa a ela quando a SCP defere.
        'proposta_id', 'manifestacao_id', 'uploaded_by', 'nome_original', 'path', 'tipo', 'tamanho', 'mime_type',
        'analise_status', 'analisado_por', 'analisado_em', 'analise_motivo',
    ];

    protected function casts(): array
    {
        return ['analisado_em' => 'datetime'];
    }

    public function manifestacao(): BelongsTo
    {
        return $this->belongsTo(ManifestacaoInteresse::class, 'manifestacao_id');
    }

    public function proposta(): BelongsTo
    {
        return $this->belongsTo(Proposta::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** Quem conferiu o documento (null enquanto ninguém analisou). */
    public function analista(): BelongsTo
    {
        return $this->belongsTo(User::class, 'analisado_por');
    }

    public function aprovado(): bool
    {
        return $this->analise_status === 'aprovado';
    }

    public function recusado(): bool
    {
        return $this->analise_status === 'recusado';
    }

    public function pendenteDeAnalise(): bool
    {
        return !$this->aprovado() && !$this->recusado();
    }

    /** A OSC pode retirar o documento? Enquanto ninguém decidiu, ou se foi recusado. */
    public function podeSerRemovido(): bool
    {
        return !$this->aprovado();
    }

    public function tamanhoFormatado(): string
    {
        $kb = $this->tamanho / 1024;
        return $kb > 1024
            ? number_format($kb / 1024, 1) . ' MB'
            : number_format($kb, 0) . ' KB';
    }
}
