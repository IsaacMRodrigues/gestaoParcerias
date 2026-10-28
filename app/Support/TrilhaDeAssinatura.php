<?php

namespace App\Support;

use App\Models\AssinaturaEvento;
use App\Models\DocumentoEmitido;
use App\Models\OrdemPagamento;
use App\Models\Peca;
use App\Models\PecaAssinatura;
use App\Models\ProcessoPeca;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Assinatura eletrônica avançada (Lei 14.063/2020, art. 4º, II): cada assinatura grava, junto e na mesma
 * transação, o código de integridade (SHA-256) do texto, o carimbo do tempo da Hora Legal e um evento na
 * trilha de auditoria encadeada (AssinaturaEvento), com quem assinou, como se autenticou, IP e navegador.
 * Serve a Peca, a ProcessoPeca e a OrdemPagamento.
 */
class TrilhaDeAssinatura
{
    public const AUTENTICACAO = 'Senha pessoal confirmada no ato da assinatura';

    private const CAMPOS = ['evento', 'documento_type', 'documento_id', 'documento_rotulo', 'codigo_validacao', 'papel',
        'user_id', 'identidade', 'autenticacao', 'ip', 'metadados', 'hash_documento', 'carimbo_tempo', 'fonte_tempo',
        'desvio_ms', 'detalhes'];

    public static function hashDoConteudo(Model $documento): string
    {
        return hash('sha256', (string) ($documento->getAttributes()['conteudo'] ?? ''));
    }

    /** Código de validação nunca usado, em nenhum documento nem na trilha (um código anulado não volta). */
    public static function novoCodigo(): string
    {
        do {
            $codigo = strtoupper(Str::random(4) . '-' . Str::random(4) . '-' . Str::random(2));
        } while (
            Peca::where('codigo_validacao', $codigo)->orWhere('codigo_validacao_contra', $codigo)->exists()
            || PecaAssinatura::where('codigo_validacao', $codigo)->exists()
            || ProcessoPeca::where('codigo_validacao', $codigo)->exists()
            || OrdemPagamento::where('codigo_validacao', $codigo)->exists()
            || AssinaturaEvento::where('codigo_validacao', $codigo)->exists()
        );

        return $codigo;
    }

    /** Assina o documento de uma assinatura só. A linha fica travada: dois cliques não assinam duas vezes. */
    public static function assinar(Model $documento, User $quem): void
    {
        $carimbo = app(HoraLegal::class)->agora();
        $identidade = $quem->identidadeParaAssinatura();

        DB::transaction(function () use ($documento, $quem, $carimbo, $identidade) {
            $atual = $documento->newQuery()->whereKey($documento->getKey())->lockForUpdate()->firstOrFail();
            abort_if($atual->assinado_em !== null, 409, 'Este documento já foi assinado.');

            $atual->update([
                'assinado_por'     => $quem->id,
                'assinado_em'      => $carimbo->paraGravar(),
                'assinante_nome'   => $identidade['nome'],
                'assinante_cargo'  => $identidade['cargo'],
                'codigo_validacao' => self::novoCodigo(),
            ]);

            self::registrarAssinatura($atual, $quem, $atual->codigo_validacao, $carimbo);
        });

        $documento->refresh();
    }

    /** O evento de uma assinatura feita agora (a do documento ou a de uma das partes). */
    public static function registrarAssinatura(Model $documento, User $quem, string $codigo, CarimboDoTempo $carimbo, ?string $papel = null): AssinaturaEvento
    {
        return self::registrar([
            'evento'           => 'assinatura',
            'codigo_validacao' => $codigo,
            'papel'            => $papel,
            'user_id'          => $quem->id,
            'identidade'       => self::json(self::identidade($quem)),
            'autenticacao'     => self::AUTENTICACAO,
        ] + self::doAto() + self::doDocumento($documento) + self::doCarimbo($carimbo));
    }

    /** Antes de a devolução apagar as assinaturas do documento: cada uma fica na trilha como anulada. */
    public static function anular(Model $documento, string $motivo): void
    {
        $assinaturas = self::assinaturasDe($documento);
        if ($assinaturas === []) {
            return;
        }

        $carimbo = app(HoraLegal::class)->agora();
        $por = auth()->user();

        foreach ($assinaturas as $assinatura) {
            self::registrar([
                'evento'           => 'anulacao',
                'codigo_validacao' => $assinatura['codigo'],
                'papel'            => $assinatura['papel'],
                'user_id'          => $por?->id,
                'identidade'       => $por ? self::json(self::identidade($por)) : null,
                'autenticacao'     => $por ? 'Sessão autenticada' : null,
                'detalhes'         => self::json(['motivo' => $motivo]),
            ] + ($por ? self::doAto() : []) + self::doDocumento($documento) + self::doCarimbo($carimbo));
        }
    }

    /**
     * As assinaturas feitas antes da trilha entram nela uma vez, com o hash do texto como está na implantação
     * (o texto assinado já não se editava). Devolve quantas foram registradas.
     */
    public static function registrarAnteriores(): int
    {
        $carimbo = app(HoraLegal::class)->agora();
        $registradas = 0;

        $consultas = [
            Peca::with('assinaturasPartes')->where(fn ($q) => $q->whereNotNull('assinado_em')
                ->orWhereNotNull('contra_assinado_em')->orWhereHas('assinaturasPartes')),
            ProcessoPeca::whereNotNull('assinado_em'),
            OrdemPagamento::whereNotNull('assinado_em'),
        ];

        foreach ($consultas as $consulta) {
            foreach ($consulta->lazyById(200) as $documento) {
                foreach (self::assinaturasDe($documento) as $assinatura) {
                    if (AssinaturaEvento::where('codigo_validacao', $assinatura['codigo'])->exists()) {
                        continue;
                    }

                    $usuario = $assinatura['user_id'] ? User::find($assinatura['user_id']) : null;

                    self::registrar([
                        'evento'           => 'registro_anterior',
                        'codigo_validacao' => $assinatura['codigo'],
                        'papel'            => $assinatura['papel'],
                        'user_id'          => $assinatura['user_id'],
                        'identidade'       => self::json(['nome' => $assinatura['nome'], 'cargo' => $assinatura['cargo']]
                            + ($usuario ? array_diff_key(self::identidade($usuario), ['nome' => 1, 'cargo' => 1]) : [])),
                        'detalhes'         => self::json([
                            'assinado_em' => $assinatura['em']?->copy()->utc()->format('Y-m-d H:i:s'),
                            'observacao'  => 'Assinatura feita antes da trilha de auditoria: o código de integridade foi '
                                . 'calculado na implantação, sobre o texto como estava, e o carimbo do tempo é o da implantação.',
                        ]),
                    ] + self::doDocumento($documento) + self::doCarimbo($carimbo));

                    $registradas++;
                }
            }
        }

        return $registradas;
    }

    /**
     * As assinaturas que o documento tem agora, cada uma com o seu código.
     *
     * @return list<array{codigo: string, papel: ?string, user_id: ?int, nome: ?string, cargo: ?string, em: ?CarbonInterface}>
     */
    public static function assinaturasDe(Model $documento): array
    {
        $lista = [];

        if ($documento->assinado_em !== null && filled($documento->codigo_validacao)) {
            $lista[] = ['codigo' => $documento->codigo_validacao, 'papel' => null, 'user_id' => $documento->assinado_por,
                'nome' => $documento->assinanteNome(), 'cargo' => $documento->assinanteCargo(), 'em' => $documento->assinado_em];
        }

        if ($documento instanceof Peca) {
            if ($documento->contraAssinado() && filled($documento->codigo_validacao_contra)) {
                $lista[] = ['codigo' => $documento->codigo_validacao_contra, 'papel' => 'contra', 'user_id' => $documento->contra_assinado_por,
                    'nome' => $documento->contraAssinanteNome(), 'cargo' => $documento->contraAssinanteCargo(), 'em' => $documento->contra_assinado_em];
            }

            foreach ($documento->assinaturasPartes as $parte) {
                $lista[] = ['codigo' => $parte->codigo_validacao, 'papel' => $parte->papel, 'user_id' => $parte->assinado_por,
                    'nome' => $parte->assinante_nome, 'cargo' => $parte->assinante_cargo, 'em' => $parte->assinado_em];
            }
        }

        return $lista;
    }

    /** O evento que registrou a assinatura deste código (a feita agora ou a anterior à trilha). */
    public static function eventoDaAssinatura(?string $codigo): ?AssinaturaEvento
    {
        return $codigo === null ? null : AssinaturaEvento::where('codigo_validacao', $codigo)
            ->whereIn('evento', ['assinatura', 'registro_anterior'])->orderByDesc('id')->first();
    }

    /**
     * Os hashes do texto que estas assinaturas firmaram, para o carimbo (normalmente um só).
     *
     * @return list<string>
     */
    public static function hashesAssinados(array $codigos): array
    {
        return AssinaturaEvento::whereIn('codigo_validacao', array_filter($codigos))
            ->whereIn('evento', ['assinatura', 'registro_anterior'])->whereNotNull('hash_documento')
            ->orderBy('id')->pluck('hash_documento')->unique()->values()->all();
    }

    /** O texto do documento é o mesmo do momento da assinatura? null quando a assinatura não está na trilha. */
    public static function integro(Model $documento, ?string $codigo): ?bool
    {
        $evento = self::eventoDaAssinatura($codigo);

        return $evento?->hash_documento === null ? null : hash_equals($evento->hash_documento, self::hashDoConteudo($documento));
    }

    /**
     * Confere a trilha inteira: cada elo da cadeia, cada evento e, para cada assinatura em vigor, se ela está na
     * trilha e se o texto ainda é o assinado.
     *
     * @return array{eventos: int, ultimo: string, problemas: list<string>}
     */
    public static function verificar(): array
    {
        $problemas = [];
        $anterior = AssinaturaEvento::GENESE;
        $eventos = 0;

        foreach (AssinaturaEvento::query()->lazyById(500) as $evento) {
            $eventos++;
            if ($evento->hash_anterior !== $anterior) {
                $problemas[] = "Evento nº {$evento->id}: o elo com o evento anterior está quebrado (evento apagado ou inserido fora da ordem).";
            }
            if (!$evento->integro()) {
                $problemas[] = "Evento nº {$evento->id}: os dados do evento foram alterados depois de gravados.";
            }
            $anterior = $evento->hash;
        }

        $consultas = [
            Peca::with('assinaturasPartes')->where(fn ($q) => $q->whereNotNull('assinado_em')
                ->orWhereNotNull('contra_assinado_em')->orWhereHas('assinaturasPartes')),
            ProcessoPeca::whereNotNull('assinado_em'),
            OrdemPagamento::whereNotNull('assinado_em'),
        ];

        foreach ($consultas as $consulta) {
            foreach ($consulta->lazyById(200) as $documento) {
                foreach (self::assinaturasDe($documento) as $assinatura) {
                    $integro = self::integro($documento, $assinatura['codigo']);
                    $qual = self::rotulo($documento) . " (código {$assinatura['codigo']})";

                    if ($integro === null) {
                        $problemas[] = "{$qual}: assinatura sem registro na trilha.";
                    } elseif (!$integro) {
                        $problemas[] = "{$qual}: o texto mudou depois da assinatura.";
                    }
                }
            }
        }

        return ['eventos' => $eventos, 'ultimo' => $anterior, 'problemas' => $problemas];
    }

    /** Cada PDF de documento assinado que o sistema gera fica registrado pelo hash, para conferência em /validar. */
    public static function registrarEmissao(Model $documento, string $pdf): void
    {
        $codigos = array_column(self::assinaturasDe($documento), 'codigo');
        if ($codigos === []) {
            return;
        }

        DocumentoEmitido::query()->insertOrIgnore([
            'hash_pdf'         => hash('sha256', $pdf),
            'documento_type'   => AssinaturaEvento::tipoDe($documento),
            'documento_id'     => $documento->getKey(),
            'documento_rotulo' => Str::limit(self::rotulo($documento), 250),
            'hash_documento'   => self::hashDoConteudo($documento),
            'codigos'          => self::json($codigos),
            'emitido_por'      => auth()->id(),
            'emitido_em'       => now(),
        ]);
    }

    public static function rotulo(Model $documento): string
    {
        return match (true) {
            $documento instanceof Peca           => (string) $documento->rotulo,
            $documento instanceof ProcessoPeca   => ProcessoPeca::TIPOS[$documento->tipo] ?? (string) $documento->tipo,
            $documento instanceof OrdemPagamento => 'Ordem de Pagamento nº ' . $documento->numero,
        };
    }

    /** Grava o evento no fim da cadeia, preso ao último: o último fica travado até o novo entrar. */
    private static function registrar(array $campos): AssinaturaEvento
    {
        for ($tentativa = 1; ; $tentativa++) {
            try {
                return DB::transaction(function () use ($campos) {
                    $ultimo = AssinaturaEvento::query()->orderByDesc('id')->lockForUpdate()->value('hash');

                    // Todas as colunas do hash presentes, mesmo vazias: o hash de agora é o mesmo de quando se reler.
                    $evento = new AssinaturaEvento($campos + array_fill_keys(self::CAMPOS, null)
                        + ['hash_anterior' => $ultimo ?? AssinaturaEvento::GENESE]);
                    $evento->hash = $evento->calcularHash();
                    $evento->save();

                    return $evento;
                });
            } catch (UniqueConstraintViolationException $e) {
                // Outro evento pegou o mesmo elo ao mesmo tempo (só com a trilha vazia): tenta no novo fim.
                if ($tentativa >= 3) {
                    throw $e;
                }
            }
        }
    }

    /** Os dados de identificação de quem age, como estão no ato (inciso I: cadastro validado pela Administração). */
    private static function identidade(User $quem): array
    {
        $assinatura = $quem->identidadeParaAssinatura();

        return array_filter([
            'nome'                 => $assinatura['nome'],
            'cargo'                => $assinatura['cargo'],
            'cpf'                  => $quem->cpf,
            'email'                => $quem->email,
            'login'                => $quem->login,
            'lotacao'              => $quem->osc_id ? 'OSC ' . $quem->osc?->name : ($quem->orgao?->name ?? $quem->setorLabel()),
            'conta_aprovada_em'    => $quem->approved_at?->copy()->utc()->format('Y-m-d H:i:s'),
            'conta_aprovada_por'   => $quem->approved_by ? User::find($quem->approved_by)?->name : null,
            'conta_cadastrada_por' => $quem->criadoPor?->name,
        ], fn ($v) => $v !== null && $v !== '');
    }

    /** IP e metadados da requisição: o IP de quem acessa e também o da conexão e o cabeçalho da CDN, crus. */
    private static function doAto(): array
    {
        $r = request();

        return [
            'ip'        => $r->ip(),
            'metadados' => self::json(array_filter([
                'ip_da_conexao'   => $r->server('REMOTE_ADDR'),
                'x_forwarded_for' => $r->header('X-Forwarded-For'),
                'navegador'       => mb_substr(mb_scrub((string) $r->userAgent()), 0, 500),
                'requisicao'      => $r->method() . ' /' . ltrim($r->path(), '/'),
                'sessao'          => $r->hasSession() ? hash('sha256', $r->session()->getId()) : null,
            ], fn ($v) => $v !== null && $v !== '')),
        ];
    }

    private static function doDocumento(Model $documento): array
    {
        return [
            'documento_type'   => AssinaturaEvento::tipoDe($documento),
            'documento_id'     => $documento->getKey(),
            'documento_rotulo' => Str::limit(self::rotulo($documento), 250),
            'hash_documento'   => self::hashDoConteudo($documento),
        ];
    }

    private static function doCarimbo(CarimboDoTempo $carimbo): array
    {
        return ['carimbo_tempo' => $carimbo->emUtc(), 'fonte_tempo' => $carimbo->fonte, 'desvio_ms' => $carimbo->desvioMs];
    }

    private static function json(array $dados): ?string
    {
        return $dados === [] ? null
            : json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
