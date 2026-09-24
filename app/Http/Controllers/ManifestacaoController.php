<?php

namespace App\Http\Controllers;

use App\Models\ManifestacaoInteresse;
use App\Models\Orgao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Manifestação de Interesse pelo lado da OSC (portal).
 *
 * É a porta para propor uma parceria quando não há chamamento aberto: a OSC
 * monta o dossiê completo — dados, plano de trabalho e habilitação — e submete
 * à SCP, que ouve a Secretaria e decide o encaminhamento.
 *
 * Montar é da equipe; submeter é do responsável legal — a mesma régua da
 * proposta e do recurso, porque submeter vincula a entidade ao que foi
 * proposto.
 */
class ManifestacaoController extends Controller
{
    public function index(): View
    {
        $manifestacoes = ManifestacaoInteresse::with('orgao')
            ->where('osc_id', auth()->user()->osc_id)
            ->latest()
            ->get();

        return view('portal.manifestacoes.index', compact('manifestacoes'));
    }

    public function create(): View
    {
        $orgaos = Orgao::orderBy('name')->get();

        return view('portal.manifestacoes.create', compact('orgaos'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validarDados($request);

        $manifestacao = ManifestacaoInteresse::create($data + [
            'osc_id' => auth()->user()->osc_id,
            'status' => 'rascunho',
        ]);

        return redirect()->route('portal.manifestacoes.show', $manifestacao)
            ->with('success', 'Manifestação criada. Monte o plano de trabalho e anexe a habilitação para submeter.');
    }

    public function show(ManifestacaoInteresse $manifestacao): View
    {
        $this->autorizar($manifestacao);

        $manifestacao->load(['orgao', 'metas.etapas', 'planoItens', 'desembolsos', 'enderecosExecucao',
            'documentos', 'parecerPor', 'decididaPor', 'proposta']);
        $orgaos = Orgao::orderBy('name')->get();

        return view('portal.manifestacoes.show', compact('manifestacao', 'orgaos'));
    }

    /*
     * Dados do plano, metas e etapas saíram daqui.
     *
     * A OSC monta o mesmo Plano de Trabalho na manifestação e na proposta, e
     * duas implementações acabariam divergindo — foi o que aconteceu: a
     * manifestação tinha metas e a proposta não tinha plano nenhum. Agora é
     * PlanoTrabalhoController, para os dois caminhos.
     */

    public function storeDocumento(Request $request, ManifestacaoInteresse $manifestacao): RedirectResponse
    {
        $this->autorizarEdicao($manifestacao);

        $request->validate([
            'arquivo' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png', 'max:10240'],
            'tipo'    => ['required', 'string', 'in:' . implode(',', array_keys(\App\Models\Documento::TIPOS))],
        ], [
            'arquivo.max'   => 'O arquivo não pode ultrapassar 10 MB.',
            'arquivo.mimes' => 'Formatos aceitos: PDF, Word, Excel, JPG, PNG.',
        ]);

        $arquivo = $request->file('arquivo');

        $manifestacao->documentos()->create([
            'uploaded_by'   => auth()->id(),
            'nome_original' => $arquivo->getClientOriginalName(),
            'path'          => $arquivo->store('manifestacoes/' . $manifestacao->id, 'local'),
            'tipo'          => $request->tipo,
            'tamanho'       => $arquivo->getSize(),
            'mime_type'     => $arquivo->getMimeType(),
        ]);

        return back()->with('success', 'Documento anexado.');
    }

    public function destroyDocumento(ManifestacaoInteresse $manifestacao, \App\Models\Documento $documento): RedirectResponse
    {
        $this->autorizarEdicao($manifestacao);
        abort_unless($documento->manifestacao_id === $manifestacao->id, 404);

        Storage::disk('local')->delete($documento->path);
        $documento->delete();

        return back()->with('success', 'Documento removido.');
    }

    public function downloadDocumento(ManifestacaoInteresse $manifestacao, \App\Models\Documento $documento)
    {
        $this->autorizar($manifestacao);
        abort_unless($documento->manifestacao_id === $manifestacao->id, 404);
        abort_unless(Storage::disk('local')->exists($documento->path), 404);

        return Storage::disk('local')->download($documento->path, $documento->nome_original);
    }

    /**
     * Submeter é ato que vincula a entidade: fica com o responsável legal, e o
     * dossiê tem de estar completo — a Secretaria não tem como opinar sobre
     * interesse público sem plano de trabalho nem habilitação.
     */
    public function submeter(ManifestacaoInteresse $manifestacao): RedirectResponse
    {
        $this->autorizarEdicao($manifestacao);

        abort_unless(auth()->user()->ehResponsavelLegalOsc(), 403,
            'Somente o responsável legal da OSC pode submeter a manifestação.');

        $pendencias = $manifestacao->pendenciasParaSubmeter();
        abort_unless(empty($pendencias), 422,
            'Complete antes de submeter: ' . implode(', ', $pendencias) . '.');

        // Vai direto à Unidade Gestora da Secretaria escolhida (homologação,
        // item 1). Antes passava pela SCP, cuja triagem era só o clique de
        // "encaminhar à Secretaria" — a UG não sabia de nada até lá, e o
        // detalhe dizia "está com Administração" sem dizer que era a UG. A SCP
        // segue decidindo depois do parecer da UG, e pode indeferir a qualquer
        // momento. O status 'submetida' fica só para as antigas.
        $manifestacao->update([
            'status'       => 'em_analise',
            'setor_atual'  => 'ug',
            'submetida_em' => now(),
        ]);

        return redirect()->route('portal.manifestacoes.show', $manifestacao)
            ->with('success', 'Manifestação enviada à Unidade Gestora — '
                . $manifestacao->orgao->name . ', que fará a análise.');
    }

    private function validarDados(Request $request): array
    {
        return $request->validate([
            'orgao_id'             => ['required', 'exists:orgaos,id'],
            'titulo'               => ['required', 'string', 'max:255'],
            'objeto'               => ['required', 'string'],
            'justificativa'        => ['required', 'string'],
            'publico_alvo'         => ['nullable', 'string'],
            'valor_solicitado'     => ['required', 'numeric', 'min:0'],
            'valor_proprio'        => ['nullable', 'numeric', 'min:0'],
            'data_inicio_prevista' => ['nullable', 'date'],
            'data_fim_prevista'    => ['nullable', 'date', 'after_or_equal:data_inicio_prevista'],
        ], [
            'orgao_id.required'      => 'Escolha a Secretaria a que a proposta se dirige.',
            'justificativa.required' => 'A justificativa é o que sustenta o interesse público da parceria.',
        ]);
    }

    /** É da OSC do usuário? */
    private function autorizar(ManifestacaoInteresse $manifestacao): void
    {
        abort_unless($manifestacao->osc_id === auth()->user()->osc_id, 403,
            'Esta manifestação pertence a outra organização.');
    }

    /** Só se mexe enquanto é rascunho: depois de submetida, o dossiê está em análise. */
    private function autorizarEdicao(ManifestacaoInteresse $manifestacao): void
    {
        $this->autorizar($manifestacao);

        abort_unless($manifestacao->ehRascunho(), 422,
            'A manifestação já foi submetida e não pode mais ser alterada.');
    }
}
