<?php

namespace App\Http\Controllers;

use App\Models\Despesa;
use App\Models\ManifestacaoInteresse;
use App\Models\Proposta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * O Plano de Trabalho da OSC — o mesmo, nos dois caminhos.
 *
 * A OSC monta plano na manifestação de interesse (quando propõe sem chamamento)
 * e na proposta (quando se inscreve num chamamento aberto). É o mesmo formulário
 * e as mesmas regras, então é o mesmo controller: o tipo do dono vem da rota,
 * por `defaults('tipo', ...)`, e não há duas implementações a divergir.
 *
 * Quem pode mexer: a equipe da OSC dona, e só enquanto o plano é dela — em
 * rascunho, ou na etapa da Celebração em que o município devolveu o plano para
 * ela elaborar. Depois disso o plano é peça de processo.
 */
class PlanoTrabalhoController extends Controller
{
    /**
     * Declara as rotas do plano para um dono.
     *
     * Fica aqui, e não no arquivo de rotas, porque a lista precisa ser
     * idêntica nos dois caminhos: acrescentar uma rota num e esquecer o outro
     * daria uma OSC que monta o plano na manifestação e não na proposta.
     * O tipo viaja como valor fixo da rota e é lido em donoEditavel().
     */
    public static function rotas(string $tipo, string $prefixo, string $nome): void
    {
        $r = fn (string $metodo, string $verbo, string $caminho, string $sufixo) => \Illuminate\Support\Facades\Route::{$verbo}(
            $prefixo . $caminho, [self::class, $metodo]
        )->defaults('tipo', $tipo)->name($nome . '.' . $sufixo);

        $r('atualizar',        'put',    '',                                  'atualizar');
        $r('storeMeta',        'post',   '/metas',                            'metas.store');
        $r('destroyMeta',      'delete', '/metas/{meta}',                     'metas.destroy');
        $r('storeEtapa',       'post',   '/metas/{meta}/etapas',              'etapas.store');
        $r('destroyEtapa',     'delete', '/metas/{meta}/etapas/{etapa}',      'etapas.destroy');
        $r('storeItem',        'post',   '/itens',                            'itens.store');
        $r('destroyItem',      'delete', '/itens/{item}',                     'itens.destroy');
        $r('storeDesembolso',  'post',   '/desembolsos',                      'desembolsos.store');
        $r('destroyDesembolso', 'delete', '/desembolsos/{desembolso}',        'desembolsos.destroy');
        $r('storeEndereco',    'post',   '/enderecos',                        'enderecos.store');
        $r('destroyEndereco',  'delete', '/enderecos/{endereco}',             'enderecos.destroy');
    }

    // ----------------------------------------------------------------- dados

    public function atualizar(Request $request): RedirectResponse
    {
        $dono = $this->donoEditavel($request);

        $data = $request->validate([
            'titulo'               => ['required', 'string', 'max:255'],
            'objeto'               => ['required', 'string'],
            'descricao_realidade'  => ['nullable', 'string'],
            'justificativa'        => ['nullable', 'string'],
            'publico_alvo'         => ['nullable', 'string'],
            'objetivos'            => ['nullable', 'string'],
            'valor_solicitado'     => ['required', 'numeric', 'min:0'],
            'valor_proprio'        => ['nullable', 'numeric', 'min:0'],
            'valor_outras_fontes'  => ['nullable', 'numeric', 'min:0'],
            'data_inicio_prevista' => ['nullable', 'date'],
            'data_fim_prevista'    => ['nullable', 'date', 'after_or_equal:data_inicio_prevista'],
            'vigencia_dias'        => ['nullable', 'integer', 'min:1', 'max:3650'],
            'atuacao_rede'         => ['nullable', 'boolean'],
            'rede_cnpj'            => ['nullable', 'string', 'max:18', 'required_if:atuacao_rede,1'],
            'rede_razao_social'    => ['nullable', 'string', 'max:255', 'required_if:atuacao_rede,1'],
            'rede_municipio'       => ['nullable', 'string', 'max:255'],
            'rede_data_termo'      => ['nullable', 'date'],
        ], [
            'rede_cnpj.required_if'         => 'Informe o CNPJ da organização executante em rede.',
            'rede_razao_social.required_if' => 'Informe a razão social da organização executante em rede.',
        ]);

        $data['atuacao_rede']        = (bool) ($data['atuacao_rede'] ?? false);
        $data['valor_proprio']       = $data['valor_proprio'] ?? 0;
        $data['valor_outras_fontes'] = $data['valor_outras_fontes'] ?? 0;

        // Rede desmarcada não deixa rastro: os campos seguiriam num plano que
        // declara não haver rede, e o documento gerado os imprimiria.
        if (!$data['atuacao_rede']) {
            $data['rede_cnpj'] = $data['rede_razao_social'] = $data['rede_municipio'] = $data['rede_data_termo'] = null;
        }

        $dono->update($data);

        return back()->with('success', 'Plano de trabalho atualizado.');
    }

    // ----------------------------------------------------------------- metas

    public function storeMeta(Request $request): RedirectResponse
    {
        $dono = $this->donoEditavel($request);

        $dono->criarMeta($request->validate([
            'descricao'            => ['required', 'string', 'max:255'],
            'atividades'           => ['nullable', 'string'],
            'indicador'            => ['nullable', 'string', 'max:255'],
            'meios_verificacao'    => ['nullable', 'string'],
            'resultados_esperados' => ['nullable', 'string'],
            'valor'                => ['nullable', 'numeric', 'min:0'],
            'meta_quantitativa'    => ['nullable', 'string', 'max:255'],
            'data_inicio'          => ['nullable', 'date'],
            'data_fim'             => ['nullable', 'date', 'after_or_equal:data_inicio'],
        ]));

        return back()->with('success', 'Meta adicionada.');
    }

    public function destroyMeta(Request $request): RedirectResponse
    {
        $dono = $this->donoEditavel($request);

        $dono->metas()->findOrFail($request->route('meta'))->delete();

        return back()->with('success', 'Meta removida.');
    }

    public function storeEtapa(Request $request): RedirectResponse
    {
        $dono = $this->donoEditavel($request);
        $meta = $dono->metas()->findOrFail($request->route('meta'));

        $meta->etapas()->create($request->validate([
            'descricao'   => ['required', 'string', 'max:255'],
            'responsavel' => ['nullable', 'string', 'max:255'],
            'data_inicio' => ['nullable', 'date'],
            'data_fim'    => ['nullable', 'date', 'after_or_equal:data_inicio'],
        ]) + ['numero' => (int) $meta->etapas()->max('numero') + 1]);

        return back()->with('success', 'Etapa adicionada.');
    }

    public function destroyEtapa(Request $request): RedirectResponse
    {
        $dono = $this->donoEditavel($request);
        $meta = $dono->metas()->findOrFail($request->route('meta'));

        $meta->etapas()->findOrFail($request->route('etapa'))->delete();

        return back()->with('success', 'Etapa removida.');
    }

    // ------------------------------------------------- plano de aplicação

    public function storeItem(Request $request): RedirectResponse
    {
        $dono = $this->donoEditavel($request);

        $dados = $request->validate([
            'descricao'             => ['required', 'string', 'max:255'],
            'tipo_despesa'          => ['required', Rule::in(array_keys(Despesa::NATUREZAS))],
            'unidade'               => ['nullable', 'string', 'max:30'],
            'quantidade'            => ['required', 'numeric', 'min:0.01'],
            'valor_unitario'        => ['required', 'numeric', 'min:0'],
            'atividades_vinculadas' => ['nullable', 'string', 'max:255'],
        ]);

        $dono->planoItens()->create($dados + ['numero' => $dono->proximoNumero('planoItens')]);

        return back()->with('success', 'Item incluído no plano de aplicação.');
    }

    public function destroyItem(Request $request): RedirectResponse
    {
        $dono = $this->donoEditavel($request);

        $dono->planoItens()->findOrFail($request->route('item'))->delete();

        return back()->with('success', 'Item removido.');
    }

    // ------------------------------------------- cronograma de desembolso

    public function storeDesembolso(Request $request): RedirectResponse
    {
        $dono = $this->donoEditavel($request);

        $dados = $request->validate([
            'ano'   => ['required', 'integer', 'min:2020', 'max:2100'],
            'mes'   => ['required', 'integer', 'min:1', 'max:12'],
            'valor' => ['required', 'numeric', 'min:0.01'],
        ]);

        // Duas parcelas no mesmo mês são sempre erro de digitação — o
        // cronograma tem uma linha por mês, e a planilha do parecer soma por
        // mês. Somar no lugar de recusar evita perder o que foi digitado.
        $existente = $dono->desembolsos()->where('ano', $dados['ano'])->where('mes', $dados['mes'])->first();
        if ($existente) {
            $existente->update(['valor' => (float) $existente->valor + (float) $dados['valor']]);

            return back()->with('success', 'Já havia parcela neste mês — os valores foram somados.');
        }

        $dono->desembolsos()->create($dados);

        return back()->with('success', 'Parcela incluída no cronograma de desembolso.');
    }

    public function destroyDesembolso(Request $request): RedirectResponse
    {
        $dono = $this->donoEditavel($request);

        $dono->desembolsos()->findOrFail($request->route('desembolso'))->delete();

        return back()->with('success', 'Parcela removida.');
    }

    // -------------------------------------------------------- endereços

    public function storeEndereco(Request $request): RedirectResponse
    {
        $dono = $this->donoEditavel($request);

        $dono->enderecosExecucao()->create($request->validate([
            'descricao' => ['nullable', 'string', 'max:255'],
            'endereco'  => ['required', 'string', 'max:255'],
        ]));

        return back()->with('success', 'Endereço de execução incluído.');
    }

    public function destroyEndereco(Request $request): RedirectResponse
    {
        $dono = $this->donoEditavel($request);

        $dono->enderecosExecucao()->findOrFail($request->route('endereco'))->delete();

        return back()->with('success', 'Endereço removido.');
    }

    // --------------------------------------------------------------- apoio

    /**
     * O dono do plano, já confirmado como da OSC logada e ainda editável.
     *
     * Tipo e id saem da rota pelo nome, e não da assinatura do método: o
     * Laravel entrega os parâmetros de rota por posição, e o `tipo` — que é
     * valor fixo da rota, não trecho da URL — entra por último. Lido por
     * posição, o método recebia o id no lugar do tipo.
     */
    private function donoEditavel(Request $request): Proposta|ManifestacaoInteresse
    {
        $tipo = (string) $request->route('tipo');
        $id   = (string) $request->route('id');

        $dono = $tipo === 'proposta'
            ? Proposta::findOrFail($id)
            : ManifestacaoInteresse::findOrFail($id);

        $osc = auth()->user()->oscVinculada();
        abort_unless($osc && $dono->osc_id === $osc->id, 403);

        abort_unless(self::planoEditavel($dono), 403,
            'Este plano de trabalho não está mais em elaboração pela organização.');

        return $dono;
    }

    /**
     * O plano ainda é da OSC?
     *
     * Na manifestação, só no rascunho. Na proposta, enquanto não foi submetida;
     * outra vez na Celebração, quando o município convoca a OSC a elaborar o
     * plano definitivo (etapa 2 do fluxo, `celebracao_setor` = osc); e outra
     * ainda na execução, enquanto houver pedido de alteração em elaboração —
     * é o próprio plano que a alteração altera (módulo 3.3).
     */
    public static function planoEditavel(Proposta|ManifestacaoInteresse $dono): bool
    {
        if ($dono instanceof ManifestacaoInteresse) {
            return $dono->ehRascunho();
        }

        if ($dono->status === 'rascunho') {
            return true;
        }

        if ($dono->temTramiteCelebracao()
            && !$dono->celebracaoConcluida()
            && $dono->celebracao_setor === 'osc') {
            return true;
        }

        return \App\Models\Alteracao::where('status', 'rascunho')
            ->whereHas('instrumento', fn ($q) => $q->where('proposta_id', $dono->id))
            ->exists();
    }
}
