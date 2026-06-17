<?php

use App\Http\Controllers\AditivoController;
use App\Http\Controllers\ChamamentoController;
use App\Http\Controllers\DiligenciaController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\EtapaController;
use App\Http\Controllers\InstrumentoController;
use App\Http\Controllers\MetaController;
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
use App\Http\Controllers\PropostaController;
use App\Http\Controllers\TermoReferenciaController;
use App\Http\Controllers\TramitacaoController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('portal.index'));

// Portal público
Route::get('/portal', [PortalController::class, 'index'])->name('portal.index');
Route::get('/portal/chamamentos/{chamamento}', [PortalController::class, 'chamamento'])->name('portal.chamamento');

// Auto-cadastro de OSC
Route::get('/cadastro/osc', [OscRegistroController::class, 'create'])->name('portal.osc.create');
Route::post('/cadastro/osc', [OscRegistroController::class, 'store'])->name('portal.osc.store');

// Área da OSC logada (portal)
Route::middleware('auth')->group(function () {
    Route::get('/portal/minhas-propostas', [PortalController::class, 'minhasPropostas'])->name('portal.minhas-propostas');
    Route::get('/portal/chamamentos/{chamamento}/participar', [PortalController::class, 'participar'])->name('portal.participar');
    Route::post('/portal/chamamentos/{chamamento}/proposta', [PortalController::class, 'storeProposta'])->name('portal.proposta.store');
    Route::get('/portal/propostas/{proposta}', [PortalController::class, 'showProposta'])->name('portal.proposta.show');
    Route::patch('/portal/propostas/{proposta}/submeter', [PortalController::class, 'submeterProposta'])->name('portal.proposta.submeter');
});

// Documentos (funciona para admin e portal via back())
Route::middleware('auth')->group(function () {
    Route::post('propostas/{proposta}/documentos', [DocumentoController::class, 'store'])->name('documentos.store');
    Route::delete('propostas/{proposta}/documentos/{documento}', [DocumentoController::class, 'destroy'])->name('documentos.destroy');
    Route::get('documentos/{documento}/download', [DocumentoController::class, 'download'])->name('documentos.download');
});

// Área administrativa — bloqueada para representante_legal
Route::middleware(['auth', 'staff'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('usuarios', UserController::class)->except(['show']);
    Route::resource('orgaos', OrgaoController::class)->except(['show']);
    Route::resource('oscs', OscController::class)->except(['show']);

    // Módulo Unidade Gestora — Planejamento (Processos)
    Route::get('processos/caixa', [ProcessoController::class, 'caixa'])->name('processos.caixa');
    Route::resource('processos', ProcessoController::class)->except(['edit', 'update']);
    Route::get('processos/{processo}/termo', [TermoReferenciaController::class, 'edit'])->name('processos.termo.edit');
    Route::put('processos/{processo}/termo', [TermoReferenciaController::class, 'update'])->name('processos.termo.update');
    Route::patch('processos/{processo}/termo/assinar', [TermoReferenciaController::class, 'assinar'])->name('processos.termo.assinar');
    Route::get('processos/{processo}/pecas/{peca}', [ProcessoPecaController::class, 'edit'])->name('processos.pecas.edit');
    Route::put('processos/{processo}/pecas/{peca}', [ProcessoPecaController::class, 'update'])->name('processos.pecas.update');
    Route::patch('processos/{processo}/pecas/{peca}/assinar', [ProcessoPecaController::class, 'assinar'])->name('processos.pecas.assinar');
    Route::post('processos/{processo}/enviar', [TramitacaoController::class, 'enviar'])->name('processos.enviar');
    Route::patch('processos/{processo}/receber', [TramitacaoController::class, 'receber'])->name('processos.receber');
    Route::patch('processos/{processo}/abrir', [TramitacaoController::class, 'abrir'])->name('processos.abrir');

    Route::resource('programas', ProgramaController::class);
    Route::resource('programas.chamamentos', ChamamentoController::class)->except(['show']);

    // 2.2 Seleção e Celebração — checklist documental do chamamento
    Route::get('chamamentos/{chamamento}/selecao', [ChamamentoController::class, 'selecao'])->name('chamamentos.selecao');

    // Peças documentais (motor genérico — 2.2 e 2.3)
    Route::put('pecas/{peca}', [PecaController::class, 'salvar'])->name('pecas.salvar');
    Route::patch('pecas/{peca}/assinar', [PecaController::class, 'assinar'])->name('pecas.assinar');
    Route::post('pecas/{peca}/arquivo', [PecaController::class, 'upload'])->name('pecas.upload');
    Route::get('pecas/{peca}/arquivo', [PecaController::class, 'download'])->name('pecas.download');
    Route::delete('pecas/{peca}/arquivo', [PecaController::class, 'removerArquivo'])->name('pecas.arquivo.remover');

    Route::resource('propostas', PropostaController::class);
    Route::patch('propostas/{proposta}/submeter', [PropostaController::class, 'submeter'])->name('propostas.submeter');
    Route::resource('propostas.metas', MetaController::class)->except(['show', 'index']);
    Route::resource('propostas.metas.etapas', EtapaController::class)->except(['show', 'index']);

    Route::get('propostas/{proposta}/pareceres/create/{tipo}', [ParecerController::class, 'create'])
        ->name('propostas.pareceres.create');
    Route::post('propostas/{proposta}/pareceres', [ParecerController::class, 'store'])
        ->name('propostas.pareceres.store');

    Route::get('propostas/{proposta}/diligencias/{diligencia}', [DiligenciaController::class, 'show'])
        ->name('propostas.diligencias.show');
    Route::patch('propostas/{proposta}/diligencias/{diligencia}/responder', [DiligenciaController::class, 'responder'])
        ->name('propostas.diligencias.responder');

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

require __DIR__.'/auth.php';
