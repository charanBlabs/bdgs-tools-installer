<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['admin' => \App\Http\Middleware\EnsureAdmin::class]);
        // Trust ngrok/reverse proxy so X-Forwarded-Proto (HTTPS) is used; makes Laravel generate secure URLs
        $middleware->trustProxies(at: '*');
        // Widget runs on external site and POSTs to license check without CSRF token
        $middleware->validateCsrfTokens(except: ['api/*']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
