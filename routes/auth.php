<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\PedidoDeSenhaController;
use App\Http\Controllers\TrocaDeSenhaController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // Senha esquecida vira chamado de suporte (ver PedidoDeSenhaController).
    // O fluxo do Breeze — link por e-mail — saiu: o sistema não envia e-mail,
    // e a tela prometia um link que nunca chegava. O nome `password.request`
    // fica, porque é por ele que a tela de entrada aponta para cá.
    Route::get('esqueci-a-senha', [PedidoDeSenhaController::class, 'create'])
        ->name('password.request');

    Route::post('esqueci-a-senha', [PedidoDeSenhaController::class, 'store'])
        ->middleware('throttle:3,10')
        ->name('senha.pedido');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    // Troca obrigatória de senha definida por outra pessoa — ver ExigeTrocaDeSenha.
    Route::get('trocar-senha', [TrocaDeSenhaController::class, 'edit'])->name('senha.trocar');
    Route::put('trocar-senha', [TrocaDeSenhaController::class, 'update'])->name('senha.trocar.salvar');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
