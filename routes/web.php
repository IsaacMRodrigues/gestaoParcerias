<?php

use App\Http\Controllers\AditivoController;
use App\Http\Controllers\CelebracaoController;
use App\Http\Controllers\ChamamentoController;
use App\Http\Controllers\SelecaoController;
use App\Http\Controllers\DiligenciaController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\ExecucaoController;
use App\Http\Controllers\EtapaController;
use App\Http\Controllers\InstrumentoController;
use App\Http\Controllers\MetaController;
use App\Http\Controllers\OrdemPagamentoController;
use App\Http\Controllers\OrgaoController;
use App\Http\Controllers\OscController;
use App\Http\Controllers\OscRegistroController;
use App\Http\Controllers\ParecerController;
use App\Http\Controllers\PecaController;
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
// Parlamentar e Conselho). Quem já está logado vai direto ao seu destino.
Route::get('/', function () {
    if (auth()->check()) {
        return auth()->user()->temAcessoInterno()
            ? redirect()->route('dashboard')
            : redirect()->route('portal.index');
    }

    return view('landing');
})->name('landing');

// Portal público
Route::get('/portal', [PortalController::class, 'index'])->name('portal.index');
Route::get('/portal/chamamentos/{chamamento}', [PortalController::class, 'chamamento'])->name('portal.chamamento');
Route::get('/transparencia', [PortalController::class, 'transparencia'])->name('transparencia');

// Auto-cadastro de OSC
Route::get('/cadastro/osc', [OscRegistroController::class, 'create'])->name('portal.osc.create');
Route::post('/cadastro/osc', [OscRegistroController::class, 'store'])->name('portal.osc.store');

// Validação pública de documentos assinados
Route::get('/validar', [\App\Http\Controllers\ValidacaoController::class, 'index'])->name('validacao.index');
Route::post('/validar', [\App\Http\Controllers\ValidacaoController::class, 'verificar'])->name('validacao.verificar');
Route::get('/validar/{codigo}', [\App\Http\Controllers\ValidacaoController::class, 'mostrar'])->name('validacao.mostrar');

// Área da OSC logada (portal)
Route::middleware('auth')->group(function () {
    Route::get('/portal/minhas-propostas', [PortalController::class, 'minhasPropostas'])->name('portal.minhas-propostas');
    Route::get('/portal/chamamentos/{chamamento}/participar', [PortalController::class, 'participar'])->name('portal.participar');
    Route::post('/portal/chamamentos/{chamamento}/proposta', [PortalController::class, 'storeProposta'])->name('portal.proposta.store');
    Route::get('/portal/propostas/{proposta}', [PortalController::class, 'showProposta'])->name('portal.proposta.show');
    Route::patch('/portal/propostas/{proposta}/submeter', [PortalController::class, 'submeterProposta'])->name('portal.proposta.submeter');
    // Recurso contra o resultado provisório (protocolo eletrônico pela OSC)
    Route::post('/portal/chamamentos/{chamamento}/recurso', [RecursoController::class, 'store'])->name('recursos.store');
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
});

// Área administrativa — bloqueada para representante_legal.
// 'readonly' garante que Controle Interno só leia (não grave).
Route::middleware(['auth', 'staff', 'readonly'])->group(function () {
    // Disponível a qualquer servidor autenticado
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Subusuários da Unidade Gestora (a UG cadastra; o admin aprova)
    Route::middleware('role:responsavel_unidade_gestora')->group(function () {
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
        Route::get('processos/caixa', [ProcessoController::class, 'caixa'])->name('processos.caixa');
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
        Route::get('chamamentos/{chamamento}/selecao', [ChamamentoController::class, 'selecao'])->name('chamamentos.selecao');
        // Trâmite da Seleção: UG → SCP → UG → SCP → Prefeito
        Route::post('chamamentos/{chamamento}/selecao/avancar', [SelecaoController::class, 'avancar'])->name('chamamentos.selecao.avancar');
        Route::post('chamamentos/{chamamento}/selecao/devolver', [SelecaoController::class, 'devolver'])->name('chamamentos.selecao.devolver');
        Route::post('chamamentos/{chamamento}/selecao/concluir', [SelecaoController::class, 'concluir'])->name('chamamentos.selecao.concluir');
    });

    // Propostas + Plano de Trabalho
    Route::middleware('permission:propostas')->group(function () {
        Route::resource('propostas', PropostaController::class);
        Route::patch('propostas/{proposta}/submeter', [PropostaController::class, 'submeter'])->name('propostas.submeter');
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
        Route::get('instrumentos/{instrumento}/execucao', [ExecucaoController::class, 'show'])->name('instrumentos.execucao');
        Route::post('instrumentos/{instrumento}/repasses', [ExecucaoController::class, 'storeRepasse'])->name('repasses.store');
        Route::delete('repasses/{repasse}', [ExecucaoController::class, 'destroyRepasse'])->name('repasses.destroy');
        Route::post('instrumentos/{instrumento}/despesas', [ExecucaoController::class, 'storeDespesa'])->name('despesas.store');
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
    Route::delete('pecas/{peca}/arquivo', [PecaController::class, 'removerArquivo'])->name('pecas.arquivo.remover');

    // Trâmite da Celebração — acessível aos setores internos e à OSC da parceria
    Route::get('celebracao/{proposta}', [CelebracaoController::class, 'show'])->name('celebracao.show');
    Route::post('celebracao/{proposta}/avancar', [CelebracaoController::class, 'avancar'])->name('celebracao.avancar');
    Route::post('celebracao/{proposta}/devolver', [CelebracaoController::class, 'devolver'])->name('celebracao.devolver');
    Route::post('celebracao/{proposta}/concluir', [CelebracaoController::class, 'concluir'])->name('celebracao.concluir');
});

require __DIR__.'/auth.php';
