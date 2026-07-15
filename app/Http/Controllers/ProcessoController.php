<?php

namespace App\Http\Controllers;

use App\Models\Orgao;
use App\Models\Processo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProcessoController extends Controller
{
    public function index(): View
    {
        $processos = Processo::with(['orgao', 'criador'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('processos.index', compact('processos'));
    }

    /**
     * Caixa de entrada do setor do usuário logado.
     */
    public function caixa(): View
    {
        $setor = auth()->user()->setor;
        abort_unless($setor, 403, 'Seu usuário não está vinculado a nenhum setor.');

        $processos = Processo::with(['orgao', 'criador'])
            ->where('setor_atual', $setor)
            ->where('status', 'em_tramite')
            ->orderByDesc('updated_at')
            ->paginate(15);

        return view('processos.caixa', compact('processos', 'setor'));
    }

    public function create(): View
    {
        // UG do próprio usuário (preenchida automaticamente, se cadastrada)
        $orgaoUsuario = auth()->user()->orgao;

        // Fallback (ex.: admin sem UG): permite escolher entre as UGs com código.
        $orgaos = Orgao::where('status', true)
            ->whereNotNull('codigo')
            ->orderBy('codigo')
            ->get();

        $esferas = Processo::ESFERAS;

        return view('processos.create', compact('orgaos', 'esferas', 'orgaoUsuario'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'esfera' => ['required', 'string', \Illuminate\Validation\Rule::in(array_keys(Processo::ESFERAS))],
        ]);

        // UG vem do cadastro do usuário; só usa a seleção manual se ele não tiver UG vinculada.
        $orgao = auth()->user()->orgao;
        if (! $orgao) {
            $request->validate(['orgao_id' => ['required', 'exists:orgaos,id']]);
            $orgao = Orgao::findOrFail($request->orgao_id);
        }

        if (! $orgao->codigo) {
            return back()
                ->withErrors(['orgao_id' => 'Esta Unidade Gestora não possui código cadastrado; informe o código no cadastro do Órgão.'])
                ->withInput();
        }

        $sequencial = Processo::proximoSequencial();

        $processo = Processo::create([
            'numero'      => Processo::formatarNumero($orgao->codigo, $sequencial, now()->year, $data['esfera']),
            'sequencial'  => $sequencial,
            'esfera'      => $data['esfera'],
            'orgao_id'    => $orgao->id,
            'created_by'  => auth()->id(),
            'status'      => 'em_planejamento',
            'setor_atual' => 'ug',
            'etapa'       => 0,
        ]);

        // cria as peças padrão (modelo já preenchido com os dados do processo)
        foreach (array_keys(\App\Models\ProcessoPeca::TIPOS) as $tipo) {
            $processo->pecas()->create([
                'tipo'     => $tipo,
                'conteudo' => \App\Models\ProcessoPeca::conteudoInicial($tipo, $processo),
            ]);
        }

        return redirect()->route('processos.show', $processo)
            ->with('success', 'Processo aberto. Preencha o Termo de Referência e as demais peças.');
    }

    public function show(Processo $processo): View
    {
        $processo->load([
            'orgao', 'criador', 'chamamento.programa',
            'pecas.assinante', 'tramitacoes.remetente', 'tramitacoes.recebedor',
        ]);

        return view('processos.show', compact('processo'));
    }

    public function destroy(Processo $processo): RedirectResponse
    {
        $processo->delete();

        return redirect()->route('processos.index')
            ->with('success', 'Processo removido.');
    }
}
