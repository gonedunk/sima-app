<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Pagination\Paginator; // <-- TAMBAHKAN BARIS INI

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
        // Force HTTPS untuk environment non-local / proxy SSL
        if (config('app.env') !== 'local' || request()->secure() || str_contains(request()->header('x-forwarded-proto', ''), 'https')) {
            URL::forceScheme('https');
        }

        Paginator::useBootstrapFive(); // <-- 2. Aktifkan pagination Bootstrap 5
    }
}