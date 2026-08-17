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
        $this->autorizarEscrita($proposta);

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
        $this->autorizarLeitura($documento->proposta);
        abort_unless(Storage::disk('local')->exists($documento->path), 404);

        return Storage::disk('local')->download($documento->path, $documento->nome_original);
    }

    public function destroy(Proposta $proposta, Documento $documento): RedirectResponse
    {
        $this->autorizarEscrita($proposta);
        abort_unless($documento->proposta_id === $proposta->id, 404);

        Storage::disk('local')->delete($documento->path);
        $documento->delete();

        return back()->with('success', 'Documento removido.');
    }

    /**
     * Quem pode ver os documentos desta proposta.
     *
     * A versão anterior só sabia negar para a OSC dona de outra proposta: quem
     * não tinha OSC — ou seja, todo usuário interno — passava sem nenhuma
     * checagem, e qualquer servidor autenticado baixava e apagava documentos de
     * qualquer proposta do município. Agora cada lado é verificado pelo que de
     * fato o autoriza, e ninguém entra por omissão.
     */
    private function autorizarLeitura(Proposta $proposta): void
    {
        $user = auth()->user();

        if ($user->ehRepresentanteOsc()) {
            // A OSC só alcança a própria proposta.
            abort_unless($proposta->osc_id === $user->osc->id, 403);
            return;
        }

        // Servidor: mesma permissão e mesmo recorte por órgão da listagem de
        // propostas — o que a tela não mostra, o download não entrega.
        abort_unless($user->can('propostas'), 403);
        abort_unless(
            Proposta::visiveisPara($user)->whereKey($proposta->getKey())->exists(),
            403,
            'Esta proposta é de outro órgão.'
        );
    }

    /** Escrita: tudo da leitura, mais o bloqueio dos perfis de auditoria. */
    private function autorizarEscrita(Proposta $proposta): void
    {
        $this->autorizarLeitura($proposta);

        // Estas rotas ficam fora do grupo 'readonly' (a OSC também as usa), por
        // isso a restrição do Controle Interno é repetida aqui.
        abort_if(
            auth()->user()->somenteLeitura(),
            403,
            'Seu perfil (auditoria) tem acesso somente de leitura.'
        );
    }
}
