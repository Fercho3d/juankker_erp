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
        // Política de contraseña fuerte global: 8+ caracteres, may/min y números.
        Password::defaults(fn () => Password::min(8)->letters()->mixedCase()->numbers());
    }
}
