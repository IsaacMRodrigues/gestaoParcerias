<?php

namespace App\Http\Controllers;

use App\Models\Processo;
use App\Models\ProcessoPeca;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProcessoPecaController extends Controller
{
    public function edit(Processo $processo, ProcessoPeca $peca): View
    {
        abort_unless($peca->processo_id === $processo->id, 404);
        abort_unless($processo->visivelPara(auth()->user()), 403, 'Este processo pertence a outra Secretaria.');

        $podeEditar = $peca->podeEditarConteudo($processo, auth()->user());
        $podeAssinar = $peca->podeAssinar($processo, auth()->user());

        // QR Code da validação (apontando para a página pública)
        $qrValidacao = null;
        if ($peca->assinado() && $peca->codigo_validacao) {
            $qrValidacao = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                ->size(110)->margin(0)
                ->generate(route('validacao.mostrar', $peca->codigo_validacao));
        }

        return view('processos.peca', compact('processo', 'peca', 'podeEditar', 'podeAssinar', 'qrValidacao'));
    }

    /**
     * Baixa as peças selecionadas como PDF: uma só baixa o PDF direto;
     * várias vêm num ZIP com um PDF separado por documento (download individual).
     */
    public function imprimirLote(Request $request, Processo $processo)
    {
        abort_unless($processo->visivelPara(auth()->user()), 403, 'Este processo pertence a outra Secretaria.');

        $ids = array_map('intval', (array) $request->query('pecas', []));

        $ordemTipos = array_keys(ProcessoPeca::TIPOS);

        $pecas = $processo->pecas()
            ->whereIn('id', $ids)
            ->whereNotNull('conteudo')
            ->with('assinante.roles', 'assinante.orgao')
            ->get()
            ->sortBy(fn ($p) => array_search($p->tipo, $ordemTipos))
            ->values();

        abort_if($pecas->isEmpty(), 404, 'Selecione ao menos um documento preenchido para baixar.');

        // Um único documento: baixa o PDF diretamente.
        if ($pecas->count() === 1) {
            $peca = $pecas->first();
            return response($this->pdfDaPeca($peca), 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $this->nomeArquivoPdf($peca, $ordemTipos) . '"',
            ]);
        }

        // Vários: um PDF por documento, empacotados num ZIP.
        $zipPath = tempnam(sys_get_temp_dir(), 'pecas_') . '.zip';
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        foreach ($pecas as $peca) {
            $zip->addFromString($this->nomeArquivoPdf($peca, $ordemTipos), $this->pdfDaPeca($peca));
        }
        $zip->close();

        return response()->download($zipPath, 'Processo-' . $processo->numero . '-documentos.zip')
            ->deleteFileAfterSend(true);
    }

    /** Gera o PDF (bytes) de uma peça do processo. */
    private function pdfDaPeca(ProcessoPeca $peca): string
    {
        $qrImg = null;
        if ($peca->assinado() && $peca->codigo_validacao) {
            $svg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                ->size(110)->margin(0)
                ->generate(route('validacao.mostrar', $peca->codigo_validacao));
            $qrImg = '<img src="data:image/svg+xml;base64,' . base64_encode($svg) . '" style="width:110px;height:110px;">';
        }

        $html = view('processos.peca-pdf', compact('peca', 'qrImg'))->render();

        $dompdf = new \Dompdf\Dompdf([
            'isRemoteEnabled'      => true,   // permite carregar o brasão (logo remoto)
            'isHtml5ParserEnabled' => true,
            'defaultFont'          => 'Helvetica',
        ]);
        $dompdf->setPaper('A4');
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();

        return $dompdf->output();
    }

    /** Nome do arquivo PDF: "03-pedido-de-parecer-financeiro.pdf" (na ordem oficial). */
    private function nomeArquivoPdf(ProcessoPeca $peca, array $ordemTipos): string
    {
        $pos   = array_search($peca->tipo, $ordemTipos);
        $label = ProcessoPeca::TIPOS[$peca->tipo] ?? $peca->tipo;

        return sprintf('%02d-%s.pdf', ($pos === false ? 99 : $pos + 1), \Illuminate\Support\Str::slug($label));
    }

    public function imprimir(Processo $processo, ProcessoPeca $peca): View
    {
        abort_unless($peca->processo_id === $processo->id, 404);
        abort_unless($processo->visivelPara(auth()->user()), 403, 'Este processo pertence a outra Secretaria.');

        $qrValidacao = null;
        if ($peca->assinado() && $peca->codigo_validacao) {
            $qrValidacao = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                ->size(110)->margin(0)
                ->generate(route('validacao.mostrar', $peca->codigo_validacao));
        }

        return view('processos.peca-impressao', compact('processo', 'peca', 'qrValidacao'));
    }

    public function update(Request $request, Processo $processo, ProcessoPeca $peca): RedirectResponse
    {
        abort_unless($peca->processo_id === $processo->id, 404);
        abort_unless($processo->visivelPara(auth()->user()), 403, 'Este processo pertence a outra Secretaria.');
        abort_unless($peca->podeEditarConteudo($processo, auth()->user()), 403,
            'Esta peça é de responsabilidade do setor '
            . (Processo::SETORES[$peca->setorResponsavel()] ?? $peca->setorResponsavel())
            . ' e só pode ser editada na etapa correspondente.');

        $peca->update($request->validate(['conteudo' => ['nullable', 'string']]));

        return redirect()->route('processos.show', $processo)
            ->with('success', ProcessoPeca::TIPOS[$peca->tipo] . ' salvo.');
    }

    public function assinar(Processo $processo, ProcessoPeca $peca): RedirectResponse
    {
        abort_unless($peca->processo_id === $processo->id, 404);
        abort_unless($processo->visivelPara(auth()->user()), 403, 'Este processo pertence a outra Secretaria.');
        abort_unless($peca->podeAssinar($processo, auth()->user()), 403,
            'Você não pode assinar esta peça nesta etapa.');

        $peca->update([
            'assinado_por'     => auth()->id(),
            'assinado_em'      => now(),
            'codigo_validacao' => $peca->codigo_validacao ?: ProcessoPeca::gerarCodigoValidacao(),
        ]);

        return redirect()->route('processos.show', $processo)
            ->with('success', ProcessoPeca::TIPOS[$peca->tipo] . ' assinado.');
    }
}
