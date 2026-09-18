<?php

namespace App\Http\Controllers;

use App\Models\Peca;
use App\Models\ProcessoPeca;
use App\Models\Proposta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * O dossiê da parceria como a OSC o vê (módulo 3.3).
 *
 * Duas pontas do mesmo assunto: a organização abre, na sua inscrição, os
 * documentos das quatro fases; e quem conduz o processo decide, numa tela, o
 * que fica aberto. Antes a régua era uma lista de três tipos cravada no
 * código da página do chamamento.
 *
 * O acesso da OSC não depende de adivinhar identificadores: o documento tem
 * de pertencer à cadeia daquela parceria, estar marcado como visível e estar
 * pronto — assinado ou com arquivo. Minuta não circula.
 */
class DossieController extends Controller
{
    // ------------------------------------------------------------- a OSC lê

    public function mostrar(Request $request, Proposta $proposta, string $origem, string $id): View
    {
        $this->autorizarOsc($proposta);
        $peca = $this->documentoAberto($proposta, $origem, $id);

        abort_if($peca->ehArquivoNoDossie(), 404, 'Este documento é um arquivo — use o download.');

        return view('portal.dossie-documento', compact('proposta', 'peca'));
    }

    public function baixar(Request $request, Proposta $proposta, string $origem, string $id)
    {
        $this->autorizarOsc($proposta);
        $peca = $this->documentoAberto($proposta, $origem, $id);

        abort_unless($peca->ehArquivoNoDossie() && $peca->temArquivo(), 404);
        abort_unless(Storage::disk('local')->exists($peca->arquivo_path), 404);

        return Storage::disk('local')->download($peca->arquivo_path, $peca->arquivo_nome);
    }

    // ------------------------------------------------ o município cura

    public function curadoria(Proposta $proposta): View
    {
        $this->autorizarMunicipio($proposta);

        $proposta->load(['osc', 'chamamento.programa.orgao', 'chamamento.processo.pecas',
            'chamamento.pecas', 'pecas', 'instrumento.pecas']);

        return view('dossie.curadoria', [
            'proposta' => $proposta,
            'grupos'   => $proposta->dossieParaCuradoria(),
        ]);
    }

    public function salvarCuradoria(Request $request, Proposta $proposta): RedirectResponse
    {
        $this->autorizarMunicipio($proposta);

        $dados = $request->validate([
            'abertas'   => ['array'],
            'abertas.*' => ['string'],
        ]);

        $abertas = array_flip($dados['abertas'] ?? []);

        // Percorre o que a tela mostrou: o que não voltou marcado foi fechado.
        foreach ($proposta->dossieParaCuradoria() as $documentos) {
            foreach ($documentos as $doc) {
                $chave = $doc->chaveDoDossie();
                $doc->update(['visivel_osc' => isset($abertas[$chave])]);
            }
        }

        return back()->with('success', 'Documentos visíveis à organização atualizados.');
    }

    // --------------------------------------------------------------- apoio

    private function autorizarOsc(Proposta $proposta): void
    {
        $osc = auth()->user()->oscVinculada();
        abort_unless($osc && $proposta->osc_id === $osc->id, 403, 'Esta parceria é de outra organização.');
    }

    private function autorizarMunicipio(Proposta $proposta): void
    {
        $user = auth()->user();

        abort_if($user->ehRepresentanteOsc(), 403, 'Quem decide o que a organização vê é o município.');

        abort_unless(
            $user->can('chamamentos') || $user->can('propostas')
                || $user->can('formalizacao') || $user->can('execucao'),
            403,
            'Seu perfil não conduz processos de parceria.'
        );

        // Mesmo recorte por Secretaria das demais telas da parceria.
        abort_unless(
            $user->podeVerTodosOrgaos()
                || $proposta->chamamento?->programa?->orgao_id === $user->orgao_id,
            403,
            'Esta parceria é de outra Secretaria.'
        );
    }

    /**
     * O documento pedido, confirmado como desta parceria, aberto e pronto.
     *
     * A checagem é feita sobre a lista montada para esta proposta — e não por
     * uma busca pelo id —, então um identificador de outra parceria não tem
     * como passar.
     */
    private function documentoAberto(Proposta $proposta, string $origem, string $id): Peca|ProcessoPeca
    {
        $procurado = $origem . ':' . $id;

        foreach ($proposta->dossieParaOsc() as $documentos) {
            foreach ($documentos as $doc) {
                if ($doc->chaveDoDossie() === $procurado) {
                    return $doc;
                }
            }
        }

        abort(404, 'Documento não encontrado no dossiê desta parceria.');
    }
}
