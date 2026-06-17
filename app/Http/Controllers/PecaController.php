<?php

namespace App\Http\Controllers;

use App\Models\Peca;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PecaController extends Controller
{
    public function salvar(Request $request, Peca $peca): RedirectResponse
    {
        abort_if($peca->tipo !== 'modelo', 422);

        $data = $request->validate([
            'conteudo' => ['nullable', 'string'],
        ]);

        $peca->update($data);

        return back()->with('success', $peca->rotulo . ' salvo.');
    }

    public function assinar(Peca $peca): RedirectResponse
    {
        abort_if($peca->tipo !== 'modelo', 422);

        $peca->update([
            'assinado_por' => auth()->id(),
            'assinado_em'  => now(),
        ]);

        return back()->with('success', $peca->rotulo . ' assinado.');
    }

    public function upload(Request $request, Peca $peca): RedirectResponse
    {
        abort_if($peca->tipo !== 'arquivo', 422);

        $request->validate([
            'arquivo' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png', 'max:10240'],
        ], [
            'arquivo.max'   => 'O arquivo não pode ultrapassar 10 MB.',
            'arquivo.mimes' => 'Formatos aceitos: PDF, Word, Excel, JPG, PNG.',
        ]);

        // remove arquivo anterior, se houver
        if ($peca->arquivo_path) {
            Storage::disk('local')->delete($peca->arquivo_path);
        }

        $arquivo = $request->file('arquivo');
        $path = $arquivo->store('pecas/' . $peca->id, 'local');

        $peca->update([
            'arquivo_path' => $path,
            'arquivo_nome' => $arquivo->getClientOriginalName(),
            'tamanho'      => $arquivo->getSize(),
            'mime_type'    => $arquivo->getMimeType(),
        ]);

        return back()->with('success', $peca->rotulo . ' enviado.');
    }

    public function download(Peca $peca): StreamedResponse
    {
        abort_unless($peca->arquivo_path && Storage::disk('local')->exists($peca->arquivo_path), 404);

        return Storage::disk('local')->download($peca->arquivo_path, $peca->arquivo_nome);
    }

    public function removerArquivo(Peca $peca): RedirectResponse
    {
        if ($peca->arquivo_path) {
            Storage::disk('local')->delete($peca->arquivo_path);
        }

        $peca->update([
            'arquivo_path' => null,
            'arquivo_nome' => null,
            'tamanho'      => null,
            'mime_type'    => null,
        ]);

        return back()->with('success', 'Arquivo removido.');
    }
}
