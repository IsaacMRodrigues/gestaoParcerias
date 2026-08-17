<?php

namespace App\Http\Controllers;

use App\Models\Chamamento;
use App\Models\Recurso;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Recursos contra o resultado provisório: a OSC protocola pelo portal (como
 * prevê o modelo do Resultado Provisório) e a Unidade Gestora responde antes de
 * emitir o resultado definitivo.
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

        abort_unless($chamamento->faseRecursalAberta(), 422,
            'A fase recursal deste chamamento não está aberta.');

        $proposta = $chamamento->propostas()->where('osc_id', $osc->id)->first();
        abort_unless($proposta, 403,
            'Só quem apresentou proposta neste chamamento pode recorrer.');

        abort_if(
            $chamamento->recursos()->where('osc_id', $osc->id)->exists(),
            422,
            'Sua OSC já protocolou um recurso neste chamamento.'
        );

        $data = $request->validate([
            'fundamentacao' => ['required', 'string', 'min:20'],
            'arquivo'       => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ], [
            'fundamentacao.required' => 'Descreva a fundamentação do recurso.',
            'fundamentacao.min'      => 'Detalhe melhor a fundamentação do recurso.',
            'arquivo.required'       => 'Anexe a peça recursal assinada, em PDF.',
            'arquivo.mimes'          => 'O recurso deve ser enviado em arquivo único no formato PDF.',
            'arquivo.max'            => 'O arquivo não pode ultrapassar 10 MB.',
        ]);

        $arquivo = $request->file('arquivo');
        $path = $arquivo->store("recursos/{$chamamento->id}", 'local');

        $chamamento->recursos()->create([
            'osc_id'          => $osc->id,
            'proposta_id'     => $proposta->id,
            'fundamentacao'   => $data['fundamentacao'],
            'arquivo_path'    => $path,
            'arquivo_nome'    => $arquivo->getClientOriginalName(),
            'tamanho'         => $arquivo->getSize(),
            'mime_type'       => $arquivo->getMimeType(),
            'protocolado_por' => auth()->id(),
            'protocolado_em'  => now(),
        ]);

        return back()->with('success',
            'Recurso protocolado. A Unidade Gestora analisará e publicará a resposta aqui.');
    }

    /**
     * A Unidade Gestora responde ao recurso (etapa 2 da Seleção).
     */
    public function responder(Request $request, Recurso $recurso): RedirectResponse
    {
        $chamamento = $recurso->chamamento;

        abort_unless(auth()->user()->setor === $chamamento->selecao_setor, 403,
            'Apenas o setor que está com a Seleção pode responder aos recursos.');
        abort_unless($chamamento->faseRecursalAberta(), 422,
            'A resposta aos recursos é feita na etapa de análise dos recursos.');

        $data = $request->validate([
            'resultado' => ['required', Rule::in(array_keys(Recurso::RESULTADOS))],
            'resposta'  => ['required', 'string', 'min:20'],
        ], [
            'resultado.required' => 'Informe o resultado do julgamento do recurso.',
            'resposta.required'  => 'Escreva a resposta ao recurso.',
            'resposta.min'       => 'Detalhe melhor a resposta ao recurso.',
        ]);

        $recurso->update([
            'resultado'        => $data['resultado'],
            'resposta'         => $data['resposta'],
            'respondido_por'   => auth()->id(),
            'respondido_em'    => now(),
            'codigo_validacao' => $recurso->codigo_validacao ?: Recurso::gerarCodigoValidacao(),
        ]);

        return back()->with('success',
            'Resposta registrada e disponibilizada à OSC (' . $recurso->resultadoLabel() . ').');
    }

    /**
     * Download da peça recursal: equipe da Administração ou a própria OSC.
     */
    public function download(Recurso $recurso): StreamedResponse
    {
        $user = auth()->user();
        $daOsc = $user->ehRepresentanteOsc() && $user->osc->id === $recurso->osc_id;

        abort_unless($daOsc || $user->can('chamamentos'), 403);
        abort_unless($recurso->arquivo_path && Storage::disk('local')->exists($recurso->arquivo_path), 404);

        return Storage::disk('local')->download($recurso->arquivo_path, $recurso->arquivo_nome);
    }
}
