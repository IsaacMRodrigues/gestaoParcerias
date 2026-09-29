<?php

namespace App\Http\Controllers;

use App\Models\Chamamento;
use App\Models\Recurso;
use App\Support\Avisos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Recursos contra o resultado provisório: a OSC protocola pelo portal, com um
 * arquivo (como prevê o modelo do Resultado Provisório), e a Comissão de
 * Seleção pode respondê-los com a peça "Resposta ao recurso", opcional, na
 * mesma etapa 3 da Seleção (decisão da gestão, 29/09/2026).
 */
class RecursoController extends Controller
{
    /**
     * A OSC protocola o seu recurso — só na fase recursal e só se participou
     * do chamamento.
     */
    public function store(Request $request, Chamamento $chamamento): RedirectResponse
    {
        $osc = auth()->user()->osc;
        abort_unless($osc, 403, 'Sua conta não está vinculada a uma OSC.');

        // Recurso é peça que a entidade assina — mesma regra da submissão:
        // a equipe prepara, o responsável legal protocola.
        abort_unless(auth()->user()->ehResponsavelLegalOsc(), 403,
            'Somente o responsável legal da OSC pode protocolar recurso.');

        abort_unless($chamamento->faseRecursalAberta(), 422,
            'O prazo de recurso deste chamamento não está aberto.');

        $proposta = $chamamento->propostas()->where('osc_id', $osc->id)->first();
        abort_unless($proposta, 403,
            'Só quem apresentou proposta neste chamamento pode recorrer.');

        abort_if(
            $chamamento->recursos()->where('osc_id', $osc->id)->exists(),
            422,
            'Sua OSC já protocolou um recurso neste chamamento.'
        );

        // O recurso é o arquivo que a OSC anexa, com as razões dentro dele
        // (decisão da gestão, 29/09/2026). O campo de texto saiu.
        $request->validate([
            'arquivo' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ], [
            'arquivo.required' => 'Anexe o recurso assinado, em PDF.',
            'arquivo.mimes'    => 'O recurso deve ser enviado em arquivo único no formato PDF.',
            'arquivo.max'      => 'O arquivo não pode ultrapassar 10 MB.',
        ]);

        $arquivo = $request->file('arquivo');
        $path = $arquivo->store("recursos/{$chamamento->id}", 'local');

        $recurso = $chamamento->recursos()->create([
            'osc_id'          => $osc->id,
            'proposta_id'     => $proposta->id,
            'arquivo_path'    => $path,
            'arquivo_nome'    => $arquivo->getClientOriginalName(),
            'tamanho'         => $arquivo->getSize(),
            'mime_type'       => $arquivo->getMimeType(),
            'protocolado_por' => auth()->id(),
            'protocolado_em'  => now(),
        ]);

        Avisos::recursoProtocolado($recurso);

        return back()->with('success',
            'Recurso protocolado. A Comissão de Seleção o analisará; a resposta, se houver, sai nos documentos da Seleção.');
    }

    /**
     * Download da peça recursal: equipe da Administração ou a própria OSC.
     */
    public function download(Recurso $recurso): StreamedResponse
    {
        $user = auth()->user();
        $daOsc = $user->ehRepresentanteOsc() && $user->osc->id === $recurso->osc_id;

        abort_unless($daOsc || $user->can('chamamentos') || $recurso->comissaoPodeVer($user), 403);
        abort_unless($recurso->arquivo_path && Storage::disk('local')->exists($recurso->arquivo_path), 404);

        return Storage::disk('local')->download($recurso->arquivo_path, $recurso->arquivo_nome);
    }
}
