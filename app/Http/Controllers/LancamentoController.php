<?php

namespace App\Http\Controllers;

use App\Models\Despesa;
use App\Models\Instrumento;
use App\Models\Lancamento;
use App\Models\PrestacaoContas;
use App\Support\Avisos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Painel da prestação de contas: a OSC lança despesas, extratos, evidências, folha e bens ao longo da
 * execução; a Prefeitura confere cada lançamento ou o devolve com motivo; a prestação de contas de um
 * período se monta com o que foi lançado nele (ver PrestacaoContasController::store).
 */
class LancamentoController extends Controller
{
    public const ABAS = [
        'despesa'   => 'Despesas',
        'extrato'   => 'Extratos',
        'evidencia' => 'Evidências',
        'folha'     => 'Folha',
        'bem'       => 'Bens',
    ];

    // ---------------------------------------------------------------- quem

    private function ehOscDa(Instrumento $instrumento): bool
    {
        $user = auth()->user();

        return $user->ehRepresentanteOsc() && $user->osc?->id !== null
            && $user->osc->id === $instrumento->proposta?->osc_id;
    }

    /** A Secretaria já é conferida pelo ParceriaVisivel; aqui, quem do município trata a execução. */
    private function podeConferir(): bool
    {
        $user = auth()->user();

        return $user->temAcessoInterno() && ($user->can('execucao') || $user->can('prestacao_contas'));
    }

    private function autorizarVer(Instrumento $instrumento): void
    {
        $user = auth()->user();
        abort_unless($this->ehOscDa($instrumento) || ($user->temAcessoInterno()
            && ($user->can('execucao') || $user->can('prestacao_contas') || $user->can('monitoramento'))), 403,
            'Este painel é de outra parceria.');
    }

    private function autorizarLancar(Instrumento $instrumento): void
    {
        abort_unless($this->ehOscDa($instrumento), 403, 'Quem lança no painel é a OSC da parceria.');
        abort_unless(in_array($instrumento->status, ['assinado', 'vigente', 'encerrado'], true), 422,
            'A parceria ainda não está em execução.');
    }

    private function autorizarAlterar(Despesa|Lancamento $item): void
    {
        $this->autorizarLancar($item->instrumento);
        abort_unless($item->podeSerAlteradoPelaOsc(), 422, $item->conferido()
            ? 'Lançamento já conferido pela Prefeitura: não se altera mais.'
            : 'Lançamento já enviado numa prestação de contas: não se altera mais.');
    }

    // -------------------------------------------------------------- painel

    public function painel(Request $request, Instrumento $instrumento): View
    {
        $this->autorizarVer($instrumento);
        $instrumento->load('proposta.osc', 'proposta.metas');

        $mes = $request->query('mes');
        $mes = is_string($mes) && preg_match('/^\d{4}-\d{2}$/', $mes) ? $mes : null;
        $noMes = fn ($q, string $coluna) => $mes
            ? $q->whereBetween($coluna, [$mes . '-01', date('Y-m-t', strtotime($mes . '-01'))])
            : $q;

        $travados = Despesa::periodosTravadosDe($instrumento->id);
        $despesas = $noMes($instrumento->despesas()->with('conferente', 'autor'), 'data_despesa')->get()
            ->sortByDesc('data_despesa')->values()->each->usarPeriodosTravados($travados);
        $lancamentos = $noMes($instrumento->lancamentos()->with('conferente', 'autor', 'meta'), 'competencia')->get()
            ->each->usarPeriodosTravados($travados);

        // Meses com algum lançamento, para o filtro.
        $meses = $instrumento->despesas()->pluck('data_despesa')
            ->concat($instrumento->lancamentos()->pluck('competencia'))
            ->map(fn ($d) => \Illuminate\Support\Carbon::parse($d)->format('Y-m'))
            ->unique()->sortDesc()->values();

        $todos = $instrumento->despesas()->get(['id', 'situacao'])->concat($instrumento->lancamentos()->get(['id', 'situacao']));

        return view('lancamentos.painel', [
            'instrumento'  => $instrumento,
            'ehOsc'        => $this->ehOscDa($instrumento),
            'podeConferir' => $this->podeConferir(),
            'despesas'     => $despesas,
            'lancamentos'  => $lancamentos,
            'meses'        => $meses,
            'mes'          => $mes,
            'aba'          => array_key_exists($request->query('aba'), self::ABAS) ? $request->query('aba') : 'despesa',
            'contagem'     => $todos->countBy('situacao'),
            'prestacoes'   => PrestacaoContas::where('instrumento_id', $instrumento->id)->latest('periodo_fim')->get(),
        ]);
    }

    // ------------------------------------------------------------ despesas

    private function regrasDaDespesa(bool $nova): array
    {
        return [
            'data_despesa'       => ['required', 'date'],
            'valor'              => ['required', 'numeric', 'min:0.01'],
            'natureza'           => ['required', Rule::in(array_keys(Despesa::NATUREZAS))],
            'fornecedor'         => ['nullable', 'string', 'max:255'],
            'doc_fornecedor'     => ['nullable', 'string', 'max:18'],
            'descricao'          => ['nullable', 'string', 'max:255'],
            'nota_fiscal_numero' => ['nullable', 'string', 'max:255'],
            'nota_fiscal'        => [$nova ? 'required' : 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }

    private const MENSAGENS = [
        'nota_fiscal.required' => 'Anexe a nota fiscal ou o comprovante da despesa.',
        'arquivo.required'     => 'Anexe o arquivo.',
        'arquivo.max'          => 'O arquivo não pode ultrapassar 10 MB.',
        'nota_fiscal.max'      => 'O arquivo não pode ultrapassar 10 MB.',
    ];

    public function storeDespesa(Request $request, Instrumento $instrumento): RedirectResponse
    {
        $this->autorizarLancar($instrumento);
        $dados = $request->validate($this->regrasDaDespesa(true), self::MENSAGENS);

        $despesa = $instrumento->despesas()->make(collect($dados)->except('nota_fiscal')->all() + ['lancado_por' => auth()->id()]);
        if ($erro = $this->periodoFechado($despesa)) {
            return $erro;
        }
        $despesa->nota_fiscal_path = $request->file('nota_fiscal')->store('notas-fiscais', 'local');
        $despesa->nota_fiscal_nome = $request->file('nota_fiscal')->getClientOriginalName();
        $despesa->save();

        return $this->voltar($instrumento, 'despesa', 'Despesa lançada. A Prefeitura vai conferir.');
    }

    public function updateDespesa(Request $request, Despesa $despesa): RedirectResponse
    {
        $this->autorizarAlterar($despesa);
        $dados = $request->validate($this->regrasDaDespesa(false), self::MENSAGENS);

        $despesa->fill(collect($dados)->except('nota_fiscal')->all());
        if ($erro = $this->periodoFechado($despesa)) {
            return $erro;
        }
        if ($request->hasFile('nota_fiscal')) {
            Storage::disk('local')->delete((string) $despesa->nota_fiscal_path);
            $despesa->nota_fiscal_path = $request->file('nota_fiscal')->store('notas-fiscais', 'local');
            $despesa->nota_fiscal_nome = $request->file('nota_fiscal')->getClientOriginalName();
        }
        $despesa->save();
        $despesa->voltarAConferencia();

        return $this->voltar($despesa->instrumento, 'despesa', 'Despesa corrigida. Volta para a conferência da Prefeitura.');
    }

    public function destroyDespesa(Despesa $despesa): RedirectResponse
    {
        $this->autorizarAlterar($despesa);
        Storage::disk('local')->delete((string) $despesa->nota_fiscal_path);
        $instrumento = $despesa->instrumento;
        $despesa->delete();

        return $this->voltar($instrumento, 'despesa', 'Despesa excluída.');
    }

    public function notaFiscal(Despesa $despesa): StreamedResponse
    {
        $this->autorizarVer($despesa->instrumento);
        abort_unless($despesa->temNotaFiscal() && Storage::disk('local')->exists($despesa->nota_fiscal_path), 404);

        return Storage::disk('local')->download($despesa->nota_fiscal_path, $despesa->nota_fiscal_nome);
    }

    // --------------------------------------------------------- lançamentos

    private function regras(string $tipo, bool $novo, Instrumento $instrumento): array
    {
        $arquivo = [$novo && $tipo !== 'folha' && $tipo !== 'bem' ? 'required' : 'nullable', 'file', 'max:10240'];
        $mes = ['required', 'date_format:Y-m'];

        return match ($tipo) {
            'extrato_conta', 'extrato_aplicacao' => ['competencia' => $mes, 'descricao' => ['nullable', 'string', 'max:1000'], 'arquivo' => $arquivo],
            'evidencia' => [
                'competencia' => ['required', 'date'],
                'meta_id'     => ['nullable', Rule::in($instrumento->proposta?->metas->pluck('id')->all() ?? [])],
                'descricao'   => ['required', 'string', 'max:1000'],
                'arquivo'     => $arquivo,
            ],
            'folha' => ['competencia' => $mes, 'descricao' => ['nullable', 'string', 'max:1000'], 'arquivo' => $arquivo]
                + collect(Lancamento::CAMPOS_DA_FOLHA)->map(fn ($r, $campo) => $campo === 'folha_funcionarios'
                    ? ['nullable', 'integer', 'min:0'] : ['nullable', 'numeric', 'min:0'])->all(),
            'bem' => [
                'competencia' => ['required', 'date'],
                'descricao'   => ['required', 'string', 'max:250'],
                'quantidade'  => ['required', 'numeric', 'min:0.01'],
                'valor'       => ['required', 'numeric', 'min:0'],
                'documento'   => ['nullable', 'string', 'max:60'],
                'arquivo'     => $arquivo,
            ],
        };
    }

    /** Os campos validados no formato da tabela: mês vira o dia 1, folha e documento do bem vão em `dados`. */
    private function campos(string $tipo, array $dados): array
    {
        $campos = collect($dados)->only(['competencia', 'descricao', 'meta_id', 'quantidade', 'valor'])->all();

        if (in_array($tipo, ['extrato_conta', 'extrato_aplicacao', 'folha'], true)) {
            $campos['competencia'] = $dados['competencia'] . '-01';
        }
        if ($tipo === 'folha') {
            $campos['dados'] = collect($dados)->only(array_keys(Lancamento::CAMPOS_DA_FOLHA))->map(fn ($v) => $v ?? 0)->all();
        }
        if ($tipo === 'bem') {
            $campos['dados'] = ['documento' => $dados['documento'] ?? null];
        }

        return $campos;
    }

    private function guardarArquivo(Request $request, Lancamento $l): void
    {
        if (!$request->hasFile('arquivo')) {
            return;
        }

        Storage::disk('local')->delete((string) $l->arquivo_path);
        $f = $request->file('arquivo');
        $l->fill(['arquivo_path' => $f->store('lancamentos/' . $l->instrumento_id, 'local'),
            'arquivo_nome' => $f->getClientOriginalName(), 'mime_type' => $f->getMimeType(), 'tamanho' => $f->getSize()]);
    }

    public function store(Request $request, Instrumento $instrumento): RedirectResponse
    {
        $this->autorizarLancar($instrumento);
        $instrumento->load('proposta.metas');

        $tipo = $request->validate(['tipo' => ['required', Rule::in(array_keys(Lancamento::TIPOS))]])['tipo'];
        $dados = $request->validate($this->regras($tipo, true, $instrumento), self::MENSAGENS);

        $l = new Lancamento($this->campos($tipo, $dados) + ['instrumento_id' => $instrumento->id, 'tipo' => $tipo, 'lancado_por' => auth()->id()]);
        if ($erro = $this->periodoFechado($l)) {
            return $erro;
        }
        $this->guardarArquivo($request, $l);
        $l->save();

        return $this->voltar($instrumento, $this->abaDo($tipo), $l->rotuloDoTipo() . ' lançado. A Prefeitura vai conferir.');
    }

    public function update(Request $request, Lancamento $lancamento): RedirectResponse
    {
        $this->autorizarAlterar($lancamento);
        $instrumento = $lancamento->instrumento->load('proposta.metas');

        $dados = $request->validate($this->regras($lancamento->tipo, false, $instrumento), self::MENSAGENS);
        $lancamento->fill($this->campos($lancamento->tipo, $dados));
        if ($erro = $this->periodoFechado($lancamento)) {
            return $erro;
        }
        $this->guardarArquivo($request, $lancamento);
        $lancamento->save();
        $lancamento->voltarAConferencia();

        return $this->voltar($instrumento, $this->abaDo($lancamento->tipo), 'Lançamento corrigido. Volta para a conferência da Prefeitura.');
    }

    public function destroy(Lancamento $lancamento): RedirectResponse
    {
        $this->autorizarAlterar($lancamento);
        Storage::disk('local')->delete((string) $lancamento->arquivo_path);
        $instrumento = $lancamento->instrumento;
        $aba = $this->abaDo($lancamento->tipo);
        $lancamento->delete();

        return $this->voltar($instrumento, $aba, 'Lançamento excluído.');
    }

    public function arquivo(Lancamento $lancamento): StreamedResponse
    {
        $this->autorizarVer($lancamento->instrumento);
        abort_unless($lancamento->temArquivo() && Storage::disk('local')->exists($lancamento->arquivo_path), 404);

        return Storage::disk('local')->download($lancamento->arquivo_path, $lancamento->arquivo_nome);
    }

    // ---------------------------------------------------------- conferência

    public function conferirDespesa(Request $request, Despesa $despesa): RedirectResponse
    {
        return $this->conferirItem($request, $despesa, 'despesa');
    }

    public function conferir(Request $request, Lancamento $lancamento): RedirectResponse
    {
        return $this->conferirItem($request, $lancamento, $this->abaDo($lancamento->tipo));
    }

    private function conferirItem(Request $request, Despesa|Lancamento $item, string $aba): RedirectResponse
    {
        abort_unless($this->podeConferir(), 403, 'Quem confere é a Prefeitura.');

        $dados = $request->validate([
            'acao'   => ['required', Rule::in(['conferir', 'devolver'])],
            'motivo' => ['required_if:acao,devolver', 'nullable', 'string', 'max:1000'],
        ], ['motivo.required_if' => 'Diga o que está errado — é o que a OSC vai ler para corrigir.']);

        // Já numa prestação enviada, a OSC não o altera: conferir pode, devolver não — o que estiver
        // errado volta com a devolução da prestação à OSC, que destrava o período.
        abort_if($dados['acao'] === 'devolver' && $item->travadoPorPrestacao(), 422,
            'Lançamento já enviado numa prestação de contas: para corrigi-lo, devolva a prestação à OSC.');

        if ($dados['acao'] === 'conferir') {
            $item->conferir(auth()->user());
        } else {
            $item->devolver(auth()->user(), $dados['motivo']);
            Avisos::lancamentoDevolvido($item);
        }

        return $this->voltar($item->instrumento, $aba, $dados['acao'] === 'conferir' ? 'Lançamento conferido.' : 'Lançamento devolvido à OSC.');
    }

    /** Confere de uma vez os que estão a conferir na aba. */
    public function conferirTodos(Request $request, Instrumento $instrumento): RedirectResponse
    {
        abort_unless($this->podeConferir(), 403, 'Quem confere é a Prefeitura.');
        $aba = $request->validate(['aba' => ['required', Rule::in(array_keys(self::ABAS))]])['aba'];

        $itens = $aba === 'despesa'
            ? $instrumento->despesas()->where('situacao', 'pendente')->get()
            : $instrumento->lancamentos()->where('situacao', 'pendente')->whereIn('tipo', $this->tiposDa($aba))->get();
        $itens->each->conferir(auth()->user());

        return $this->voltar($instrumento, $aba, $itens->count() . ' lançamento(s) conferido(s).');
    }

    // ---------------------------------------------------------------- apoio

    /**
     * Data num período cuja prestação já saiu da OSC: o lançamento nasceria travado, sem conferência
     * possível, e seguraria o encerramento daquela prestação.
     */
    private function periodoFechado(Despesa|Lancamento $item): ?RedirectResponse
    {
        if (!$item->travadoPorPrestacao()) {
            return null;
        }

        return back()->withInput()->withErrors(['periodo' => 'A data cai num período cuja prestação de contas já foi enviada. '
            . 'Lance com a data certa num período aberto, ou espere a prestação voltar à OSC.']);
    }

    private function abaDo(string $tipo): string
    {
        return str_starts_with($tipo, 'extrato') ? 'extrato' : $tipo;
    }

    private function tiposDa(string $aba): array
    {
        return $aba === 'extrato' ? ['extrato_conta', 'extrato_aplicacao'] : [$aba];
    }

    private function voltar(Instrumento $instrumento, string $aba, string $mensagem): RedirectResponse
    {
        return redirect()->route('lancamentos.painel', ['instrumento' => $instrumento, 'aba' => $aba])->with('success', $mensagem);
    }
}
