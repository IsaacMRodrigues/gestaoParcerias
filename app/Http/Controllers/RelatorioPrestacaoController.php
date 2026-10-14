<?php

namespace App\Http\Controllers;

use App\Models\Orgao;
use App\Models\Osc;
use App\Support\RelatorioPrestacao;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/**
 * Relatórios da prestação de contas — execução financeira e situação das prestações —, na tela, em
 * planilha ou em PDF. A Prefeitura vê o que é da sua Secretaria (ou tudo, quem atende o município);
 * a OSC, as próprias parcerias.
 */
class RelatorioPrestacaoController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = auth()->user();
        $ehOsc = $user->ehRepresentanteOsc();
        abort_unless($ehOsc ? $user->osc !== null
            : ($user->can('prestacao_contas') || $user->can('execucao') || $user->can('monitoramento')), 403,
            'Seu perfil não acessa os relatórios da prestação de contas.');

        $filtros = $request->validate([
            'relatorio'      => ['nullable', Rule::in(array_keys(RelatorioPrestacao::RELATORIOS))],
            'orgao_id'       => ['nullable', 'integer'],
            'osc_id'         => ['nullable', 'integer'],
            'instrumento_id' => ['nullable', 'integer'],
            'de'             => ['nullable', 'date_format:Y-m-d'],
            'ate'            => ['nullable', 'date_format:Y-m-d', 'after_or_equal:de'],
            'situacao'       => ['nullable', Rule::in(array_keys(RelatorioPrestacao::SITUACOES))],
            'formato'        => ['nullable', Rule::in(['tela', 'planilha', 'pdf'])],
        ], ['ate.after_or_equal' => 'O fim do período não pode ser anterior ao início.']);
        $filtros['relatorio'] ??= 'execucao';

        // A OSC não filtra por Secretaria nem por outra organização.
        if ($ehOsc) {
            unset($filtros['orgao_id'], $filtros['osc_id']);
        }

        $relatorio = RelatorioPrestacao::gerar($user, $filtros);
        $arquivo = 'relatorio-' . \Illuminate\Support\Str::slug($relatorio['titulo']) . '-' . now()->format('Y-m-d');

        return match ($filtros['formato'] ?? 'tela') {
            'planilha' => response(RelatorioPrestacao::csv($relatorio), 200, [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $arquivo . '.csv"',
            ]),
            'pdf' => response(RelatorioPrestacao::pdf($relatorio), 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $arquivo . '.pdf"',
            ]),
            default => response()->view('relatorios.prestacao', [
                'r'          => $relatorio,
                'filtros'    => $filtros,
                'ehOsc'      => $ehOsc,
                'parcerias'  => RelatorioPrestacao::parcerias($user)->with('proposta.osc')->get(),
                'orgaos'     => $ehOsc ? collect() : ($user->podeVerTodosOrgaos() ? Orgao::orderBy('name')->get(['id', 'name'])
                    : Orgao::whereKey($user->orgao_id)->get(['id', 'name'])),
                'oscs'       => $ehOsc ? collect() : Osc::whereIn('id', \App\Models\Proposta::whereIn('id',
                    RelatorioPrestacao::parcerias($user)->reorder()->select('proposta_id'))->select('osc_id'))->orderBy('name')->get(['id', 'name']),
            ]),
        };
    }
}
