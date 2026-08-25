<?php

namespace App\Http\Controllers;

use App\Models\Chamamento;
use App\Models\ManifestacaoInteresse;
use App\Models\Programa;
use App\Models\Proposta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Manifestação de Interesse pelo lado do município.
 *
 * A SCP recebe, ouve a Secretaria da área — que diz se há interesse público e
 * orçamento — e decide o encaminhamento: dispensa ou inexigibilidade de
 * chamamento público, ou indeferimento.
 *
 * O deferimento não é um carimbo: é ele que cria o chamamento do tipo escolhido
 * e a proposta correspondente, levando junto o plano de trabalho e os
 * documentos que a OSC já entregou. Daí em diante corre o fluxo de sempre.
 */
class ManifestacaoAnaliseController extends Controller
{
    public function index(Request $request): View
    {
        $manifestacoes = ManifestacaoInteresse::with(['osc', 'orgao'])
            ->visiveisPara(auth()->user())
            ->where('status', '!=', 'rascunho')   // rascunho é da OSC; o município não vê
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest('submetida_em')
            ->paginate(15)
            ->withQueryString();

        return view('manifestacoes.index', compact('manifestacoes'));
    }

    public function show(ManifestacaoInteresse $manifestacao): View
    {
        $this->autorizarLeitura($manifestacao);

        $manifestacao->load(['osc', 'orgao', 'metas.etapas', 'documentos', 'parecerPor', 'decididaPor', 'chamamento', 'proposta']);

        // Só programas da Secretaria a que a manifestação se dirige: o
        // chamamento nasce dentro de um programa, e é ele que define o órgão.
        $programas = Programa::where('orgao_id', $manifestacao->orgao_id)->orderBy('name')->get();

        return view('manifestacoes.show', compact('manifestacao', 'programas'));
    }

    public function downloadDocumento(ManifestacaoInteresse $manifestacao, \App\Models\Documento $documento)
    {
        $this->autorizarLeitura($manifestacao);
        abort_unless($documento->manifestacao_id === $manifestacao->id, 404);
        abort_unless(Storage::disk('local')->exists($documento->path), 404);

        return Storage::disk('local')->download($documento->path, $documento->nome_original);
    }

    /** SCP → Secretaria: pedido de manifestação técnica. */
    public function encaminhar(ManifestacaoInteresse $manifestacao): RedirectResponse
    {
        $this->autorizarSetor($manifestacao, 'scp');
        abort_unless($manifestacao->status === 'submetida', 422,
            'Só uma manifestação recém-recebida vai à Secretaria.');

        $manifestacao->update(['status' => 'em_analise', 'setor_atual' => 'ug']);

        return back()->with('success',
            'Enviada à ' . $manifestacao->orgao->name . ' para manifestação técnica.');
    }

    /** Secretaria → SCP: opinião sobre interesse público e orçamento. */
    public function parecer(Request $request, ManifestacaoInteresse $manifestacao): RedirectResponse
    {
        $this->autorizarSetor($manifestacao, 'ug');
        abort_unless($manifestacao->status === 'em_analise', 422,
            'Esta manifestação não está em análise na Secretaria.');

        $data = $request->validate([
            'parecer_favoravel' => ['required', 'boolean'],
            'parecer_ug'        => ['required', 'string'],
        ], [
            'parecer_ug.required' => 'Escreva a manifestação técnica: é ela que fundamenta a decisão da SCP.',
        ]);

        $manifestacao->update($data + [
            'status'      => 'analisada',
            'setor_atual' => 'scp',
            'parecer_por' => auth()->id(),
            'parecer_em'  => now(),
        ]);

        return back()->with('success', 'Manifestação técnica registrada e devolvida à SCP.');
    }

    /**
     * Deferimento: escolhe o encaminhamento e faz nascer o chamamento e a
     * proposta, com o plano de trabalho e os documentos já entregues.
     */
    public function deferir(Request $request, ManifestacaoInteresse $manifestacao): RedirectResponse
    {
        $this->autorizarSetor($manifestacao, 'scp');
        abort_if($manifestacao->decidida(), 422, 'Esta manifestação já foi decidida.');
        abort_unless($manifestacao->status === 'analisada', 422,
            'Ouça a Secretaria antes de decidir — é a manifestação técnica que fundamenta o encaminhamento.');

        $data = $request->validate([
            'decisao'     => ['required', Rule::in(array_keys(ManifestacaoInteresse::ENCAMINHAMENTOS))],
            'programa_id' => ['required', Rule::exists('programas', 'id')->where('orgao_id', $manifestacao->orgao_id)],
            'numero'      => ['nullable', 'string', 'max:50'],
            'fundamento'  => ['required', 'string'],
        ], [
            'programa_id.required' => 'Escolha o programa em que a parceria será cadastrada.',
            'programa_id.exists'   => 'O programa precisa ser da mesma Secretaria da manifestação.',
            'fundamento.required'  => 'Fundamente o enquadramento (arts. 30 e 31 da Lei 13.019/2014).',
        ]);

        DB::transaction(function () use ($manifestacao, $data) {
            $chamamento = Chamamento::create([
                'programa_id'     => $data['programa_id'],
                'numero'          => $data['numero'] ?: null,
                'titulo'          => $manifestacao->titulo,
                'objeto'          => $manifestacao->objeto,
                'tipo'            => $data['decisao'],
                'valor_disponivel' => $manifestacao->valor_solicitado,
                'status'          => 'rascunho',
            ]);

            // A proposta nasce já submetida: o dossiê foi apresentado e
            // analisado aqui — pedir à OSC que reenvie seria pedir duas vezes.
            $proposta = Proposta::create([
                'chamamento_id'        => $chamamento->id,
                'osc_id'               => $manifestacao->osc_id,
                'titulo'               => $manifestacao->titulo,
                'objeto'               => $manifestacao->objeto,
                'justificativa'        => $manifestacao->justificativa,
                'valor_solicitado'     => $manifestacao->valor_solicitado,
                // A contrapartida é opcional na manifestação e obrigatória na
                // proposta: sem contrapartida declarada, é zero.
                'valor_proprio'        => $manifestacao->valor_proprio ?? 0,
                'data_inicio_prevista' => $manifestacao->data_inicio_prevista,
                'data_fim_prevista'    => $manifestacao->data_fim_prevista,
                'status'               => 'submetida',
                'submitted_at'         => $manifestacao->submetida_em ?? now(),
            ]);

            // Plano de trabalho e habilitação passam a ser da proposta — os
            // mesmos registros, sem recadastro e sem cópia a divergir.
            $manifestacao->metas()->update(['proposta_id' => $proposta->id]);
            $manifestacao->documentos()->update(['proposta_id' => $proposta->id]);

            $manifestacao->update([
                'status'         => 'deferida',
                'setor_atual'    => null,
                'decisao'        => $data['decisao'],
                'decisao_motivo' => $data['fundamento'],
                'decidida_por'   => auth()->id(),
                'decidida_em'    => now(),
                'chamamento_id'  => $chamamento->id,
                'proposta_id'    => $proposta->id,
            ]);
        });

        return redirect()->route('manifestacoes.show', $manifestacao)->with('success',
            'Deferida como ' . ManifestacaoInteresse::ENCAMINHAMENTOS[$data['decisao']]
            . '. O chamamento e a proposta foram criados com o plano de trabalho da OSC.');
    }

    public function indeferir(Request $request, ManifestacaoInteresse $manifestacao): RedirectResponse
    {
        $this->autorizarSetor($manifestacao, 'scp');
        abort_if($manifestacao->decidida(), 422, 'Esta manifestação já foi decidida.');

        $data = $request->validate([
            'decisao_motivo' => ['required', 'string'],
        ], [
            'decisao_motivo.required' => 'Informe o motivo — é o que a OSC lerá no portal.',
        ]);

        $manifestacao->update($data + [
            'status'       => 'indeferida',
            'setor_atual'  => null,
            'decisao'      => 'indeferida',
            'decidida_por' => auth()->id(),
            'decidida_em'  => now(),
        ]);

        return back()->with('success', 'Manifestação indeferida. A OSC verá o motivo no portal.');
    }

    /** Vê quem tem acesso ao módulo e ao órgão — o mesmo recorte das propostas. */
    private function autorizarLeitura(ManifestacaoInteresse $manifestacao): void
    {
        abort_if($manifestacao->ehRascunho(), 404);

        $user = auth()->user();
        abort_unless($user->podeVerTodosOrgaos() || $user->orgao_id === $manifestacao->orgao_id, 403,
            'Esta manifestação é de outra Secretaria.');
    }

    /** Age quem está com a vez: a SCP conduz, a Secretaria opina. */
    private function autorizarSetor(ManifestacaoInteresse $manifestacao, string $setor): void
    {
        $this->autorizarLeitura($manifestacao);

        abort_unless(auth()->user()->setorNoTramite() === $setor, 403,
            $setor === 'scp'
                ? 'Só o Setor de Convênios e Parcerias conduz a manifestação.'
                : 'Só a Secretaria a que a manifestação se dirige emite a manifestação técnica.');

        if ($setor === 'ug') {
            abort_unless(auth()->user()->orgao_id === $manifestacao->orgao_id, 403,
                'Esta manifestação é de outra Secretaria.');
        }
    }
}
