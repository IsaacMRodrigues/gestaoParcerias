<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Uma versão de um documento de "Arquivos da OSC": vale para todas as parcerias. A atual é a
 * de maior número; as anteriores são o histórico.
 */
class OscArquivo extends Model
{
    protected $table = 'osc_arquivos';

    /** Avisa a OSC por e-mail esta quantidade de dias antes de a certidão vencer. */
    public const DIAS_AVISO_VENCIMENTO = 7;

    /**
     * Grupos e documentos: certidões (com validade) e os institucionais. As declarações e os documentos
     * complementares (balanço, experiência prévia) são da Celebração, não se exigem antes dela.
     */
    public const GRUPOS = [
        'certidoes' => [
            'rotulo' => 'CND — Certidões negativas',
            'itens'  => [
                'cnd_federal'  => 'Certidão de débitos relativos a créditos tributários federais e à dívida ativa da União',
                'cnd_estadual' => 'Certidão negativa de débitos estaduais',
                'cnd_municipal' => 'Certidão negativa de débitos municipais',
                'cndt'         => 'Certidão negativa de débitos trabalhistas — CNDT',
                'crf_fgts'     => 'Certificado de regularidade do Fundo de Garantia do Tempo de Serviço — CRF/FGTS',
            ],
        ],
        'institucionais' => [
            'rotulo' => 'Arquivos da OSC',
            'itens'  => [
                'ata_eleicao'        => 'Cópia da ata de eleição do quadro dirigente atual ou documento equivalente',
                'estatuto'           => 'Cópia do estatuto registrado e suas eventuais alterações',
                'docs_presidente'    => 'Cópia simples do RG, CPF e comprovante de endereço do representante legal',
                'cartao_cnpj'        => 'Comprovante de inscrição no CNPJ, emitido no sítio eletrônico oficial da Receita Federal do Brasil (cadastro ativo há, no mínimo, três anos)',
                'relacao_dirigentes' => 'Relação nominal atualizada dos dirigentes, conforme o estatuto, com endereço, telefone, e-mail, número e órgão expedidor da carteira de identidade e número de registro',
            ],
        ],
    ];

    protected $fillable = [
        'osc_id', 'tipo', 'versao', 'arquivo_path', 'arquivo_nome', 'mime_type', 'tamanho',
        'validade', 'aviso_vencimento_em', 'enviado_por',
    ];

    protected function casts(): array
    {
        return ['validade' => 'date', 'aviso_vencimento_em' => 'datetime'];
    }

    /** ['tipo' => 'rótulo'] de todos os grupos. */
    public static function tipos(): array
    {
        return array_merge(...array_map(fn ($g) => $g['itens'], array_values(self::GRUPOS)));
    }

    public static function rotulo(string $tipo): string
    {
        return self::tipos()[$tipo] ?? $tipo;
    }

    /** Certidões: valem até uma data. */
    public static function exigeValidade(string $tipo): bool
    {
        return array_key_exists($tipo, self::GRUPOS['certidoes']['itens']);
    }

    public function osc(): BelongsTo
    {
        return $this->belongsTo(Osc::class);
    }

    public function remetente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enviado_por');
    }

    public function analises(): HasMany
    {
        return $this->hasMany(OscArquivoAnalise::class);
    }

    public function vencida(): bool
    {
        return $this->validade !== null && $this->validade->endOfDay()->isPast();
    }

    public function venceEmBreve(): bool
    {
        return $this->validade !== null && !$this->vencida()
            && $this->validade->lte(now()->addDays(self::DIAS_AVISO_VENCIMENTO)->endOfDay());
    }

    /** Baixa: a própria OSC, a SCP, e o servidor da Secretaria em que a OSC tem parceria. */
    public function podeBaixar(?User $user): bool
    {
        if ($user === null) {
            return false;
        }
        if ($user->oscVinculada()?->id === $this->osc_id) {
            return true;
        }

        return $user->temAcessoInterno() && ($user->baixaTodosOsDocumentos()
            || Proposta::visiveisPara($user)->where('osc_id', $this->osc_id)->exists());
    }

    public function tamanhoFormatado(): string
    {
        $kb = (int) $this->tamanho / 1024;

        return $kb >= 1024 ? number_format($kb / 1024, 1, ',', '.') . ' MB' : number_format($kb, 0, ',', '.') . ' KB';
    }
}
