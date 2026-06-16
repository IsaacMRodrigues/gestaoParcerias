<?php

use App\Http\Controllers\ChamamentoController;
use App\Http\Controllers\OrgaoController;
use App\Http\Controllers\OscController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgramaController;
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
});

require __DIR__.'/auth.php';
