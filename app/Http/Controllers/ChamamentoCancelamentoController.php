<?php

namespace App\Http\Controllers;

use App\Models\Chamamento;
use App\Support\Avisos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Cancelar e reabrir o chamamento, pela UG dona, com motivo e aviso às OSCs. Cancelar não exclui
 * nada; vale até a homologação (ver Chamamento::motivoParaNaoCancelar).
 */
class ChamamentoCancelamentoController extends Controller
{
    public function cancelar(Request $request, Chamamento $chamamento): RedirectResponse
    {
        $this->autorizarUg($chamamento);

        if ($motivo = $chamamento->motivoParaNaoCancelar()) {
            return back()->withErrors(['cancelamento' => $motivo]);
        }

        $dados = $this->motivo($request, 'Informe o motivo do cancelamento — as OSCs inscritas vão lê-lo.');

        DB::transaction(function () use ($chamamento, $dados) {
            $chamamento->update(['status_antes_cancelar' => $chamamento->status, 'status' => 'cancelado']);
            $this->registrar($chamamento, 'cancelado', $dados['motivo']);
        });

        Avisos::chamamentoCancelado($chamamento, $dados['motivo']);

        return back()->with('success', 'Chamamento cancelado. Nada foi excluído: ele pode ser reaberto por aqui.');
    }

    public function reabrir(Request $request, Chamamento $chamamento): RedirectResponse
    {
        $this->autorizarUg($chamamento);
        abort_unless($chamamento->cancelado(), 422, 'Este chamamento não está cancelado.');

        $dados = $this->motivo($request, 'Informe o motivo da reabertura.');

        DB::transaction(function () use ($chamamento, $dados) {
            $chamamento->update([
                'status'                => $chamamento->status_antes_cancelar ?: 'publicado',
                'status_antes_cancelar' => null,
            ]);
            $this->registrar($chamamento, 'reaberto', $dados['motivo']);
        });

        Avisos::chamamentoReaberto($chamamento, $dados['motivo']);

        return back()->with('success', 'Chamamento reaberto: voltou a "' . Chamamento::STATUS[$chamamento->status] . '".');
    }

    private function autorizarUg(Chamamento $chamamento): void
    {
        abort_unless($chamamento->geridoPelaUg(auth()->user()), 403,
            'Só a Unidade Gestora da Secretaria dona do chamamento o cancela ou reabre.');
    }

    private function motivo(Request $request, string $mensagem): array
    {
        return $request->validate(
            ['motivo' => ['required', 'string', 'max:2000']],
            ['motivo.required' => $mensagem],
        );
    }

    private function registrar(Chamamento $chamamento, string $acao, string $motivo): void
    {
        $chamamento->cancelamentos()->create([
            'acao'       => $acao,
            'motivo'     => $motivo,
            'user_id'    => auth()->id(),
            'autor_nome' => auth()->user()->name,
        ]);
    }
}
