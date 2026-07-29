<?php

namespace App\Http\Controllers;

use App\Models\Chamamento;
use App\Models\Processo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TramitacaoController extends Controller
{
    /**
     * Garante que o usuário logado é do setor que está com o processo agora.
     */
    private function autorizarSetor(Processo $processo): void
    {
        abort_unless($processo->visivelPara(auth()->user()), 403,
            'Este processo pertence a outra Secretaria.');
        abort_unless(auth()->user()->setor === $processo->setor_atual, 403,
            'Apenas o setor que está com o processo pode movimentá-lo.');
    }

    /**
     * O setor de destino registra o recebimento antes de atuar.
     */
    public function receber(Processo $processo): RedirectResponse
    {
        $this->autorizarSetor($processo);

        $atual = $processo->tramitacaoAtual();
        abort_unless($atual, 404);

        $atual->update([
            'recebido_por' => auth()->id(),
            'recebido_em'  => now(),
            'status'       => 'recebido',
        ]);

        return back()->with('success', 'Recebimento registrado.');
    }

    /**
     * Avança para a próxima etapa do fluxo (envia para o próximo setor).
     */
    public function avancar(Request $request, Processo $processo): RedirectResponse
    {
        $this->autorizarSetor($processo);

        abort_unless($processo->podeAvancar(), 422, 'Não é possível avançar a partir desta etapa.');

        // recebimento pendente precisa ser registrado antes
        $atual = $processo->tramitacaoAtual();
        abort_if($atual && is_null($atual->recebido_em), 422, 'Registre o recebimento antes de encaminhar.');

        // peças obrigatórias da etapa precisam estar assinadas
        $pendentes = $processo->pendenciasParaAvancar();
        abort_unless(empty($pendentes), 422,
            'Assine antes de encaminhar: ' . implode(', ', $pendentes) . '.');

        // Na etapa de análise (SCP), o setor define a modalidade da seleção ao aprovar.
        $ehAnalise = $processo->etapaEhAnalise();

        $rules = ['parecer' => ['nullable', 'string']];
        if ($ehAnalise) {
            $rules['modalidade'] = ['required', \Illuminate\Validation\Rule::in(array_keys(Processo::MODALIDADES))];
        }

        $data = $request->validate($rules, [
            'modalidade.required' => 'Selecione a modalidade (Chamamento Público, Dispensa ou Inexigibilidade) antes de aprovar.',
            'modalidade.in'       => 'Modalidade inválida.',
        ]);

        // A modalidade decidida na análise já resolve a rota da(s) próxima(s) etapa(s).
        if ($ehAnalise && !empty($data['modalidade'])) {
            $processo->modalidade = $data['modalidade'];
        }

        $proxEtapa = $processo->etapa + 1;
        $proxSetor = $processo->etapas()[$proxEtapa]['setor'];

        $processo->tramitacoes()->create([
            'de_setor'    => $processo->setor_atual,
            'para_setor'  => $proxSetor,
            'enviado_por' => auth()->id(),
            'enviado_em'  => now(),
            'parecer'     => $data['parecer'] ?? null,
            'status'      => 'enviado',
        ]);

        $update = [
            'etapa'       => $proxEtapa,
            'setor_atual' => $proxSetor,
            'status'      => 'em_tramite',
        ];
        if ($ehAnalise) {
            $update['modalidade'] = $data['modalidade'];
        }

        $processo->update($update);

        return redirect()->route('processos.show', $processo)
            ->with('success', 'Processo encaminhado para ' . Processo::SETORES[$proxSetor] . '.');
    }

    /**
     * Devolve para a etapa anterior (quando há pendência a corrigir).
     */
    public function devolver(Request $request, Processo $processo): RedirectResponse
    {
        $this->autorizarSetor($processo);
        abort_if($processo->etapa === 0, 422, 'Não há etapa anterior para devolver.');

        $data = $request->validate(['parecer' => ['required', 'string']], [
            'parecer.required' => 'Informe o motivo da devolução.',
        ]);

        $etapaAnterior = $processo->etapa - 1;
        $setorAnterior = $processo->etapas()[$etapaAnterior]['setor'];

        $processo->tramitacoes()->create([
            'de_setor'    => $processo->setor_atual,
            'para_setor'  => $setorAnterior,
            'enviado_por' => auth()->id(),
            'enviado_em'  => now(),
            'parecer'     => $data['parecer'],
            'status'      => 'devolvido',
        ]);

        $processo->update([
            'etapa'       => $etapaAnterior,
            'setor_atual' => $setorAnterior,
            'status'      => 'em_tramite',
        ]);

        return redirect()->route('processos.show', $processo)
            ->with('success', 'Processo devolvido para ' . Processo::SETORES[$setorAnterior] . '.');
    }

    /**
     * Conclui o trâmite na última etapa (SCP publica — trâmite externo).
     */
    public function concluir(Processo $processo): RedirectResponse
    {
        $this->autorizarSetor($processo);
        abort_unless($processo->ultimaEtapa(), 422, 'O processo ainda não chegou à etapa final.');

        $atual = $processo->tramitacaoAtual();
        abort_if($atual && is_null($atual->recebido_em), 422, 'Registre o recebimento antes de concluir.');

        $processo->update(['status' => 'concluido']);

        $chamamento = $processo->gerarChamamentoPublicacao();

        return redirect()->route('processos.show', $processo)
            ->with('success', 'Processo concluído e publicado como '
                . (Chamamento::TIPOS[$chamamento->tipo] ?? 'chamamento')
                . ' no programa "' . $chamamento->programa->name . '".');
    }

    /**
     * Gera o Chamamento para um processo já concluído que ainda não tenha publicação.
     */
    public function publicar(Request $request, Processo $processo): RedirectResponse
    {
        abort_unless($processo->visivelPara(auth()->user()), 403, 'Este processo pertence a outra Secretaria.');
        abort_unless($processo->status === 'concluido', 422, 'O processo precisa estar concluído.');
        abort_unless(!$processo->chamamento, 422, 'Este processo já possui chamamento publicado.');

        // Se a modalidade não foi definida no trâmite (ex.: processos antigos),
        // permite escolhê-la aqui antes de gerar a publicação.
        if (! $processo->modalidade) {
            $data = $request->validate([
                'modalidade' => ['required', Rule::in(array_keys(Processo::MODALIDADES))],
            ]);
            $processo->update(['modalidade' => $data['modalidade']]);
        }

        // Processo já concluído: qualquer usuário com planejamento pode gerar a publicação
        // (não exige mais estar no setor atual).
        $chamamento = $processo->gerarChamamentoPublicacao();

        return redirect()->route('processos.show', $processo)
            ->with('success', 'Chamamento gerado: ' . $chamamento->titulo);
    }
}
