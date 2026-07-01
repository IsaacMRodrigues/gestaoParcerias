<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Senha: mínimo de 6 caracteres (letras e/ou números), sem exigência de complexidade.
        Password::defaults(fn () => Password::min(6));
    }
}
