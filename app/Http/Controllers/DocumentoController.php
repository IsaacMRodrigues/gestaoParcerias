<?php

namespace App\Http\Controllers;

use App\Models\Documento;
use App\Models\Proposta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentoController extends Controller
{
    public function store(Request $request, Proposta $proposta): RedirectResponse
    {
        $this->autorizarAcesso($proposta);

        $request->validate([
            'arquivo' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png', 'max:10240'],
            'tipo'    => ['required', Rule::in(array_keys(Documento::TIPOS))],
        ], [
            'arquivo.max'   => 'O arquivo não pode ultrapassar 10 MB.',
            'arquivo.mimes' => 'Formatos aceitos: PDF, Word, Excel, JPG, PNG.',
        ]);

        $arquivo = $request->file('arquivo');
        $path    = $arquivo->store("documentos/{$proposta->id}", 'local');

        $proposta->documentos()->create([
            'uploaded_by'   => auth()->id(),
            'nome_original' => $arquivo->getClientOriginalName(),
            'path'          => $path,
            'tipo'          => $request->tipo,
            'tamanho'       => $arquivo->getSize(),
            'mime_type'     => $arquivo->getMimeType(),
        ]);

        return back()->with('success', 'Documento enviado com sucesso.');
    }

    public function download(Documento $documento): StreamedResponse
    {
        $this->autorizarAcesso($documento->proposta);
        abort_unless(Storage::disk('local')->exists($documento->path), 404);

        return Storage::disk('local')->download($documento->path, $documento->nome_original);
    }

    public function destroy(Proposta $proposta, Documento $documento): RedirectResponse
    {
        $this->autorizarAcesso($proposta);
        abort_unless($documento->proposta_id === $proposta->id, 404);

        Storage::disk('local')->delete($documento->path);
        $documento->delete();

        return back()->with('success', 'Documento removido.');
    }

    private function autorizarAcesso(Proposta $proposta): void
    {
        $userOsc = auth()->user()->osc;
        if ($userOsc) {
            abort_unless($proposta->osc_id === $userOsc->id, 403);
        }
    }
}
