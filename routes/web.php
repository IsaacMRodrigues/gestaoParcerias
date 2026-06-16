<?php

use App\Http\Controllers\AditivoController;
use App\Http\Controllers\ChamamentoController;
use App\Http\Controllers\DiligenciaController;
use App\Http\Controllers\EtapaController;
use App\Http\Controllers\InstrumentoController;
use App\Http\Controllers\MetaController;
use App\Http\Controllers\OrgaoController;
use App\Http\Controllers\OscController;
use App\Http\Controllers\ParecerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgramaController;
use App\Http\Controllers\PropostaController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('usuarios', UserController::class)->except(['show']);
    Route::resource('orgaos', OrgaoController::class)->except(['show']);
    Route::resource('oscs', OscController::class)->except(['show']);

    Route::resource('programas', ProgramaController::class);
    Route::resource('programas.chamamentos', ChamamentoController::class)->except(['show']);

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
});

require __DIR__.'/auth.php';
