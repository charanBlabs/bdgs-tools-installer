<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

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
        // Force HTTPS so the browser shows "secure" (ngrok, Railway, or production)
        if (! $this->app->runningInConsole() && (request()->isSecure() || str_starts_with(config('app.url', ''), 'https://') || config('app.env') === 'production')) {
            URL::forceScheme('https');
        }
    }
}
