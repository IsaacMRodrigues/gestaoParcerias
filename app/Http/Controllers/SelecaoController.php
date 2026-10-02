<?php

namespace App\Http\Controllers;

use App\Models\Chamamento;
use App\Models\Peca;
use App\Support\Avisos;
use App\Support\Devolucao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Trâmite da Seleção do Chamamento Público (etapas em Chamamento::ETAPAS_SELECAO). */
class SelecaoController extends Controller
{
    /** Só o setor que está com a Seleção pode movimentá-la. */
    private function autorizarSetor(Chamamento $chamamento): void
    {
        abort_unless($chamamento->temTramiteSelecao(), 422,
            'Dispensa/Inexigibilidade não passa por julgamento de propostas.');
        abort_if($chamamento->selecaoConcluida(), 422, 'A Seleção deste chamamento já foi encerrada.');
        abort_if($chamamento->cancelado(), 422, 'Este chamamento está cancelado: a Seleção só anda depois de reaberto.');
        abort_unless(auth()->user()->setor === $chamamento->selecao_setor, 403,
            'Apenas o setor que está com a Seleção pode movimentá-la.');
    }

    /** Encaminha a Seleção para o próximo setor do fluxo. */
    public function avancar(Request $request, Chamamento $chamamento): RedirectResponse
    {
        $this->autorizarSetor($chamamento);
        abort_unless($chamamento->podeAvancarSelecao(), 422,
            'Não é possível encaminhar a partir desta etapa.');

        $pendentes = $chamamento->pendenciasSelecao();
        abort_unless(empty($pendentes), 422,
            'Conclua antes de encaminhar: ' . implode(', ', $pendentes) . '.');

        $proxEtapa = (int) $chamamento->selecao_etapa + 1;
        $abrePrazo = $proxEtapa === Chamamento::ETAPA_PRAZO_RECURSO;

        // Ao publicar o Resultado Provisório, a SCP informa até quando cabe
        // recurso: o prazo é o do edital, e varia de um para outro.
        $data = $request->validate([
            'parecer'           => ['nullable', 'string'],
            'prazo_recurso_ate' => [$abrePrazo ? 'required' : 'nullable', 'date', 'after_or_equal:today'],
        ], [
            'prazo_recurso_ate.required'       => 'Informe o último dia do prazo de recurso previsto no edital.',
            'prazo_recurso_ate.after_or_equal' => 'O prazo de recurso não pode terminar antes de hoje.',
        ]);

        $proxSetor = Chamamento::ETAPAS_SELECAO[$proxEtapa]['setor'];

        $chamamento->selecaoTramitacoes()->create([
            'de_setor'    => $chamamento->selecao_setor,
            'para_setor'  => $proxSetor,
            'enviado_por' => auth()->id(),
            'enviado_em'  => now(),
            'parecer'     => $data['parecer'] ?? null,
            'status'      => 'enviado',
        ]);

        $chamamento->update(array_merge([
            'selecao_etapa' => $proxEtapa,
            'selecao_setor' => $proxSetor,
            'status'        => 'em_analise',
        ], $abrePrazo ? ['prazo_recurso_ate' => $data['prazo_recurso_ate']] : []));

        if ($abrePrazo) {
            Avisos::prazoDeRecursoAberto($chamamento);
        }

        return redirect()->route('chamamentos.selecao', $chamamento)
            ->with('success', 'Seleção encaminhada para ' . Chamamento::SETORES_SELECAO[$proxSetor] . '.');
    }

    /** Devolve para uma etapa anterior (pendência a corrigir). */
    public function devolver(Request $request, Chamamento $chamamento): RedirectResponse
    {
        $this->autorizarSetor($chamamento);
        abort_if((int) $chamamento->selecao_etapa === 0, 422, 'Não há etapa anterior para devolver.');

        $data = $request->validate(['parecer' => ['required', 'string']], [
            'parecer.required' => 'Informe o motivo da devolução.',
        ]);

        // Devolução por documento: só os marcados reabrem, e a
        // Seleção volta para a etapa do mais antigo deles.
        $escolhidas    = Devolucao::escolhidas($request, $chamamento->documentosDevolviveis());
        $etapaAnterior = Devolucao::etapaDestino($escolhidas, (int) $chamamento->selecao_etapa - 1);
        $setorAnterior = Chamamento::ETAPAS_SELECAO[$etapaAnterior]['setor'];

        $chamamento->selecaoTramitacoes()->create([
            'de_setor'    => $chamamento->selecao_setor,
            'para_setor'  => $setorAnterior,
            'enviado_por' => auth()->id(),
            'enviado_em'  => now(),
            'parecer'     => Devolucao::reabrir($escolhidas, $data['parecer'], auth()->user()),
            'status'      => 'devolvido',
        ]);

        $chamamento->update([
            'selecao_etapa' => $etapaAnterior,
            'selecao_setor' => $setorAnterior,
        ]);

        return redirect()->route('chamamentos.selecao', $chamamento)
            ->with('success', 'Seleção devolvida para ' . Chamamento::SETORES_SELECAO[$setorAnterior] . '.');
    }

    /**
     * Abre mais um espaço de anexo (republicação, errata…). Na etapa corrente, é da vez de quem
     * o cria; nos documentos gerais, grava-se o setor de quem abriu, e só ele preenche.
     */
    public function adicionarAnexo(Request $request, Chamamento $chamamento): RedirectResponse
    {
        $data = $request->validate([
            'rotulo' => ['required', 'string', 'max:120'],
            'escopo' => ['required', Rule::in(['etapa', 'geral'])],
        ], [
            'rotulo.required' => 'Dê um nome ao anexo (ex.: "Publicação — 2ª edição").',
        ]);

        $naEtapa = $data['escopo'] === 'etapa';

        if ($naEtapa) {
            $this->autorizarSetor($chamamento);
        }

        $etapa = $naEtapa ? (int) $chamamento->selecao_etapa : null;

        // Fora da etapa, segue os vizinhos: no chamamento as peças prévias têm dono; na Dispensa, não.
        $setor = match (true) {
            $naEtapa => $chamamento->selecao_setor,
            $chamamento->categoriaPecas() === 'chamamento_publico' => auth()->user()->setorNoTramite(),
            default  => null,
        };

        // Entra no fim do bloco onde nasce: a maior ordem de lá, que o desempate
        // por id resolve entre anexos criados na mesma etapa.
        $pecas = $chamamento->pecas()->get();
        $ordem = $pecas->filter(fn (Peca $p) => $p->selecaoEtapa() === $etapa)->max('ordem')
            ?? $pecas->max('ordem')
            ?? 0;

        $peca = $chamamento->pecas()->create([
            'categoria'   => $chamamento->categoriaPecas(),
            'chave'       => 'extra_' . Str::uuid()->toString(),
            'rotulo'      => $data['rotulo'],
            'tipo'        => 'arquivo',
            'obrigatorio' => false,
            'ordem'       => $ordem,
            'extra'       => true,
            'setor'       => $setor,
            'etapa'       => $etapa,
            'criado_por'  => auth()->id(),
        ]);

        return back()->withFragment('peca-' . $peca->id)
            ->with('success', 'Espaço de anexo criado. Envie o arquivo abaixo.');
    }

    /** Propostas ainda sem decisão neste chamamento (nem aprovadas, reprovadas ou rascunho). */
    private function propostasEmJulgamento(Chamamento $chamamento)
    {
        return $chamamento->propostas()
            ->whereIn('status', ['submetida', 'em_analise'])
            ->with('osc')
            ->get();
    }

    /**
     * Adjudica: as vencedoras são aprovadas e seguem para a Celebração; as demais são reprovadas
     * no mesmo ato, porque o resultado do julgamento é um só.
     */
    private function adjudicarPropostas(Chamamento $chamamento, array $vencedoras): void
    {
        foreach ($this->propostasEmJulgamento($chamamento) as $proposta) {
            $proposta->update([
                'status' => in_array($proposta->id, $vencedoras) ? 'aprovada' : 'reprovada',
            ]);
        }
    }

    /** Regras da declaração de vencedoras, comuns ao encerramento e à declaração posterior. */
    private function validarVencedoras(Request $request, Chamamento $chamamento): array
    {
        $candidatas = $this->propostasEmJulgamento($chamamento);

        if ($candidatas->isEmpty()) {
            return [];   // chamamento deserto ou já julgado
        }

        $data = $request->validate([
            // Ao menos uma: chamamento fracassado se resolve reprovando as propostas uma a uma.
            'vencedoras'   => ['required', 'array', 'min:1'],
            'vencedoras.*' => ['integer', Rule::in($candidatas->pluck('id')->all())],
        ], [
            'vencedoras.required' => 'Selecione a(s) proposta(s) vencedora(s) para adjudicar.',
            'vencedoras.*.in'     => 'Proposta que não está em julgamento neste chamamento.',
        ]);

        return $data['vencedoras'];
    }

    /** Declara as vencedoras de um chamamento já encerrado (homologado antes de a adjudicação existir). */
    public function adjudicar(Request $request, Chamamento $chamamento): RedirectResponse
    {
        abort_unless($chamamento->temTramiteSelecao(), 422,
            'Dispensa/Inexigibilidade não passa por julgamento de propostas.');
        abort_unless($chamamento->selecaoConcluida(), 422,
            'A Seleção ainda não foi encerrada — a adjudicação acontece no encerramento.');
        abort_unless(auth()->user()->setor === 'ug', 403,
            'Apenas a Unidade Gestora declara o resultado do julgamento.');

        $vencedoras = $this->validarVencedoras($request, $chamamento);
        abort_if(empty($vencedoras), 422, 'Não há propostas em julgamento neste chamamento.');

        $this->adjudicarPropostas($chamamento, $vencedoras);

        return back()->with('success', count($vencedoras) === 1
            ? 'Proposta adjudicada. A Celebração já pode ser iniciada.'
            : count($vencedoras).' propostas adjudicadas. A Celebração já pode ser iniciada.');
    }

    /** Encerra a Seleção na última etapa e devolve o chamamento à UG para a Celebração. */
    public function concluir(Request $request, Chamamento $chamamento): RedirectResponse
    {
        $this->autorizarSetor($chamamento);
        abort_unless($chamamento->ultimaEtapaSelecao(), 422,
            'A Seleção só pode ser encerrada na última etapa.');

        $pendentes = $chamamento->pendenciasSelecao();
        abort_unless(empty($pendentes), 422,
            'Conclua antes de encerrar: ' . implode(', ', $pendentes) . '.');

        // Homologar sem dizer quem venceu era o que deixava a parceria sem
        // continuidade — ver adjudicarPropostas().
        $vencedoras = $this->validarVencedoras($request, $chamamento);

        $chamamento->selecaoTramitacoes()->create([
            'de_setor'    => $chamamento->selecao_setor,
            'para_setor'  => 'ug',
            'enviado_por' => auth()->id(),
            'enviado_em'  => now(),
            'status'      => 'concluido',
        ]);

        $chamamento->update([
            'selecao_setor'        => 'ug',
            'selecao_concluida_em' => now(),
            'status'               => 'encerrado',
            'data_resultado'       => $chamamento->data_resultado ?: now()->toDateString(),
        ]);

        $this->adjudicarPropostas($chamamento, $vencedoras);

        return redirect()->route('chamamentos.selecao', $chamamento)
            ->with('success', $vencedoras
                ? 'Seleção encerrada e homologada. A Celebração já pode ser iniciada.'
                : 'Seleção encerrada e homologada (sem propostas em julgamento).');
    }
}
