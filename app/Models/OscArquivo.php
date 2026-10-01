<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Uma versão de um documento da área "Arquivos da OSC" (pedido da gestão,
 * 30/09/2026): anexado uma vez, vale para todas as parcerias da organização.
 * A versão atual de cada tipo é a de número maior; as anteriores são o
 * histórico.
 */
class OscArquivo extends Model
{
    protected $table = 'osc_arquivos';

    /** Avisa a OSC por e-mail esta quantidade de dias antes de a certidão vencer. */
    public const DIAS_AVISO_VENCIMENTO = 7;

    /**
     * Os grupos e os documentos, como no modelo do DF. As certidões têm
     * validade; as declarações têm texto-modelo, preenchido com o cadastro da
     * OSC, para imprimir, assinar e anexar.
     */
    public const GRUPOS = [
        'certidoes' => [
            'rotulo' => 'CND — Certidões negativas',
            'itens'  => [
                'cnd_federal'  => 'Certidão de débitos relativos a créditos tributários federais e à dívida ativa da União',
                'cnd_estadual' => 'Certidão negativa de débitos estaduais',
                'cndt'         => 'Certidão negativa de débitos trabalhistas — CNDT',
                'crf_fgts'     => 'Certificado de regularidade do Fundo de Garantia do Tempo de Serviço — CRF/FGTS',
            ],
        ],
        'institucionais' => [
            'rotulo' => 'Arquivos da OSC',
            'itens'  => [
                'ata_eleicao' => 'Cópia da ata de eleição do quadro dirigente atual ou documento equivalente',
                'estatuto'    => 'Cópia do estatuto registrado e suas eventuais alterações',
            ],
        ],
        'declaracoes' => [
            'rotulo' => 'Declarações',
            'itens'  => [
                'decl_art7'          => 'Declaração — art. 7º, XXXIII, CF/88 (não emprega menor)',
                'decl_art33'         => 'Declaração — art. 33, V, "c", Lei 13.019/2014 (condições materiais)',
                'decl_art34'         => 'Declaração — art. 34, VII, Lei 13.019/2014 (sede e tempo de existência)',
                'decl_art39'         => 'Declaração do representante legal de que a organização e seus dirigentes não incorrem em vedações (art. 39, Lei 13.019/2014)',
                'decl_art45'         => 'Declaração — art. 45, Lei 13.019/2014 (vedações de remuneração)',
                'decl_autenticidade' => 'Declaração de autenticidade dos documentos',
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

    public static function exigeValidade(string $tipo): bool
    {
        return array_key_exists($tipo, self::GRUPOS['certidoes']['itens']);
    }

    public static function ehDeclaracao(string $tipo): bool
    {
        return array_key_exists($tipo, self::GRUPOS['declaracoes']['itens']);
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

    public function tamanhoFormatado(): string
    {
        $kb = (int) $this->tamanho / 1024;

        return $kb >= 1024 ? number_format($kb / 1024, 1, ',', '.') . ' MB' : number_format($kb, 0, ',', '.') . ' KB';
    }
}
