<?php

namespace App\Http\Controllers;

use App\Models\Despesa;
use App\Models\ManifestacaoInteresse;
use App\Models\PlanoEquipe;
use App\Models\User;
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
        $r('storeContrapartida',   'post',   '/contrapartidas',               'contrapartidas.store');
        $r('destroyContrapartida', 'delete', '/contrapartidas/{contrapartida}', 'contrapartidas.destroy');
        $r('storeEquipe',          'post',   '/equipe',                       'equipe.store');
        $r('destroyEquipe',        'delete', '/equipe/{membro}',              'equipe.destroy');
    }

    // ----------------------------------------------------------------- dados

    public function atualizar(Request $request): RedirectResponse
    {
        $dono = $this->donoEditavel($request);

        // Os campos dos itens 2 a 6 do modelo de Plano de Trabalho da cliente.
        // Contrapartida em dinheiro, outras fontes e atuação em rede não
        // constam do modelo: saíram da tela, e o que já estava gravado fica.
        $data = $request->validate([
            'titulo'                => ['required', 'string', 'max:255'],
            'objeto'                => ['required', 'string'],
            'publico_alvo'          => ['nullable', 'string'],
            'vigencia_dias'         => ['nullable', 'integer', 'min:1', 'max:3650'],
            'data_inicio_prevista'  => ['nullable', 'date'],
            'data_fim_prevista'     => ['nullable', 'date', 'after_or_equal:data_inicio_prevista'],
            'valor_solicitado'      => ['required', 'numeric', 'min:0'],
            'descricao_realidade'   => ['nullable', 'string'],
            'objetivos'             => ['nullable', 'string'],
            'objetivos_especificos' => ['nullable', 'string'],
            'metodologia'           => ['nullable', 'string'],
            'justificativa'         => ['nullable', 'string'],
        ]);

        $dono->update($data);

        return back()->with('success', 'Plano de trabalho atualizado.');
    }

    // ----------------------------------------------------------------- metas

    public function storeMeta(Request $request): RedirectResponse
    {
        $dono = $this->donoEditavel($request);

        // Item 7 do modelo: objetivo específico, meta, indicadores
        // (qualitativos e quantitativos), resultados esperados e meios de
        // verificação. As atividades são lançadas na própria meta.
        $dono->criarMeta($request->validate([
            'objetivo_especifico'  => ['nullable', 'string'],
            'descricao'            => ['required', 'string', 'max:255'],
            'indicador'            => ['nullable', 'string', 'max:255'],
            'meta_quantitativa'    => ['nullable', 'string', 'max:255'],
            'resultados_esperados' => ['nullable', 'string'],
            'meios_verificacao'    => ['nullable', 'string'],
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

        // Atividade da meta, com o período e o estimado do item 10 do modelo
        // (cronograma de execução física e financeira).
        $meta->etapas()->create($request->validate([
            'descricao'   => ['required', 'string', 'max:255'],
            'data_inicio' => ['nullable', 'date'],
            'data_fim'    => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'valor'       => ['nullable', 'numeric', 'min:0'],
        ]) + ['numero' => (int) $meta->etapas()->max('numero') + 1]);

        return back()->with('success', 'Atividade adicionada.');
    }

    public function destroyEtapa(Request $request): RedirectResponse
    {
        $dono = $this->donoEditavel($request);
        $meta = $dono->metas()->findOrFail($request->route('meta'));

        $meta->etapas()->findOrFail($request->route('etapa'))->delete();

        return back()->with('success', 'Atividade removida.');
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

        // Item 11 do modelo: por meta e parcela.
        $dados = $request->validate([
            'meta_id' => ['required', Rule::exists('metas', 'id')->where($dono->chavePlano(), $dono->id)],
            'parcela' => ['required', 'integer', 'min:1', 'max:120'],
            'valor'   => ['required', 'numeric', 'min:0.01'],
        ], [
            'meta_id.required' => 'Escolha a meta da parcela.',
            'meta_id.exists'   => 'Escolha uma meta deste plano.',
        ]);

        // A mesma parcela da mesma meta duas vezes é erro de digitação — o
        // cronograma tem uma célula por meta e parcela. Somar no lugar de
        // recusar evita perder o que foi digitado.
        $existente = $dono->desembolsos()->where('meta_id', $dados['meta_id'])->where('parcela', $dados['parcela'])->first();
        if ($existente) {
            $existente->update(['valor' => (float) $existente->valor + (float) $dados['valor']]);

            return back()->with('success', 'Já havia esta parcela nesta meta — os valores foram somados.');
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

    // ----------------------------------- contrapartida não financeira (8)

    public function storeContrapartida(Request $request): RedirectResponse
    {
        $dono = $this->donoEditavel($request);

        $dono->contrapartidas()->create($request->validate([
            'descricao'  => ['required', 'string', 'max:255'],
            'quantidade' => ['nullable', 'string', 'max:100'],
        ]) + ['numero' => $dono->proximoNumero('contrapartidas')]);

        return back()->with('success', 'Contrapartida incluída.');
    }

    public function destroyContrapartida(Request $request): RedirectResponse
    {
        $dono = $this->donoEditavel($request);

        $dono->contrapartidas()->findOrFail($request->route('contrapartida'))->delete();

        return back()->with('success', 'Contrapartida removida.');
    }

    // ------------------------------------------------------- equipe (12)

    public function storeEquipe(Request $request): RedirectResponse
    {
        $dono = $this->donoEditavel($request);

        $dono->equipe()->create($request->validate([
            'cargo_funcao'         => ['required', 'string', 'max:255'],
            'formacao'             => ['nullable', 'string', 'max:255'],
            'carga_horaria_mensal' => ['nullable', 'string', 'max:50'],
            'vinculo'              => ['required', Rule::in(array_keys(PlanoEquipe::VINCULOS))],
        ]));

        return back()->with('success', 'Integrante da equipe incluído.');
    }

    public function destroyEquipe(Request $request): RedirectResponse
    {
        $dono = $this->donoEditavel($request);

        $dono->equipe()->findOrFail($request->route('membro'))->delete();

        return back()->with('success', 'Integrante removido.');
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

        // A UG, na tela interna da proposta, durante a Celebração.
        if ($tipo === 'celebracao') {
            $proposta = Proposta::findOrFail($id);
            abort_unless(self::ugPodeEditar($proposta, auth()->user()), 403,
                'O plano só se edita na Celebração, pela Unidade Gestora da Secretaria, até o documento "Plano de Trabalho" ser assinado.');

            return $proposta;
        }

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
     * A UG edita o plano na Celebração (decisão da gestão, 30/09/2026): quem
     * trabalha na formalização, da Secretaria da proposta, enquanto o
     * documento do plano não foi assinado. A permissão de formalização deixa
     * de fora a Comissão de Seleção, que também é da UG.
     */
    public static function ugPodeEditar(Proposta $proposta, ?User $user): bool
    {
        return $user !== null
            && $user->setorNoTramite() === 'ug'
            && $user->can('formalizacao')
            && $proposta->visivelPara($user)
            && $proposta->planoAbertoNaCelebracao();
    }

    /**
     * O plano ainda é da OSC?
     *
     * Na manifestação, só no rascunho. Na proposta, enquanto não foi submetida;
     * outra vez na Celebração, em qualquer etapa, até o documento "Plano de
     * Trabalho" ser assinado; e outra ainda na execução, enquanto houver pedido
     * de alteração em elaboração — é o próprio plano que a alteração altera
     * (módulo 3.3).
     */
    public static function planoEditavel(Proposta|ManifestacaoInteresse $dono): bool
    {
        if ($dono instanceof ManifestacaoInteresse) {
            return $dono->ehRascunho();
        }

        if ($dono->status === 'rascunho') {
            return true;
        }

        // Na Celebração, em qualquer etapa, até o documento do plano ser
        // assinado (decisão da gestão, 30/09/2026). Antes só na etapa da OSC.
        if ($dono->planoAbertoNaCelebracao()) {
            return true;
        }

        return \App\Models\Alteracao::where('status', 'rascunho')
            ->whereHas('instrumento', fn ($q) => $q->where('proposta_id', $dono->id))
            ->exists();
    }
}
