<?php

namespace App\Http\Controllers;

use App\Models\Chamamento;
use App\Models\ChamamentoProrrogacao;
use App\Support\Avisos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Prorrogação do prazo de inscrições do chamamento (decisão da gestão,
 * 28/09/2026).
 *
 * Quem prorroga é a SCP, anexando os dois documentos que a fundamentam — o
 * aviso de prorrogação e o comprovante da publicação. O prazo novo vale na
 * hora: se as inscrições já tinham acabado, reabrem até a nova data. As OSCs
 * com proposta no chamamento são avisadas, e a página pública mostra a
 * prorrogação com os dois documentos, que são públicos como o edital.
 */
class ChamamentoProrrogacaoController extends Controller
{
    public function store(Request $request, Chamamento $chamamento): RedirectResponse
    {
        abort_unless($chamamento->prorrogavelPor(auth()->user()), 403,
            'Quem prorroga o prazo de inscrições é o Setor de Convênios e Parcerias.');

        if ($motivo = $chamamento->motivoParaNaoProrrogar()) {
            return back()->withErrors(['prorrogacao' => $motivo]);
        }

        $base = $chamamento->data_fim_inscricao && $chamamento->data_fim_inscricao->isFuture()
            ? $chamamento->data_fim_inscricao
            : now()->startOfDay();

        $dados = $request->validate([
            'fim_novo'   => ['required', 'date', 'after:' . $base->format('Y-m-d')],
            'aviso'      => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'publicacao' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ], [
            'fim_novo.required'   => 'Informe o novo prazo de inscrições.',
            'fim_novo.after'      => 'O novo prazo precisa ser depois de ' . $base->format('d/m/Y') . '.',
            'aviso.required'      => 'Anexe o aviso de prorrogação.',
            'publicacao.required' => 'Anexe o comprovante de publicação da prorrogação.',
            '*.mimes'             => 'Formatos aceitos: PDF, JPG ou PNG.',
            '*.max'               => 'Cada arquivo pode ter até 10 MB.',
        ]);

        $anterior = $chamamento->data_fim_inscricao;

        DB::transaction(function () use ($chamamento, $request, $dados, $anterior) {
            $pasta = "chamamentos/{$chamamento->id}/prorrogacoes";

            $chamamento->prorrogacoes()->create([
                'fim_anterior'    => $anterior,
                'fim_novo'        => $dados['fim_novo'],
                'aviso_path'      => $request->file('aviso')->store($pasta, 'local'),
                'aviso_nome'      => $request->file('aviso')->getClientOriginalName(),
                'publicacao_path' => $request->file('publicacao')->store($pasta, 'local'),
                'publicacao_nome' => $request->file('publicacao')->getClientOriginalName(),
                'user_id'         => auth()->id(),
                'autor_nome'      => auth()->user()->name,
            ]);

            $chamamento->update(['data_fim_inscricao' => $dados['fim_novo']]);
        });

        Avisos::chamamentoProrrogado($chamamento->fresh(), $anterior);

        return back()->with('success', 'Prazo de inscrições prorrogado até '
            . $chamamento->fresh()->data_fim_inscricao->format('d/m/Y') . '.');
    }

    /**
     * Os dois documentos são públicos — publicados como o edital — e abrem sem
     * login, pela página pública do chamamento.
     */
    public function arquivo(ChamamentoProrrogacao $prorrogacao, string $documento)
    {
        abort_unless(isset(ChamamentoProrrogacao::DOCUMENTOS[$documento]), 404);

        $caminho = $prorrogacao->{$documento . '_path'};
        abort_unless($caminho && Storage::disk('local')->exists($caminho), 404, 'Arquivo não encontrado.');

        return Storage::disk('local')->download($caminho, $prorrogacao->{$documento . '_nome'});
    }
}
