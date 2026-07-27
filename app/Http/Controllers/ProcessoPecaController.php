<?php

namespace App\Http\Controllers;

use App\Models\Processo;
use App\Models\ProcessoPeca;
use App\Models\ProcessoPecaAnexo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProcessoPecaController extends Controller
{
    public function edit(Processo $processo, ProcessoPeca $peca): View
    {
        abort_unless($peca->processo_id === $processo->id, 404);
        abort_unless($processo->visivelPara(auth()->user()), 403, 'Este processo pertence a outra Secretaria.');

        $podeEditar = $peca->podeEditarConteudo($processo, auth()->user());
        $podeAssinar = $peca->podeAssinar($processo, auth()->user());
        $podeAnexar = $peca->podeAnexar($processo, auth()->user());
        $anexos = $peca->aceitaAnexos() ? $peca->anexos()->with('remetente')->get() : collect();

        // QR Code da validação (apontando para a página pública)
        $qrValidacao = null;
        if ($peca->assinado() && $peca->codigo_validacao) {
            $qrValidacao = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                ->size(110)->margin(0)
                ->generate(route('validacao.mostrar', $peca->codigo_validacao));
        }

        return view('processos.peca', compact('processo', 'peca', 'podeEditar', 'podeAssinar', 'podeAnexar', 'anexos', 'qrValidacao'));
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

    /** Anexa um arquivo à peça (peça ARQUIVO ou de texto que aceita anexos, ex. Edital). */
    public function anexar(Request $request, Processo $processo, ProcessoPeca $peca): RedirectResponse
    {
        abort_unless($peca->processo_id === $processo->id, 404);
        abort_unless($peca->podeAnexar($processo, auth()->user()), 403,
            'Você não pode anexar arquivos a esta peça nesta etapa.');

        $request->validate([
            'arquivo' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png', 'max:10240'],
        ], [
            'arquivo.required' => 'Selecione um arquivo para anexar.',
            'arquivo.max'      => 'O arquivo não pode ultrapassar 10 MB.',
            'arquivo.mimes'    => 'Formatos aceitos: PDF, Word, Excel, JPG, PNG.',
        ]);

        $arquivo = $request->file('arquivo');
        $path = $arquivo->store('processo-pecas/' . $peca->id, 'local');

        $peca->anexos()->create([
            'arquivo_path' => $path,
            'arquivo_nome' => $arquivo->getClientOriginalName(),
            'tamanho'      => $arquivo->getSize(),
            'mime_type'    => $arquivo->getMimeType(),
            'enviado_por'  => auth()->id(),
        ]);

        return redirect()->route('processos.pecas.edit', [$processo, $peca])
            ->with('success', 'Arquivo anexado.');
    }

    /** Baixa um anexo da peça. */
    public function baixarAnexo(Processo $processo, ProcessoPeca $peca, ProcessoPecaAnexo $anexo): StreamedResponse
    {
        abort_unless($peca->processo_id === $processo->id && $anexo->processo_peca_id === $peca->id, 404);
        abort_unless(Storage::disk('local')->exists($anexo->arquivo_path), 404, 'Arquivo não encontrado.');

        return Storage::disk('local')->download($anexo->arquivo_path, $anexo->arquivo_nome);
    }

    /** Remove um anexo da peça (mesma regra de quem pode anexar). */
    public function removerAnexo(Processo $processo, ProcessoPeca $peca, ProcessoPecaAnexo $anexo): RedirectResponse
    {
        abort_unless($peca->processo_id === $processo->id && $anexo->processo_peca_id === $peca->id, 404);
        abort_unless($peca->podeAnexar($processo, auth()->user()), 403,
            'Você não pode remover anexos desta peça nesta etapa.');

        if ($anexo->arquivo_path) {
            Storage::disk('local')->delete($anexo->arquivo_path);
        }
        $anexo->delete();

        return redirect()->route('processos.pecas.edit', [$processo, $peca])
            ->with('success', 'Anexo removido.');
    }
}
