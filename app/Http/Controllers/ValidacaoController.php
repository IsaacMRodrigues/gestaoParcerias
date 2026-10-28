<?php

namespace App\Http\Controllers;

use App\Models\AssinaturaEvento;
use App\Models\DocumentoEmitido;
use App\Models\OrdemPagamento;
use App\Models\Peca;
use App\Models\PecaAssinatura;
use App\Models\ProcessoPeca;
use App\Support\TrilhaDeAssinatura;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Validação pública, sem login, de documentos assinados: autoria, data e hora, integridade do texto (SHA-256) e a
 * trilha de auditoria; e a conferência de um arquivo PDF emitido pelo sistema.
 */
class ValidacaoController extends Controller
{
    public function index(): View
    {
        return view('validacao.index');
    }

    public function verificar(Request $request)
    {
        $request->validate(['codigo' => ['required', 'string']]);

        return redirect()->route('validacao.mostrar', ['codigo' => trim($request->codigo)]);
    }

    public function mostrar(string $codigo): View
    {
        $codigo = strtoupper(trim($codigo));
        [$doc, $documento] = $this->assinaturaEmVigor($codigo);

        if ($doc) {
            $evento = TrilhaDeAssinatura::eventoDaAssinatura($codigo);
            $doc += [
                'integro'       => TrilhaDeAssinatura::integro($documento, $codigo),
                'hash_assinado' => $evento?->hash_documento,
                'hash_atual'    => TrilhaDeAssinatura::hashDoConteudo($documento),
                'evento'        => $evento,
            ];
            $trilha = $this->trilhaDo($documento);

            return view('validacao.mostrar', compact('doc', 'codigo', 'trilha'));
        }

        // Fora dos documentos, o código pode estar na trilha: assinatura anulada por devolução.
        $eventos = AssinaturaEvento::where('codigo_validacao', $codigo)->orderBy('id')->get();
        $anulacao = $eventos->firstWhere('evento', 'anulacao');
        $documento = $eventos->first()?->documento();
        $trilha = $documento ? $this->trilhaDo($documento) : $this->comElos($eventos);
        $vigentes = $documento ? array_column(TrilhaDeAssinatura::assinaturasDe($documento), 'codigo') : [];

        return view('validacao.mostrar', ['doc' => null] + compact('codigo', 'eventos', 'anulacao', 'trilha', 'vigentes'));
    }

    /** Confere um arquivo pelo hash: só o PDF exatamente como o sistema o gerou é reconhecido. */
    public function arquivo(Request $request): View
    {
        $request->validate(['arquivo' => ['required', 'file', 'max:30720']], [
            'arquivo.required' => 'Escolha o arquivo PDF a conferir.',
            'arquivo.max'      => 'O arquivo não pode passar de 30 MB.',
        ]);

        $hash = hash_file('sha256', $request->file('arquivo')->getRealPath());
        $emitido = DocumentoEmitido::where('hash_pdf', $hash)->first();

        $codigos = collect($emitido?->codigos ?? [])->map(fn (string $c) => [
            'codigo'   => $c,
            'anulada'  => AssinaturaEvento::where('codigo_validacao', $c)->where('evento', 'anulacao')->first(),
        ]);

        return view('validacao.arquivo', [
            'emitido' => $emitido,
            'hash'    => $hash,
            'nome'    => $request->file('arquivo')->getClientOriginalName(),
            'codigos' => $codigos,
        ]);
    }

    /** @return array{0: ?array, 1: ?Model} os dados da assinatura em vigor com este código e o documento */
    private function assinaturaEmVigor(string $codigo): array
    {
        $peca = ProcessoPeca::with(['assinante', 'processo.orgao'])
            ->whereNotNull('assinado_em')
            ->where('codigo_validacao', $codigo)
            ->first();

        if ($peca) {
            return [[
                'tipo'        => ProcessoPeca::TIPOS[$peca->tipo] ?? $peca->tipo,
                'ref_label'   => 'Processo nº',
                'ref'         => $peca->processo->numero,
                'extra_label' => 'Unidade Gestora',
                'extra'       => $peca->processo->orgao->name ?? '—',
                'assinante'   => $peca->assinante_nome ?: $peca->assinante?->name,
                'assinado_em' => $peca->assinado_em,
                'codigo'      => $peca->codigo_validacao,
                'conteudo'    => $peca->conteudo,
            ], $peca];
        }

        $op = OrdemPagamento::with(['assinante', 'instrumento.proposta.osc'])
            ->whereNotNull('assinado_em')
            ->where('codigo_validacao', $codigo)
            ->first();

        if ($op) {
            return [[
                'tipo'        => 'Ordem de Pagamento nº ' . $op->numero,
                'ref_label'   => 'Instrumento nº',
                'ref'         => $op->instrumento->numero,
                'extra_label' => 'Favorecido',
                'extra'       => $op->favorecido ?: ($op->instrumento->proposta->osc->name ?? '—'),
                'assinante'   => $op->assinante_nome ?: $op->assinante?->name,
                'assinado_em' => $op->assinado_em,
                'codigo'      => $op->codigo_validacao,
                'conteudo'    => $op->conteudo,
            ], $op];
        }

        // Termos antigos têm também o código da contra-assinatura da OSC, que o carimbo imprime.
        $selecao = Peca::with(['assinante', 'contraAssinante', 'pecaable'])
            ->where('tipo', 'modelo')
            ->whereNotNull('assinado_em')
            ->where(fn ($q) => $q->where('codigo_validacao', $codigo)
                ->orWhere('codigo_validacao_contra', $codigo))
            ->first();

        if ($selecao) {
            $alvo = $selecao->pecaable;
            $ref = match (true) {
                $alvo instanceof \App\Models\Processo   => $alvo->numero,
                $alvo instanceof \App\Models\Chamamento => $alvo->numero ?: $alvo->titulo,
                $alvo instanceof \App\Models\Proposta   => $alvo->titulo,
                $alvo instanceof \App\Models\Aditivo    => 'Aditivo #' . $alvo->id,
                default                                 => '—',
            };

            $doc = [
                'tipo'        => $selecao->rotulo,
                'ref_label'   => 'Referência',
                'ref'         => $ref,
                'extra_label' => 'Categoria',
                'extra'       => Peca::CATEGORIA_LABELS[$selecao->categoria] ?? $selecao->categoria,
                'assinante'   => $selecao->assinante_nome ?: $selecao->assinante?->name,
                'assinado_em' => $selecao->assinado_em,
                'codigo'      => $selecao->codigo_validacao,
                'conteudo'    => $selecao->conteudo,
            ];

            if ($selecao->contraAssinado()) {
                $doc['contra_assinante']   = $selecao->contra_assinante_nome
                    ?: $selecao->contraAssinante?->name;
                $doc['contra_osc']         = $selecao->contraAssinante?->osc?->name;
                $doc['contra_assinado_em'] = $selecao->contra_assinado_em;
                $doc['contra_codigo']      = $selecao->codigo_validacao_contra;
            }

            return [$doc, $selecao];
        }

        // Assinatura de uma das partes de documento assinado em sequência: cada parte tem o seu código.
        $parte = PecaAssinatura::with('peca.pecaable', 'peca.assinaturasPartes')->where('codigo_validacao', $codigo)->first();

        if ($parte) {
            $selecao = $parte->peca;
            $rotulos = $selecao->sequenciaDeAssinaturas();

            return [[
                'tipo'        => $selecao->rotulo,
                'ref_label'   => 'Referência',
                'ref'         => $selecao->pecaable?->titulo ?? '—',
                'extra_label' => 'Categoria',
                'extra'       => Peca::CATEGORIA_LABELS[$selecao->categoria] ?? $selecao->categoria,
                'assinante'   => $parte->assinante_nome,
                'assinado_em' => $parte->assinado_em,
                'codigo'      => $parte->codigo_validacao,
                'conteudo'    => $selecao->conteudo,
                'partes'      => $selecao->assinaturasPartes->map(fn ($a) => [
                    'rotulo' => $rotulos[$a->papel]['rotulo'] ?? $a->papel,
                    'nome'   => $a->assinante_nome,
                    'em'     => $a->assinado_em,
                    'codigo' => $a->codigo_validacao,
                ])->all(),
                'completo'    => $selecao->sequenciaCompleta(),
            ], $selecao];
        }

        return [null, null];
    }

    /** Os eventos do documento, do mais antigo ao mais novo, cada um com o seu elo conferido. */
    private function trilhaDo(Model $documento): Collection
    {
        return $this->comElos(AssinaturaEvento::where('documento_type', AssinaturaEvento::tipoDe($documento))
            ->where('documento_id', $documento->getKey())->orderBy('id')->get());
    }

    private function comElos(Collection $eventos): Collection
    {
        return $eventos->map(function (AssinaturaEvento $e) {
            $anterior = AssinaturaEvento::where('id', '<', $e->id)->orderByDesc('id')->value('hash') ?? AssinaturaEvento::GENESE;
            $e->setAttribute('elo_integro', $e->integro() && hash_equals($anterior, (string) $e->hash_anterior));

            return $e;
        });
    }
}
