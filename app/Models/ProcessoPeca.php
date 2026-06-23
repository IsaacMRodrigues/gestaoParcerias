<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessoPeca extends Model
{
    public const TIPOS = [
        'oficio'             => 'Ofício',
        'pedido_parecer'     => 'Pedido de Parecer Financeiro',
        'parecer_financeiro' => 'Parecer Financeiro',
        'abertura'           => 'Termo de Abertura de Processo',
        'edital'             => 'Edital',
    ];

    /**
     * Setor que PREENCHE cada peça e em qual etapa do fluxo.
     */
    public const SETOR_RESPONSAVEL = [
        'oficio'             => 'ug',
        'pedido_parecer'     => 'scp',
        'parecer_financeiro' => 'seplan',
        'abertura'           => 'ug',
        'edital'             => 'scp',
    ];

    public const ETAPA = [
        'oficio'             => 0,
        'pedido_parecer'     => 1,
        'parecer_financeiro' => 2,
        'abertura'           => 3,
        'edital'             => 4,
    ];

    /**
     * Quem ASSINA (quando difere de quem preenche). Ex.: o Edital é elaborado
     * pela SCP (etapa 4) mas assinado pela UG (etapa 5).
     */
    public const ASSINATURA = [
        'edital' => ['setor' => 'ug', 'etapa' => 5],
    ];

    /**
     * Texto-modelo pré-preenchido (modelo padrão do cliente — substituir os "xxxxx").
     */
    public const MODELO = [
        'oficio' =>
            "(Papel timbrado)\n\nOfício nº xxx/xxxx                                              [Cidade], xx/xx/xxxx\n\n".
            "A(o) Sr.(a) [responsável], Secretaria de Planejamento.\n\n".
            "Solicitamos a celebração de parceria, nos termos da Lei Federal nº 13.019/2014.\n".
            "A parceria proposta será executada com recursos oriundos de xxxxx e tem por finalidade xxxxx.\n\n".
            "Diante do exposto, requer-se a análise e celebração da parceria.\n\n".
            "Atenciosamente,\n\n_____________________________\nSecretaria Municipal de xxxxx",
        'pedido_parecer' =>
            "Solicito parecer financeiro do seguinte processo:\n\n".
            "Dotação: xxxxx\nFicha: xxxxx\nFonte: xxxxx\n".
            "Objeto do instrumento: xxxxx\nInstrumento: xxxxx\nParceiro: xxxxx\n".
            "Valor total: R$ xxxxx (xxxxx)\nPrazo: xx meses",
        'parecer_financeiro' =>
            "PARECER FINANCEIRO\n\nOfício nº xxx/xxxx                                              Data: xx/xx/xxxx\n\n".
            "A Secretaria Municipal de Planejamento, após análise, informa:\n\n".
            "Valor solicitado: R$ xxxxx\nValor da Receita (exercício): R$ xxxxx\n".
            "Percentual em relação às receitas orçamentárias: xx%\n\n".
            "Parecer: xxxxx\n\n_____________________________\nSecretaria Municipal de Planejamento",
        'abertura' =>
            "TERMO DE ABERTURA DE PROCESSO\n\nProcesso nº: xxxxx\nData de abertura: xx/xx/xxxx\nObjeto: xxxxx\n\n".
            "Aos xx de xxxxx de 20xx, eu, [nome], secretário(a) da unidade gestora xxxxx, ABRI o processo ".
            "referente a xxxxx, atendendo o disposto na Lei nº 13.019/2014, art. 23.\n\n".
            "_____________________________\nSecretário(a) — Unidade Gestora",
        'edital' =>
            "EDITAL DE CHAMAMENTO PÚBLICO Nº xxx/xxxx\n\n(Cole/edite aqui o conteúdo do edital.)",
    ];

    protected $fillable = [
        'processo_id', 'tipo', 'conteudo', 'assinado_por', 'assinado_em',
    ];

    protected function casts(): array
    {
        return ['assinado_em' => 'datetime'];
    }

    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class);
    }

    public function assinante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assinado_por');
    }

    public function assinado(): bool
    {
        return !is_null($this->assinado_em);
    }

    public function setorResponsavel(): string
    {
        return self::SETOR_RESPONSAVEL[$this->tipo] ?? 'ug';
    }

    public function etapaDesignada(): int
    {
        return self::ETAPA[$this->tipo] ?? 0;
    }

    public function setorAssinatura(): string
    {
        return self::ASSINATURA[$this->tipo]['setor'] ?? $this->setorResponsavel();
    }

    public function etapaAssinatura(): int
    {
        return self::ASSINATURA[$this->tipo]['etapa'] ?? $this->etapaDesignada();
    }

    private function emAndamento(Processo $processo): bool
    {
        return in_array($processo->status, ['em_planejamento', 'em_tramite']);
    }

    /**
     * Pode editar o CONTEÚDO (setor responsável, na etapa de edição, peça não assinada).
     */
    public function podeEditarConteudo(Processo $processo, ?User $user): bool
    {
        return $user
            && $this->emAndamento($processo)
            && !$this->assinado()
            && $user->setor === $this->setorResponsavel()
            && $processo->setor_atual === $this->setorResponsavel()
            && $processo->etapa === $this->etapaDesignada();
    }

    /**
     * Pode ASSINAR (setor de assinatura, na etapa de assinatura, com conteúdo preenchido).
     */
    public function podeAssinar(Processo $processo, ?User $user): bool
    {
        return $user
            && $this->emAndamento($processo)
            && !$this->assinado()
            && !empty($this->conteudo)
            && $user->setor === $this->setorAssinatura()
            && $processo->setor_atual === $this->setorAssinatura()
            && $processo->etapa === $this->etapaAssinatura();
    }
}
