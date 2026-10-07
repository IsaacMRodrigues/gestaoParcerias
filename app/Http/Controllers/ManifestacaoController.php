<?php

namespace App\Http\Controllers;

use App\Models\ManifestacaoInteresse;
use App\Models\Orgao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Manifestação de Interesse e Nova Proposta pelo lado da OSC (portal). Montar é da equipe;
 * submeter, do responsável legal.
 */
class ManifestacaoController extends Controller
{
    public function index(): View
    {
        return $this->listar('manifestacao');
    }

    /** Nova Proposta: o mesmo conteúdo da manifestação, outro caminho (ver ManifestacaoInteresse::TIPOS). */
    public function indexPropostas(): View
    {
        return $this->listar('proposta');
    }

    private function listar(string $tipo): View
    {
        $manifestacoes = ManifestacaoInteresse::with('orgao')
            ->where('osc_id', auth()->user()->osc_id)
            ->doTipo($tipo)
            ->latest()
            ->get();

        return view('portal.manifestacoes.index', compact('manifestacoes', 'tipo'));
    }

    public function create(): View
    {
        $orgaos = Orgao::orderBy('name')->get();
        $tipo   = 'manifestacao';

        return view('portal.manifestacoes.create', compact('orgaos', 'tipo'));
    }

    public function createProposta(): View
    {
        $orgaos = collect();
        $tipo   = 'proposta';

        return view('portal.manifestacoes.create', compact('orgaos', 'tipo'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validarDados($request);

        $manifestacao = ManifestacaoInteresse::create($data + [
            'tipo'   => 'manifestacao',
            'osc_id' => auth()->user()->osc_id,
            'status' => 'rascunho',
        ]);

        return redirect()->route('portal.manifestacoes.show', $manifestacao)
            ->with('success', 'Manifestação criada. Monte o plano de trabalho e anexe a habilitação para submeter.');
    }

    /** A Secretaria não se escolhe aqui: quem a define é a SCP, ao encaminhar. */
    public function storeProposta(Request $request): RedirectResponse
    {
        $data = $this->validarDados($request, proposta: true) + $request->validate(
            ['plano_aplicacao' => ['required', 'string', 'max:1000']],
            ['plano_aplicacao.required' => 'Descreva o plano de aplicação dos recursos.'],
        );

        // O valor pleiteado e a planilha de itens se lançam no plano de trabalho, na tela seguinte.
        $manifestacao = ManifestacaoInteresse::create($data + [
            'tipo'             => 'proposta',
            'osc_id'           => auth()->user()->osc_id,
            'status'           => 'rascunho',
            'valor_solicitado' => 0,
        ]);

        return redirect()->route('portal.manifestacoes.show', $manifestacao)
            ->with('success', 'Proposta criada. Monte o plano de trabalho e anexe a habilitação para enviar.');
    }

    public function show(ManifestacaoInteresse $manifestacao): View
    {
        $this->autorizar($manifestacao);

        $manifestacao->load(['orgao', 'metas.etapas', 'planoItens', 'desembolsos', 'contrapartidas', 'equipe',
            'documentos', 'parecerPor', 'decididaPor', 'proposta']);
        $orgaos = Orgao::orderBy('name')->get();

        return view('portal.manifestacoes.show', compact('manifestacao', 'orgaos'));
    }

    public function storeDocumento(Request $request, ManifestacaoInteresse $manifestacao): RedirectResponse
    {
        $this->autorizarEdicao($manifestacao);

        $request->validate([
            'arquivo' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png', 'max:10240'],
            // Estatuto, ata e certidões estão em "Arquivos da OSC".
            'tipo'    => ['required', 'string', 'in:' . implode(',', array_keys(\App\Models\Documento::tiposParaAnexar()))],
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

    /** Submeter: só o responsável legal, com o dossiê completo. */
    public function submeter(ManifestacaoInteresse $manifestacao): RedirectResponse
    {
        $this->autorizarEdicao($manifestacao);

        abort_unless(auth()->user()->ehResponsavelLegalOsc(), 403,
            'Somente o responsável legal da OSC pode submeter a manifestação.');

        $pendencias = $manifestacao->pendenciasParaSubmeter();
        abort_unless(empty($pendencias), 422,
            'Complete antes de submeter: ' . implode(', ', $pendencias) . '.');

        // Nova Proposta: vai primeiro à SCP, que escolhe a Unidade Gestora que
        // a atende (ManifestacaoAnaliseController::encaminhar).
        if ($manifestacao->ehNovaProposta()) {
            $manifestacao->enviar(['status' => 'submetida', 'setor_atual' => 'scp']);

            return redirect()->route('portal.manifestacoes.show', $manifestacao)
                ->with('success', 'Proposta enviada — protocolo nº ' . $manifestacao->protocolo
                    . '. O Setor de Convênios e Parcerias a encaminhará à Unidade Gestora adequada.');
        }

        // Vai direto à UG da Secretaria escolhida; a SCP decide depois do parecer dela.
        $manifestacao->enviar(['status' => 'em_analise', 'setor_atual' => 'ug']);

        return redirect()->route('portal.manifestacoes.show', $manifestacao)
            ->with('success', 'Manifestação enviada — protocolo nº ' . $manifestacao->protocolo
                . '. A Unidade Gestora — ' . $manifestacao->orgao->name . ' — fará a análise.');
    }

    private function validarDados(Request $request, bool $proposta = false): array
    {
        return $request->validate([
            // Na Nova Proposta a SCP escolhe a Secretaria e o fundamento ao
            // encaminhar; a OSC não informa nenhum dos dois.
            'orgao_id'             => $proposta ? ['exclude'] : ['required', 'exists:orgaos,id'],
            'fundamento_pedido'    => ['exclude'],
            'titulo'               => ['required', 'string', 'max:255'],
            'objeto'               => ['required', 'string', 'max:1000'],
            'justificativa'        => ['required', 'string', 'max:1000'],
            'publico_alvo'         => ['nullable', 'string', 'max:1000'],
            // Na Nova Proposta o valor tem validação própria, com a planilha do
            // plano de aplicação: ver validarValoresDaProposta.
            'valor_solicitado'     => $proposta ? ['exclude'] : ['required', 'numeric', 'min:0'],
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
