<?php

namespace App\Http\Controllers;

use App\Models\Osc;
use App\Models\OscArquivo;
use App\Models\OscArquivoAnalise;
use App\Models\Peca;
use App\Models\Proposta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * "Arquivos da OSC": certidões, estatuto, ata e declarações, anexados uma vez (cada envio é uma
 * versão). A Prefeitura os vê na área da OSC e os analisa em cada parceria.
 */
class OscArquivoController extends Controller
{
    // ------------------------------------------------------------- portal

    public function index(): View
    {
        $osc = auth()->user()->oscVinculada();
        abort_unless($osc, 403, 'Sua conta não está vinculada a uma OSC.');

        return view('portal.arquivos.index', [
            'osc'        => $osc->load('arquivos.remetente'),
            'podeEditar' => !auth()->user()->oscSemFuncao('osc_documentos'),
        ]);
    }

    public function store(Request $request, string $tipo): RedirectResponse
    {
        $osc = auth()->user()->oscVinculada();
        abort_unless($osc, 403);
        abort_if(auth()->user()->oscSemFuncao('osc_documentos'), 403,
            'Sua conta não tem a função "Documentos da organização".');
        abort_unless(array_key_exists($tipo, OscArquivo::tipos()), 404);

        $dados = $request->validate([
            'arquivo'  => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'validade' => [OscArquivo::exigeValidade($tipo) ? 'required' : 'nullable', 'date', 'after_or_equal:today'],
        ], [
            'arquivo.required'        => 'Escolha o arquivo.',
            'arquivo.mimes'           => 'Envie em PDF, JPG ou PNG.',
            'validade.required'       => 'Informe até quando o documento é válido.',
            'validade.after_or_equal' => 'O documento já está vencido: envie um atualizado.',
        ]);

        $arquivo = $request->file('arquivo');
        $osc->arquivos()->create([
            'tipo'         => $tipo,
            'versao'       => (int) $osc->arquivos()->where('tipo', $tipo)->max('versao') + 1,
            'arquivo_path' => $arquivo->store("osc-arquivos/{$osc->id}", 'local'),
            'arquivo_nome' => $arquivo->getClientOriginalName(),
            'mime_type'    => $arquivo->getMimeType(),
            'tamanho'      => $arquivo->getSize(),
            'validade'     => $dados['validade'] ?? null,
            'enviado_por'  => auth()->id(),
        ]);

        $this->atualizarCelebracoes($osc->propostas()->whereNotNull('celebracao_iniciada_em')->whereNull('celebracao_concluida_em')->get());

        return back()->withFragment('arquivo-' . $tipo)
            ->with('success', OscArquivo::rotulo($tipo) . ': nova versão anexada.');
    }

    /** O texto da declaração preenchido com o cadastro, para imprimir e assinar. */
    public function declaracao(string $tipo): View
    {
        $osc = auth()->user()->oscVinculada();
        abort_unless($osc && OscArquivo::ehDeclaracao($tipo), 404);

        return view('portal.arquivos.declaracao', [
            'titulo'   => OscArquivo::rotulo($tipo),
            'conteudo' => Peca::declaracaoParaOsc($tipo, $osc),
        ]);
    }

    // ---------------------------------------------------------- Prefeitura

    public function daOsc(Osc $osc): View
    {
        abort_unless(auth()->user()->temAcessoInterno(), 403);

        return view('oscs.arquivos', ['osc' => $osc->load('arquivos.remetente')]);
    }

    /** Análise de um arquivo numa parceria — a da versão que se viu. */
    public function analisar(Request $request, Proposta $proposta, OscArquivo $arquivo): RedirectResponse
    {
        abort_unless($arquivo->osc_id === $proposta->osc_id, 404);
        abort_unless($proposta->visivelPara(auth()->user()), 403);

        $dados = $request->validate([
            'situacao' => ['required', Rule::in(array_keys(OscArquivoAnalise::SITUACOES))],
            'motivo'   => ['required_if:situacao,recusado', 'nullable', 'string'],
        ], [
            'motivo.required_if' => 'Diga o motivo da recusa — é o que a OSC vai ler para corrigir.',
        ]);

        $analise = OscArquivoAnalise::updateOrCreate(
            ['proposta_id' => $proposta->id, 'osc_arquivo_id' => $arquivo->id],
            $dados + ['analisado_por' => auth()->id(), 'analisado_em' => now()],
        );

        if ($analise->situacao === 'recusado') {
            \App\Support\Avisos::arquivoRecusado($analise);
        }
        $this->atualizarCelebracoes([$proposta]);

        return back()->withFragment('arquivos-osc')
            ->with('success', OscArquivo::rotulo($arquivo->tipo) . ': ' . mb_strtolower(OscArquivoAnalise::SITUACOES[$dados['situacao']]) . '.');
    }

    /** Os itens da Celebração que vêm da área acompanham a versão nova ou a recusa. */
    private function atualizarCelebracoes(iterable $propostas): void
    {
        foreach ($propostas as $proposta) {
            if ($proposta->celebracao_iniciada_em) {
                Peca::puxarDaAreaDaOsc($proposta);
            }
        }
    }

    // ---------------------------------------------------------- download

    public function download(OscArquivo $arquivo): StreamedResponse
    {
        abort_unless($arquivo->podeBaixar(auth()->user()), 403);
        abort_unless(Storage::disk('local')->exists($arquivo->arquivo_path), 404, 'Arquivo não encontrado.');

        return Storage::disk('local')->download($arquivo->arquivo_path, $arquivo->arquivo_nome);
    }
}
