<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: env('SPA_MODE', false) ? __DIR__.'/../routes/web-spa.php' : __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'role.api' => \App\Http\Middleware\CheckRoleApi::class,
        ]);
        
        // Ensure Sanctum middleware is applied to API routes
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
        
        // Add session to API routes for SPA mode
        if (env('SPA_MODE', false)) {
            $middleware->group('api', [
                \Illuminate\Cookie\Middleware\EncryptCookies::class,
                \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
                \Illuminate\Session\Middleware\StartSession::class,
            ]);
        }
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();