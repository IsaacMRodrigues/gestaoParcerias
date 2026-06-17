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
        $orgaos = Orgao::where('status', true)->orderBy('name')->get();

        return view('processos.create', compact('orgaos'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'orgao_id' => ['required', 'exists:orgaos,id'],
        ]);

        $processo = Processo::create([
            'numero'      => Processo::proximoNumero(),
            'orgao_id'    => $data['orgao_id'],
            'created_by'  => auth()->id(),
            'status'      => 'em_planejamento',
            'setor_atual' => 'ug',
        ]);

        // cria o termo de referência vazio e as peças padrão
        $processo->termoReferencia()->create([]);
        foreach (array_keys(\App\Models\ProcessoPeca::TIPOS) as $tipo) {
            $processo->pecas()->create(['tipo' => $tipo]);
        }

        return redirect()->route('processos.show', $processo)
            ->with('success', 'Processo aberto. Preencha o Termo de Referência e as demais peças.');
    }

    public function show(Processo $processo): View
    {
        $processo->load([
            'orgao', 'criador', 'termoReferencia.assinante',
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
