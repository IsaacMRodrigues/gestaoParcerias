<?php

namespace App\Support;

use App\Models\Despesa;
use App\Models\Lancamento;
use App\Models\Peca;
use App\Models\PrestacaoContas;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Monta a prestação de contas com o que a OSC lançou no painel durante o período: a folha somada,
 * os bens e os arquivos dos documentos (extratos, evidências, notas fiscais e o resto). O devolvido
 * pela Prefeitura fica de fora até a OSC corrigir. Peça com arquivo enviado à mão não é tocada.
 * A devolução de uma peça só se desfaz quando a OSC pede para atualizar ($corrigindo).
 */
class PrestacaoDosLancamentos
{
    /** Peça da prestação => o que a compõe. */
    private const PECAS = [
        'extrato_conta'         => ['extrato_conta'],
        'extrato_aplicacao'     => ['extrato_aplicacao'],
        'docs_execucao'         => ['evidencia'],
        'comprovantes_despesas' => ['despesas'],
        'outros_documentos'     => ['folha', 'bem'],
    ];

    public static function montar(PrestacaoContas $pc, bool $corrigindo = false): void
    {
        $lancamentos = self::lancamentos($pc);

        self::folha($pc, $lancamentos->where('tipo', 'folha'));
        self::bens($pc, $lancamentos->where('tipo', 'bem'));

        $despesas = $pc->despesas()->reject(fn (Despesa $d) => $d->devolvido() || !$d->temNotaFiscal());
        $pecas = $pc->pecas()->whereIn('chave', array_keys(self::PECAS))->get()->keyBy('chave');

        foreach (self::PECAS as $chave => $origens) {
            $peca = $pecas[$chave] ?? null;
            if (!$peca || ($peca->arquivo_path && !$peca->dos_lancamentos)) {
                continue;
            }

            $arquivos = collect();
            foreach ($origens as $origem) {
                $arquivos = $arquivos->concat($origem === 'despesas'
                    ? $despesas->map(fn (Despesa $d) => [
                        'path' => $d->nota_fiscal_path, 'nome' => $d->nota_fiscal_nome, 'data' => $d->data_despesa])
                    : $lancamentos->where('tipo', $origem)->filter->temArquivo()->map(fn (Lancamento $l) => [
                        'path' => $l->arquivo_path, 'nome' => $l->arquivo_nome, 'data' => $l->competencia]));
            }

            self::arquivoDaPeca($peca, $arquivos->filter(fn ($a) => Storage::disk('local')->exists($a['path']))
                ->sortBy(fn ($a) => $a['data']->format('Y-m-d'))->values(), $pc);

            if ($corrigindo) {
                $peca->limparDevolucao();
            }
        }
    }

    /** O que entra: do período, sem os devolvidos. */
    public static function lancamentos(PrestacaoContas $pc): Collection
    {
        return Lancamento::where('instrumento_id', $pc->instrumento_id)
            ->whereBetween('competencia', [$pc->periodo_inicio->toDateString(), $pc->periodo_fim->toDateString()])
            ->where('situacao', '!=', 'devolvido')
            ->orderBy('competencia')->orderBy('id')
            ->get();
    }

    /** Resumo da folha: os valores somados mês a mês; o número de funcionários é o maior do período. */
    private static function folha(PrestacaoContas $pc, Collection $folhas): void
    {
        if ($folhas->isEmpty()) {
            return;
        }

        $campos = [];
        foreach (array_keys(Lancamento::CAMPOS_DA_FOLHA) as $campo) {
            $valores = $folhas->map(fn (Lancamento $l) => (float) ($l->dados[$campo] ?? 0));
            $campos[$campo] = $campo === 'folha_funcionarios' ? (int) $valores->max() : round($valores->sum(), 2);
        }

        $pc->update($campos);
    }

    /** Os bens lançados entram no Anexo VII; os incluídos à mão na prestação ficam. */
    private static function bens(PrestacaoContas $pc, Collection $bens): void
    {
        $pc->bens()->whereNotNull('lancamento_id')->delete();

        foreach ($bens as $bem) {
            $pc->bens()->create([
                'lancamento_id'  => $bem->id,
                'especificacao'  => Str::limit((string) $bem->descricao, 250, ''),
                'quantidade'     => $bem->quantidade ?: 1,
                'valor_unitario' => $bem->valor ?: 0,
                'documento'      => Str::limit((string) ($bem->dados['documento'] ?? ''), 60, ''),
            ]);
        }
    }

    /** Um arquivo vai como veio; vários, num ZIP em ordem cronológica. Sem nenhum, a peça fica vazia. */
    private static function arquivoDaPeca(Peca $peca, Collection $arquivos, PrestacaoContas $pc): void
    {
        $disco = Storage::disk('local');

        if ($peca->arquivo_path) {
            $disco->delete($peca->arquivo_path);
        }

        if ($arquivos->isEmpty()) {
            $peca->update(['arquivo_path' => null, 'arquivo_nome' => null, 'tamanho' => null, 'mime_type' => null,
                'dos_lancamentos' => false]);

            return;
        }

        $pasta = 'pecas/' . $peca->id;

        if ($arquivos->count() === 1) {
            $a = $arquivos->first();
            $ext = pathinfo($a['nome'], PATHINFO_EXTENSION);
            $destino = $pasta . '/' . Str::random(20) . ($ext ? '.' . $ext : '');
            $disco->copy($a['path'], $destino);
            $nome = $a['nome'];
            $mime = $disco->mimeType($destino) ?: null;
        } else {
            $destino = $pasta . '/' . Str::random(20) . '.zip';
            $disco->makeDirectory($pasta);
            $zip = new \ZipArchive();
            $zip->open($disco->path($destino), \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
            foreach ($arquivos as $n => $a) {
                $zip->addFile($disco->path($a['path']), sprintf('%02d-%s-%s', $n + 1, $a['data']->format('Y-m-d'), $a['nome']));
            }
            $zip->close();
            $nome = Str::slug($peca->rotulo) . '-' . $pc->periodo_inicio->format('Y-m-d') . '-a-' . $pc->periodo_fim->format('Y-m-d') . '.zip';
            $mime = 'application/zip';
        }

        $peca->update(['arquivo_path' => $destino, 'arquivo_nome' => $nome, 'tamanho' => $disco->size($destino),
            'mime_type' => $mime, 'dos_lancamentos' => true]);
    }
}
