<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Documento extends Model
{
    /**
     * Tipos de anexo da organização.
     *
     * Acompanham o checklist de habilitação do módulo 3.2: com seis opções, a
     * OSC classificava metade dos documentos como "Outro" e quem conferia
     * tinha de abrir arquivo por arquivo para saber o que era o quê.
     */
    public const TIPOS = [
        'estatuto'             => 'Estatuto Social',
        'cnpj'                 => 'Cartão CNPJ',
        'certidao'             => 'Certidão de regularidade (INSS/FGTS/Débitos)',
        'plano_trabalho'       => 'Plano de Trabalho',
        'ata'                  => 'Ata de Eleição da Diretoria',
        'oficio_pedido'        => 'Ofício do pedido',
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

    /**
     * A OSC pode retirar o documento?
     *
     * Só enquanto ninguém decidiu, ou quando foi recusado — aí retirar é parte
     * de corrigir. Documento aprovado virou peça da instrução do processo: sai
     * do alcance de quem o enviou.
     */
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
