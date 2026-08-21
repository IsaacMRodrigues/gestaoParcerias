<?php

namespace App\Http\Controllers;

use App\Models\OrdemPagamento;
use App\Models\Peca;
use App\Models\ProcessoPeca;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Validação pública da autenticidade de documentos assinados.
 * Acessível sem login (igual aos portais de validação de documentos).
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
        $doc = null;

        $peca = ProcessoPeca::with(['assinante', 'processo.orgao'])
            ->whereNotNull('assinado_em')
            ->where('codigo_validacao', $codigo)
            ->first();

        if ($peca) {
            $doc = [
                'tipo'        => ProcessoPeca::TIPOS[$peca->tipo] ?? $peca->tipo,
                'ref_label'   => 'Processo nº',
                'ref'         => $peca->processo->numero,
                'extra_label' => 'Unidade Gestora',
                'extra'       => $peca->processo->orgao->name ?? '—',
                'assinante'   => $peca->assinante?->name,
                'assinado_em' => $peca->assinado_em,
                'codigo'      => $peca->codigo_validacao,
                'conteudo'    => $peca->conteudo,
            ];
        } else {
            $op = OrdemPagamento::with(['assinante', 'instrumento.proposta.osc'])
                ->whereNotNull('assinado_em')
                ->where('codigo_validacao', $codigo)
                ->first();

            if ($op) {
                $doc = [
                    'tipo'        => 'Ordem de Pagamento nº ' . $op->numero,
                    'ref_label'   => 'Instrumento nº',
                    'ref'         => $op->instrumento->numero,
                    'extra_label' => 'Favorecido',
                    'extra'       => $op->favorecido ?: ($op->instrumento->proposta->osc->name ?? '—'),
                    'assinante'   => $op->assinante?->name,
                    'assinado_em' => $op->assinado_em,
                    'codigo'      => $op->codigo_validacao,
                    'conteudo'    => $op->conteudo,
                ];
            } else {
                // Documento com assinatura das partes (o Termo de Parceria) tem
                // dois códigos: o do Município e o da contra-assinatura da OSC.
                // O carimbo imprime ambos, então ambos precisam validar — pelo
                // código da OSC a busca não encontrava nada e o portal dizia
                // "documento não encontrado" para um documento autêntico.
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
                        'assinante'   => $selecao->assinante?->name,
                        'assinado_em' => $selecao->assinado_em,
                        'codigo'      => $selecao->codigo_validacao,
                        'conteudo'    => $selecao->conteudo,
                    ];

                    if ($selecao->contraAssinado()) {
                        $doc['contra_assinante']   = $selecao->contraAssinante?->name;
                        $doc['contra_osc']         = $selecao->contraAssinante?->osc?->name;
                        $doc['contra_assinado_em'] = $selecao->contra_assinado_em;
                        $doc['contra_codigo']      = $selecao->codigo_validacao_contra;
                    }
                }
            }
        }

        return view('validacao.mostrar', compact('doc', 'codigo'));
    }
}
