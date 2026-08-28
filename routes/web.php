<?php

use App\Http\Controllers\AditivoController;
use App\Http\Controllers\BuscaController;
use App\Http\Controllers\CaixaController;
use App\Http\Controllers\CelebracaoController;
use App\Http\Controllers\ChamamentoController;
use App\Http\Controllers\SelecaoController;
use App\Http\Controllers\DiligenciaController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\ExecucaoController;
use App\Http\Controllers\EtapaController;
use App\Http\Controllers\InstrumentoController;
use App\Http\Controllers\MetaController;
use App\Http\Controllers\ModeloController;
use App\Http\Controllers\OrdemPagamentoController;
use App\Http\Controllers\OrgaoController;
use App\Http\Controllers\OscController;
use App\Http\Controllers\OscRegistroController;
use App\Http\Controllers\ParecerController;
use App\Http\Controllers\PecaController;
use App\Http\Controllers\OscUsuarioController;
use App\Http\Controllers\ManifestacaoAnaliseController;
use App\Http\Controllers\ManifestacaoController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\ProcessoController;
use App\Http\Controllers\ProcessoPecaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgramaController;
use App\Http\Controllers\RecursoController;
use App\Http\Controllers\PropostaController;
use App\Http\Controllers\TramitacaoController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Tela principal: escolha do perfil de acesso (Prefeitura, OSC, Cidadão,
// Parlamentar e Conselho). É o "início do site" — o logotipo aponta para cá em
// todas as telas, inclusive para quem já está logado, por isso a raiz sempre
// renderiza a landing. O destino de quem acabou de entrar continua sendo
// definido no AuthenticatedSessionController, não aqui.
Route::get('/', fn () => view('landing'))->name('landing');

// Portal público
Route::get('/portal', [PortalController::class, 'index'])->name('portal.index');
Route::get('/portal/chamamentos/{chamamento}', [PortalController::class, 'chamamento'])->name('portal.chamamento');
Route::get('/transparencia', [PortalController::class, 'transparencia'])->name('transparencia');

// Auto-cadastro de OSC — só para visitante. O store abre uma conta nova e faz
// login nela; se um servidor logado chegasse aqui, sairia da própria conta e
// passaria a existir como OSC, que é exatamente o que não pode acontecer.
Route::middleware('guest')->group(function () {
    Route::get('/cadastro/osc', [OscRegistroController::class, 'create'])->name('portal.osc.create');
    Route::post('/cadastro/osc', [OscRegistroController::class, 'store'])->name('portal.osc.store');
});

// Validação pública de documentos assinados
Route::get('/validar', [\App\Http\Controllers\ValidacaoController::class, 'index'])->name('validacao.index');
Route::post('/validar', [\App\Http\Controllers\ValidacaoController::class, 'verificar'])->name('validacao.verificar');
Route::get('/validar/{codigo}', [\App\Http\Controllers\ValidacaoController::class, 'mostrar'])->name('validacao.mostrar');

// Área da OSC logada (portal) — 'osc' barra o usuário interno: servidor analisa
// e decide sobre a proposta, nunca a apresenta.
Route::middleware(['auth', 'osc'])->group(function () {
    /*
     * Ver é de toda a equipe da OSC; agir é de quem tem a função marcada.
     *
     * A régua está aqui, na rota, e não espalhada nos controllers: o
     * responsável legal marca as funções no cadastro do integrante (ver
     * User::FUNCOES_OSC) e cada grupo abaixo diz qual delas abre o quê.
     */
    Route::get('/portal/minhas-propostas', [PortalController::class, 'minhasPropostas'])->name('portal.minhas-propostas');
    Route::get('/portal/propostas/{proposta}', [PortalController::class, 'showProposta'])->name('portal.proposta.show');
    Route::patch('/portal/propostas/{proposta}/submeter', [PortalController::class, 'submeterProposta'])->name('portal.proposta.submeter');

    Route::middleware('permission:osc_propostas')->group(function () {
        Route::get('/portal/chamamentos/{chamamento}/participar', [PortalController::class, 'participar'])->name('portal.participar');
        Route::post('/portal/chamamentos/{chamamento}/proposta', [PortalController::class, 'storeProposta'])->name('portal.proposta.store');
    });

    // Manifestação de Interesse: propor parceria sem chamamento aberto.
    // Montar é da equipe da OSC; submeter, do responsável legal (no controller).
    Route::middleware('permission:osc_manifestacoes')->group(function () {
        Route::get('/portal/manifestacoes/nova', [ManifestacaoController::class, 'create'])->name('portal.manifestacoes.create');
        Route::post('/portal/manifestacoes', [ManifestacaoController::class, 'store'])->name('portal.manifestacoes.store');
        Route::put('/portal/manifestacoes/{manifestacao}', [ManifestacaoController::class, 'update'])->name('portal.manifestacoes.update');
        Route::post('/portal/manifestacoes/{manifestacao}/metas', [ManifestacaoController::class, 'storeMeta'])->name('portal.manifestacoes.metas.store');
        Route::delete('/portal/manifestacoes/{manifestacao}/metas/{meta}', [ManifestacaoController::class, 'destroyMeta'])->name('portal.manifestacoes.metas.destroy');
        Route::post('/portal/manifestacoes/{manifestacao}/metas/{meta}/etapas', [ManifestacaoController::class, 'storeEtapa'])->name('portal.manifestacoes.etapas.store');
        Route::delete('/portal/manifestacoes/{manifestacao}/metas/{meta}/etapas/{etapa}', [ManifestacaoController::class, 'destroyEtapa'])->name('portal.manifestacoes.etapas.destroy');
    });

    Route::get('/portal/manifestacoes', [ManifestacaoController::class, 'index'])->name('portal.manifestacoes.index');
    Route::get('/portal/manifestacoes/{manifestacao}', [ManifestacaoController::class, 'show'])->name('portal.manifestacoes.show');
    Route::get('/portal/manifestacoes/{manifestacao}/documentos/{documento}', [ManifestacaoController::class, 'downloadDocumento'])->name('portal.manifestacoes.documentos.download');
    Route::patch('/portal/manifestacoes/{manifestacao}/submeter', [ManifestacaoController::class, 'submeter'])->name('portal.manifestacoes.submeter');

    // O documento da manifestação é documento da organização: mesma função que
    // governa os anexos da proposta, não a de montar a manifestação.
    Route::middleware('permission:osc_documentos')->group(function () {
        Route::post('/portal/manifestacoes/{manifestacao}/documentos', [ManifestacaoController::class, 'storeDocumento'])->name('portal.manifestacoes.documentos.store');
        Route::delete('/portal/manifestacoes/{manifestacao}/documentos/{documento}', [ManifestacaoController::class, 'destroyDocumento'])->name('portal.manifestacoes.documentos.destroy');
    });

    // Recurso contra o resultado provisório (protocolo eletrônico pela OSC)
    Route::post('/portal/chamamentos/{chamamento}/recurso', [RecursoController::class, 'store'])->name('recursos.store');

    // Equipe da OSC — o responsável legal cadastra as contas da organização.
    // O controller confere a titularidade (oscs.user_id); o papel aqui é a
    // primeira barreira, para o membro comum nem ver a rota.
    Route::middleware('role:responsavel_legal')->group(function () {
        Route::get('/portal/usuarios', [OscUsuarioController::class, 'index'])->name('portal.usuarios.index');
        Route::get('/portal/usuarios/novo', [OscUsuarioController::class, 'create'])->name('portal.usuarios.create');
        Route::post('/portal/usuarios', [OscUsuarioController::class, 'store'])->name('portal.usuarios.store');
        Route::patch('/portal/usuarios/{usuario}/funcoes', [OscUsuarioController::class, 'funcoes'])->name('portal.usuarios.funcoes');
        Route::patch('/portal/usuarios/{usuario}/acesso', [OscUsuarioController::class, 'alternarAcesso'])->name('portal.usuarios.acesso');
    });
});

// Recursos: download pela OSC autora ou pela equipe; resposta pela Unidade Gestora
Route::middleware('auth')->group(function () {
    Route::get('recursos/{recurso}/arquivo', [RecursoController::class, 'download'])->name('recursos.download');
    Route::post('recursos/{recurso}/responder', [RecursoController::class, 'responder'])->name('recursos.responder');
});

// Documentos (funciona para admin e portal via back())
Route::middleware('auth')->group(function () {
    Route::post('propostas/{proposta}/documentos', [DocumentoController::class, 'store'])->name('documentos.store');
    Route::delete('propostas/{proposta}/documentos/{documento}', [DocumentoController::class, 'destroy'])->name('documentos.destroy');
    Route::get('documentos/{documento}/download', [DocumentoController::class, 'download'])->name('documentos.download');
    // Conferência pelo município: aprovar ou recusar o documento da OSC.
    Route::patch('propostas/{proposta}/documentos/{documento}/analisar', [DocumentoController::class, 'analisar'])->name('documentos.analisar');
});

// Área administrativa — bloqueada para representante_legal.
// 'readonly' garante que Controle Interno só leia (não grave).
Route::middleware(['auth', 'staff', 'readonly'])->group(function () {
    // Disponível a qualquer servidor autenticado
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Busca global da barra de comandos (Ctrl+K). Cada módulo só entra no
    // resultado se o usuário tiver a permissão — a checagem é no controller.
    Route::get('/busca', BuscaController::class)->name('busca');

    // Caixa de entrada do setor — vale para TODOS os setores, por isso fica
    // aqui e não dentro de um grupo `permission:`. Ver CaixaController.
    Route::get('/caixa', CaixaController::class)->name('caixa');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Catálogo de modelos padrão — apoio do TI (Administrador Setorial)
    Route::middleware('role:administrador_setorial')->group(function () {
        Route::get('modelos', [ModeloController::class, 'index'])->name('modelos.index');
        Route::get('modelos/{origem}/{chave}', [ModeloController::class, 'show'])->name('modelos.show');
    });

    // Equipe do setor: o chefe cadastra, o administrador aprova. Vale para
    // qualquer setor — era exclusivo da Unidade Gestora.
    Route::middleware('permission:usuarios_setor')->group(function () {
        Route::get('meus-usuarios', [\App\Http\Controllers\SubusuarioController::class, 'index'])->name('subusuarios.index');
        Route::get('meus-usuarios/novo', [\App\Http\Controllers\SubusuarioController::class, 'create'])->name('subusuarios.create');
        Route::post('meus-usuarios', [\App\Http\Controllers\SubusuarioController::class, 'store'])->name('subusuarios.store');
    });

    // Cadastros institucionais
    Route::middleware('permission:cadastros')->group(function () {
        // Aprovação de cadastros (auto-cadastro de servidores e subusuários da UG)
        Route::get('usuarios/pendentes', [UserController::class, 'pendentes'])->name('usuarios.pendentes');
        Route::patch('usuarios/{usuario}/aprovar', [UserController::class, 'aprovar'])->name('usuarios.aprovar');
        Route::patch('usuarios/{usuario}/recusar', [UserController::class, 'recusar'])->name('usuarios.recusar');

        Route::resource('usuarios', UserController::class)->except(['show']);
        Route::resource('orgaos', OrgaoController::class)->except(['show']);
        Route::resource('oscs', OscController::class)->except(['show']);
        Route::get('oscs/{osc}/anexo/{campo}', [OscController::class, 'baixarAnexo'])->name('oscs.anexo');
    });

    // Módulo Unidade Gestora — Planejamento (Processos)
    Route::middleware('permission:planejamento')->group(function () {
        // A caixa virou única (os três trâmites) e mudou de lugar. O nome antigo
        // continua respondendo para não quebrar link salvo nem histórico.
        Route::get('processos/caixa', fn () => redirect()->route('caixa'))->name('processos.caixa');
        Route::resource('processos', ProcessoController::class)->except(['edit', 'update']);
        Route::get('processos/{processo}/imprimir-pecas', [ProcessoPecaController::class, 'imprimirLote'])->name('processos.pecas.imprimir-lote');
        Route::get('processos/{processo}/pecas/{peca}', [ProcessoPecaController::class, 'edit'])->name('processos.pecas.edit');
        Route::get('processos/{processo}/pecas/{peca}/imprimir', [ProcessoPecaController::class, 'imprimir'])->name('processos.pecas.imprimir');
        Route::put('processos/{processo}/pecas/{peca}', [ProcessoPecaController::class, 'update'])->name('processos.pecas.update');
        Route::patch('processos/{processo}/pecas/{peca}/assinar', [ProcessoPecaController::class, 'assinar'])->name('processos.pecas.assinar');
        Route::post('processos/{processo}/pecas/{peca}/anexos', [ProcessoPecaController::class, 'anexar'])->name('processos.pecas.anexos.store');
        Route::get('processos/{processo}/pecas/{peca}/anexos/{anexo}', [ProcessoPecaController::class, 'baixarAnexo'])->name('processos.pecas.anexos.download');
        Route::delete('processos/{processo}/pecas/{peca}/anexos/{anexo}', [ProcessoPecaController::class, 'removerAnexo'])->name('processos.pecas.anexos.destroy');
        Route::patch('processos/{processo}/receber', [TramitacaoController::class, 'receber'])->name('processos.receber');
        Route::post('processos/{processo}/avancar', [TramitacaoController::class, 'avancar'])->name('processos.avancar');
        Route::post('processos/{processo}/devolver', [TramitacaoController::class, 'devolver'])->name('processos.devolver');
        Route::patch('processos/{processo}/concluir', [TramitacaoController::class, 'concluir'])->name('processos.concluir');
        Route::post('processos/{processo}/publicar-chamamento', [TramitacaoController::class, 'publicar'])->name('processos.publicar-chamamento');
    });

    // Programas, Chamamentos e Seleção (2.2)
    Route::middleware('permission:chamamentos')->group(function () {
        Route::resource('programas', ProgramaController::class);
        Route::resource('programas.chamamentos', ChamamentoController::class)->except(['show']);
        // Manifestação de Interesse: antessala do chamamento — a SCP conduz, a
        // Secretaria opina, e o deferimento cria a dispensa/inexigibilidade.
        Route::get('manifestacoes', [ManifestacaoAnaliseController::class, 'index'])->name('manifestacoes.index');
        Route::get('manifestacoes/{manifestacao}', [ManifestacaoAnaliseController::class, 'show'])->name('manifestacoes.show');
        Route::get('manifestacoes/{manifestacao}/documentos/{documento}', [ManifestacaoAnaliseController::class, 'downloadDocumento'])->name('manifestacoes.documentos.download');
        Route::post('manifestacoes/{manifestacao}/encaminhar', [ManifestacaoAnaliseController::class, 'encaminhar'])->name('manifestacoes.encaminhar');
        Route::post('manifestacoes/{manifestacao}/parecer', [ManifestacaoAnaliseController::class, 'parecer'])->name('manifestacoes.parecer');
        Route::post('manifestacoes/{manifestacao}/deferir', [ManifestacaoAnaliseController::class, 'deferir'])->name('manifestacoes.deferir');
        Route::post('manifestacoes/{manifestacao}/indeferir', [ManifestacaoAnaliseController::class, 'indeferir'])->name('manifestacoes.indeferir');

        Route::get('chamamentos/{chamamento}/selecao', [ChamamentoController::class, 'selecao'])->name('chamamentos.selecao');
        // Trâmite da Seleção: UG → SCP → UG → SCP → Prefeito
        Route::post('chamamentos/{chamamento}/selecao/avancar', [SelecaoController::class, 'avancar'])->name('chamamentos.selecao.avancar');
        Route::post('chamamentos/{chamamento}/selecao/devolver', [SelecaoController::class, 'devolver'])->name('chamamentos.selecao.devolver');
        Route::post('chamamentos/{chamamento}/selecao/concluir', [SelecaoController::class, 'concluir'])->name('chamamentos.selecao.concluir');
        // Espaço extra de anexo: o número de publicações varia de um chamamento
        // para outro (republicação, errata, segunda edição do Diário).
        Route::post('chamamentos/{chamamento}/selecao/anexos', [SelecaoController::class, 'adicionarAnexo'])->name('chamamentos.selecao.anexos.store');
        // Declara as vencedoras de uma Seleção já encerrada (chamamentos
        // homologados antes de a adjudicação existir no encerramento).
        Route::post('chamamentos/{chamamento}/selecao/adjudicar', [SelecaoController::class, 'adjudicar'])->name('chamamentos.selecao.adjudicar');
    });

    // Propostas + Plano de Trabalho
    Route::middleware('permission:propostas')->group(function () {
        // Proposta é ato da OSC: ela cria, edita e submete no portal
        // (PortalController). Ao município cabe ler, analisar e decidir — daí
        // aqui só index e show. Antes havia CRUD completo, com a OSC escolhida
        // num dropdown: dava para o município redigir e submeter uma proposta
        // em nome de terceiro e depois aprová-la, sem rastro de quem propôs.
        Route::resource('propostas', PropostaController::class)->only(['index', 'show']);
        Route::resource('propostas.metas', MetaController::class)->except(['show', 'index']);
        Route::resource('propostas.metas.etapas', EtapaController::class)->except(['show', 'index']);
    });

    // Pareceres (técnico/jurídico/decisão) e diligências — autorização fina por tipo no controller
    Route::middleware('permission:pareceres_tecnico|pareceres_juridico|pareceres_decisao')->group(function () {
        Route::get('propostas/{proposta}/pareceres/create/{tipo}', [ParecerController::class, 'create'])
            ->name('propostas.pareceres.create');
        Route::post('propostas/{proposta}/pareceres', [ParecerController::class, 'store'])
            ->name('propostas.pareceres.store');
        Route::get('propostas/{proposta}/diligencias/{diligencia}', [DiligenciaController::class, 'show'])
            ->name('propostas.diligencias.show');
        Route::patch('propostas/{proposta}/diligencias/{diligencia}/responder', [DiligenciaController::class, 'responder'])
            ->name('propostas.diligencias.responder');
    });

    // Formalização (instrumentos e aditivos)
    Route::middleware('permission:formalizacao')->group(function () {
        Route::resource('instrumentos', InstrumentoController::class)->except(['create', 'store']);
        Route::get('propostas/{proposta}/instrumentos/create', [InstrumentoController::class, 'create'])
            ->name('instrumentos.create');
        Route::post('propostas/{proposta}/instrumentos', [InstrumentoController::class, 'store'])
            ->name('instrumentos.store');
        Route::get('instrumentos/{instrumento}/minuta', [InstrumentoController::class, 'minuta'])
            ->name('instrumentos.minuta');
        Route::patch('instrumentos/{instrumento}/assinar', [InstrumentoController::class, 'assinar'])
            ->name('instrumentos.assinar');
        Route::patch('instrumentos/{instrumento}/publicar', [InstrumentoController::class, 'publicar'])
            ->name('instrumentos.publicar');
        Route::resource('instrumentos.aditivos', AditivoController::class)->except(['index', 'show']);
        Route::get('instrumentos/{instrumento}/aditivos/{aditivo}/documentacao', [AditivoController::class, 'documentacao'])
            ->name('instrumentos.aditivos.documentacao');
    });

    // 2.3.1 Ordem de Pagamento (emitida durante a vigência do instrumento)
    Route::middleware('permission:ordem_pagamento')->group(function () {
        Route::post('instrumentos/{instrumento}/ordens-pagamento', [OrdemPagamentoController::class, 'create'])->name('ordens-pagamento.create');
        Route::get('ordens-pagamento/{ordem}/editar', [OrdemPagamentoController::class, 'edit'])->name('ordens-pagamento.edit');
        Route::put('ordens-pagamento/{ordem}', [OrdemPagamentoController::class, 'update'])->name('ordens-pagamento.update');
        Route::patch('ordens-pagamento/{ordem}/assinar', [OrdemPagamentoController::class, 'assinar'])->name('ordens-pagamento.assinar');
        Route::post('ordens-pagamento/{ordem}/dados-bancarios', [OrdemPagamentoController::class, 'uploadDadosBancarios'])->name('ordens-pagamento.dados-bancarios.upload');
        Route::get('ordens-pagamento/{ordem}/dados-bancarios', [OrdemPagamentoController::class, 'downloadDadosBancarios'])->name('ordens-pagamento.dados-bancarios.download');
        Route::get('ordens-pagamento/{ordem}/imprimir', [OrdemPagamentoController::class, 'imprimir'])->name('ordens-pagamento.imprimir');
        Route::delete('ordens-pagamento/{ordem}', [OrdemPagamentoController::class, 'destroy'])->name('ordens-pagamento.destroy');
    });

    // 4.4 Execução — repasses, despesas e controle de saldo do instrumento vigente
    Route::middleware('permission:execucao')->group(function () {
        Route::get('execucao', [ExecucaoController::class, 'index'])->name('execucao.index');
        Route::get('instrumentos/{instrumento}/execucao', [ExecucaoController::class, 'show'])->name('instrumentos.execucao');
        Route::post('instrumentos/{instrumento}/repasses', [ExecucaoController::class, 'storeRepasse'])->name('repasses.store');
        Route::put('repasses/{repasse}', [ExecucaoController::class, 'updateRepasse'])->name('repasses.update');
        Route::delete('repasses/{repasse}', [ExecucaoController::class, 'destroyRepasse'])->name('repasses.destroy');
        Route::post('instrumentos/{instrumento}/despesas', [ExecucaoController::class, 'storeDespesa'])->name('despesas.store');
        Route::put('despesas/{despesa}', [ExecucaoController::class, 'updateDespesa'])->name('despesas.update');
        Route::delete('despesas/{despesa}', [ExecucaoController::class, 'destroyDespesa'])->name('despesas.destroy');
        Route::get('despesas/{despesa}/nota-fiscal', [ExecucaoController::class, 'downloadNotaFiscal'])->name('despesas.nota.download');
    });

});

// Peças documentais (motor genérico — Seleção 2.2, Celebração e Formalização 2.3).
// A autorização é feita no PecaController: peças em trâmite são liberadas por
// setor + etapa (o que inclui a vez da OSC na Celebração); fora de trâmite,
// continua exigindo a permissão de chamamentos/formalização.
Route::middleware('auth')->group(function () {
    Route::put('pecas/{peca}', [PecaController::class, 'salvar'])->name('pecas.salvar');
    Route::patch('pecas/{peca}/assinar', [PecaController::class, 'assinar'])->name('pecas.assinar');
    Route::patch('pecas/{peca}/contra-assinar', [PecaController::class, 'contraAssinar'])->name('pecas.contra-assinar');
    Route::post('pecas/{peca}/arquivo', [PecaController::class, 'upload'])->name('pecas.upload');
    Route::post('pecas/{peca}/puxar', [PecaController::class, 'puxar'])->name('pecas.puxar');
    Route::get('pecas/{peca}/arquivo', [PecaController::class, 'download'])->name('pecas.download');
    // Anexo do documento do Planejamento que cumpre a peça — a rota do módulo
    // de Processos exige `planejamento`, que nem todo condutor da Seleção tem.
    Route::get('pecas/{peca}/origem/anexos/{anexo}', [PecaController::class, 'baixarAnexoOrigem'])->name('pecas.origem.anexo');
    Route::delete('pecas/{peca}/arquivo', [PecaController::class, 'removerArquivo'])->name('pecas.arquivo.remover');
    // Anexo avulso: só o campo criado à mão é removível (ver PecaController)
    Route::delete('pecas/{peca}', [PecaController::class, 'destruirExtra'])->name('pecas.extra.destruir');

    // Trâmite da Celebração — acessível aos setores internos e à OSC da parceria
    // A listagem é só dos setores que participam do fluxo (checagem no controller);
    // as telas por proposta seguem abertas à OSC da parceria.
    Route::get('celebracao', [CelebracaoController::class, 'index'])->name('celebracao.index');
    Route::get('celebracao/{proposta}', [CelebracaoController::class, 'show'])->name('celebracao.show');
    Route::post('celebracao/{proposta}/anexos', [CelebracaoController::class, 'adicionarAnexo'])->name('celebracao.anexos.store');
    Route::post('celebracao/{proposta}/avancar', [CelebracaoController::class, 'avancar'])->name('celebracao.avancar');
    Route::post('celebracao/{proposta}/devolver', [CelebracaoController::class, 'devolver'])->name('celebracao.devolver');
    Route::post('celebracao/{proposta}/concluir', [CelebracaoController::class, 'concluir'])->name('celebracao.concluir');
});

require __DIR__.'/auth.php';
