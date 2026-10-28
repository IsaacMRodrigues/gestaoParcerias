<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Um evento da trilha de auditoria das assinaturas. Só se acrescenta: cada evento leva o hash do anterior
 * (hash_anterior) e o seu (hash), e mudar ou apagar um deles quebra a cadeia — ver TrilhaDeAssinatura::verificar().
 */
class AssinaturaEvento extends Model
{
    protected $table = 'assinatura_eventos';

    public $timestamps = false;

    protected $guarded = ['id'];

    /** O hash_anterior do primeiro evento. */
    public const GENESE = '0000000000000000000000000000000000000000000000000000000000000000';

    public const EVENTOS = [
        'assinatura'        => 'Assinatura',
        'anulacao'          => 'Assinatura anulada (documento devolvido para correção)',
        'registro_anterior' => 'Assinatura anterior à trilha, registrada na implantação',
    ];

    /** Nome estável de cada tipo de documento na trilha: a trilha não depende do nome da classe. */
    public const DOCUMENTOS = [
        'peca'            => Peca::class,
        'processo_peca'   => ProcessoPeca::class,
        'ordem_pagamento' => OrdemPagamento::class,
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('A trilha de auditoria das assinaturas não se altera.'));
        static::deleting(fn () => throw new LogicException('A trilha de auditoria das assinaturas não se apaga.'));
    }

    public static function tipoDe(Model $documento): string
    {
        return array_search($documento::class, self::DOCUMENTOS, true)
            ?: throw new LogicException('Documento sem assinatura: ' . $documento::class);
    }

    public function documento(): ?Model
    {
        $classe = self::DOCUMENTOS[$this->documento_type] ?? null;

        return $classe ? $classe::find($this->documento_id) : null;
    }

    public function instante(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y-m-d H:i:s.u', $this->carimbo(), 'UTC');
    }

    public function dadosDeIdentidade(): array
    {
        return json_decode((string) $this->identidade, true) ?: [];
    }

    public function dadosDaRequisicao(): array
    {
        return json_decode((string) $this->metadados, true) ?: [];
    }

    public function dadosDoEvento(): array
    {
        return json_decode((string) $this->detalhes, true) ?: [];
    }

    /**
     * A fonte oficial da hora da assinatura: só a da assinatura feita com a trilha. No registro de uma anterior, o NTP
     * carimbou o registro, não a assinatura, que teve a hora do relógio do servidor.
     */
    public function fonteDaHoraDaAssinatura(): ?string
    {
        return $this->evento === 'assinatura' ? $this->fonte_tempo : null;
    }

    public function rotuloDoEvento(): string
    {
        return self::EVENTOS[$this->evento] ?? $this->evento;
    }

    /** Em que qualidade assinou, quando o documento tem mais de uma assinatura. */
    public function rotuloDoPapel(): ?string
    {
        if ($this->papel === null) {
            return null;
        }
        if ($this->papel === 'contra') {
            return 'contra-assinatura da OSC';
        }

        foreach (Peca::ASSINATURAS_EM_SEQUENCIA as $documentos) {
            foreach ($documentos as $papeis) {
                if (isset($papeis[$this->papel])) {
                    return $papeis[$this->papel]['rotulo'];
                }
            }
        }

        return $this->papel;
    }

    /** CPF para a página pública: só os seis dígitos do meio, como nos validadores do governo. */
    public function cpfMascarado(): ?string
    {
        $digitos = preg_replace('/\D/', '', (string) ($this->dadosDeIdentidade()['cpf'] ?? ''));

        return strlen($digitos) === 11 ? '***.' . substr($digitos, 3, 3) . '.' . substr($digitos, 6, 3) . '-**' : null;
    }

    /** O hash deste evento, recalculado dos campos gravados. */
    public function calcularHash(): string
    {
        return hash('sha256', json_encode([
            $this->evento, $this->documento_type, (int) $this->documento_id, $this->documento_rotulo,
            $this->codigo_validacao, $this->papel, $this->user_id === null ? null : (int) $this->user_id,
            $this->identidade, $this->autenticacao, $this->ip, $this->metadados, $this->hash_documento,
            $this->carimbo(), $this->fonte_tempo, $this->desvio_ms === null ? null : (int) $this->desvio_ms,
            $this->detalhes, $this->hash_anterior,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE));
    }

    public function integro(): bool
    {
        return hash_equals((string) $this->hash, $this->calcularHash());
    }

    /** O carimbo como gravado, sempre com os seis dígitos da fração (o MySQL e o SQLite devolvem igual). */
    private function carimbo(): string
    {
        $valor = (string) $this->getAttributes()['carimbo_tempo'];

        return str_contains($valor, '.') ? str_pad($valor, 26, '0') : $valor . '.000000';
    }
}
