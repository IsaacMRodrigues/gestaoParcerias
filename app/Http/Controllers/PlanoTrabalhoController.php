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
 * O Plano de Trabalho da OSC, o mesmo na manifestação e na proposta (o tipo do dono vem da
 * rota). A OSC mexe enquanto o plano é dela; na Celebração, a UG também (ugPodeEditar).
 */
class PlanoTrabalhoController extends Controller
{
    /** Declara as rotas do plano para um dono: a lista é idêntica nos dois caminhos. */
    public static function rotas(string $tipo, string $prefixo, string $nome): void
    {
        $r = fn (string $metodo, string $verbo, string $caminho, string $sufixo) => \Illuminate\Support\Facades\Route::{$verbo}(
            $prefixo . $caminho, [self::class, $metodo]
        )->defaults('tipo', $tipo)->name($nome . '.' . $sufixo);

        $r('atualizar',        'put',    '',                                  'atualizar');
        $r('atualizarAplicacao', 'put',  '/aplicacao',                        'aplicacao');
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
            'objeto'                => ['required', 'string', 'max:1000'],
            'publico_alvo'          => ['nullable', 'string', 'max:1000'],
            'vigencia_dias'         => ['nullable', 'integer', 'min:1', 'max:3650'],
            'data_inicio_prevista'  => ['nullable', 'date'],
            'data_fim_prevista'     => ['nullable', 'date', 'after_or_equal:data_inicio_prevista'],
            'valor_solicitado'      => ['required', 'numeric', 'min:0'],
            'descricao_realidade'   => ['nullable', 'string', 'max:1000'],
            'objetivos'             => ['nullable', 'string', 'max:1000'],
            'objetivos_especificos' => ['nullable', 'string', 'max:1000'],
            'metodologia'           => ['nullable', 'string', 'max:1000'],
            'justificativa'         => ['nullable', 'string', 'max:1000'],
        ]);

        $dono->update($data);

        return back()->with('success', 'Plano de trabalho atualizado.');
    }

    /** O texto do plano de aplicação dos recursos (item 13), à parte da planilha de itens. */
    public function atualizarAplicacao(Request $request): RedirectResponse
    {
        $this->donoEditavel($request)->update($request->validate([
            'plano_aplicacao' => ['nullable', 'string', 'max:1000'],
        ]));

        return back()->with('success', 'Plano de aplicação atualizado.');
    }

    // ----------------------------------------------------------------- metas

    public function storeMeta(Request $request): RedirectResponse
    {
        $dono = $this->donoEditavel($request);

        // Item 7 do modelo: objetivo específico, meta, indicadores
        // (qualitativos e quantitativos), resultados esperados e meios de
        // verificação. As atividades são lançadas na própria meta.
        $dono->criarMeta($request->validate([
            'objetivo_especifico'  => ['nullable', 'string', 'max:1000'],
            'descricao'            => ['required', 'string', 'max:1000'],
            'indicador'            => ['nullable', 'string', 'max:1000'],
            'meta_quantitativa'    => ['nullable', 'string', 'max:1000'],
            'resultados_esperados' => ['nullable', 'string', 'max:1000'],
            'meios_verificacao'    => ['nullable', 'string', 'max:1000'],
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
            'descricao'   => ['required', 'string', 'max:1000'],
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
            'descricao'             => ['required', 'string', 'max:1000'],
            'tipo_despesa'          => ['required', Rule::in(array_keys(Despesa::NATUREZAS))],
            'unidade'               => ['nullable', 'string', 'max:30'],
            'quantidade'            => ['required', 'numeric', 'min:0.01'],
            'valor_unitario'        => ['required', 'numeric', 'min:0'],
            'atividades_vinculadas' => ['nullable', 'string', 'max:1000'],
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
            'descricao'  => ['required', 'string', 'max:1000'],
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
            'cargo_funcao'         => ['required', 'string', 'max:1000'],
            'formacao'             => ['nullable', 'string', 'max:1000'],
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
     * O dono do plano, da OSC logada e ainda editável. Tipo e id saem da rota pelo nome
     * (o tipo é valor fixo da rota e viria fora de posição).
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

    /** A UG edita o plano na Celebração: formalização, da Secretaria da proposta, até o plano ser assinado. */
    public static function ugPodeEditar(Proposta $proposta, ?User $user): bool
    {
        return $user !== null
            && $user->setorNoTramite() === 'ug'
            && $user->can('formalizacao')
            && $proposta->visivelPara($user)
            && $proposta->planoAbertoNaCelebracao();
    }

    /**
     * O plano ainda é da OSC? Na manifestação, em rascunho; na proposta, até submeter, na Celebração
     * até o plano ser assinado e na execução com alteração em elaboração.
     */
    public static function planoEditavel(Proposta|ManifestacaoInteresse $dono): bool
    {
        if ($dono instanceof ManifestacaoInteresse) {
            return $dono->ehRascunho();
        }

        if ($dono->status === 'rascunho') {
            return true;
        }

        // Na Celebração, em qualquer etapa, até o documento do plano ser assinado.
        if ($dono->planoAbertoNaCelebracao()) {
            return true;
        }

        return \App\Models\Alteracao::where('status', 'rascunho')
            ->whereHas('instrumento', fn ($q) => $q->where('proposta_id', $dono->id))
            ->exists();
    }
}
