<?php

namespace App\Http\Controllers;

use App\Models\Despesa;
use App\Models\ManifestacaoInteresse;
use App\Models\Orgao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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
        return $this->listar('manifestacao');
    }

    /**
     * Nova Proposta (28/09/2026): mesmo conteúdo da manifestação, outro
     * caminho — ver ManifestacaoInteresse::TIPOS. Listagem, criação e envio
     * próprios; o resto (dados, plano, documentos) é a mesma tela.
     */
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
        $data = $this->validarDados($request, proposta: true);
        [$valores, $itens] = $this->validarValoresDaProposta($request);

        $manifestacao = DB::transaction(function () use ($data, $valores, $itens) {
            $manifestacao = ManifestacaoInteresse::create($data + $valores + [
                'tipo'   => 'proposta',
                'osc_id' => auth()->user()->osc_id,
                'status' => 'rascunho',
            ]);

            foreach (array_values($itens) as $n => $item) {
                $manifestacao->planoItens()->create($item + ['numero' => $n + 1]);
            }

            return $manifestacao;
        });

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

        // Nova Proposta: vai primeiro à SCP, que escolhe a Unidade Gestora que
        // a atende (ManifestacaoAnaliseController::encaminhar).
        if ($manifestacao->ehNovaProposta()) {
            $manifestacao->update(['status' => 'submetida', 'setor_atual' => 'scp', 'submetida_em' => now()]);

            return redirect()->route('portal.manifestacoes.show', $manifestacao)
                ->with('success', 'Proposta enviada ao Setor de Convênios e Parcerias, que a encaminhará à Unidade Gestora adequada.');
        }

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

    /**
     * O valor pleiteado (item 2 do modelo de Plano de Trabalho) e a planilha
     * do plano de aplicação (item 13), que a Nova Proposta já traz no primeiro
     * formulário (decisão da gestão, 29/09/2026). O cronograma de desembolso,
     * por meta, fica para a tela seguinte, onde as metas são lançadas. A
     * planilha pode ficar vazia aqui — o envio é que exige o plano completo.
     */
    private function validarValoresDaProposta(Request $request): array
    {
        $dados = $request->validate([
            'valor_solicitado'              => ['required', 'numeric', 'min:0'],
            'itens'                         => ['nullable', 'array', 'max:200'],
            'itens.*.descricao'             => ['required', 'string', 'max:255'],
            'itens.*.tipo_despesa'          => ['required', Rule::in(array_keys(Despesa::NATUREZAS))],
            'itens.*.unidade'               => ['nullable', 'string', 'max:30'],
            'itens.*.quantidade'            => ['required', 'numeric', 'min:0.01'],
            'itens.*.valor_unitario'        => ['required', 'numeric', 'min:0'],
            'itens.*.atividades_vinculadas' => ['nullable', 'string', 'max:255'],
        ], [
            'valor_solicitado.required'       => 'Informe o valor pleiteado.',
            'itens.*.descricao.required'      => 'Descreva cada item do plano de aplicação.',
            'itens.*.valor_unitario.required' => 'Informe o valor unitário de cada item.',
        ]);

        return [['valor_solicitado' => $dados['valor_solicitado']], $dados['itens'] ?? []];
    }

    private function validarDados(Request $request, bool $proposta = false): array
    {
        return $request->validate([
            // Na Nova Proposta a SCP escolhe a Secretaria e o fundamento ao
            // encaminhar; a OSC não informa nenhum dos dois.
            'orgao_id'             => $proposta ? ['exclude'] : ['required', 'exists:orgaos,id'],
            'fundamento_pedido'    => ['exclude'],
            'titulo'               => ['required', 'string', 'max:255'],
            'objeto'               => ['required', 'string'],
            'justificativa'        => ['required', 'string'],
            'publico_alvo'         => ['nullable', 'string'],
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
