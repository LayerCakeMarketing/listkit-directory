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
        
        // For SPA mode, we need to set up the correct middleware order
        if (env('SPA_MODE', false)) {
            // Clear the default API middleware and set our own
            $middleware->group('api', []);
            
            // Then append the middleware in the correct order
            $middleware->appendToGroup('api', \App\Http\Middleware\EncryptCookies::class);
            $middleware->appendToGroup('api', \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class);
            $middleware->appendToGroup('api', \Illuminate\Session\Middleware\StartSession::class);
            $middleware->appendToGroup('api', \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class);
            // Remove throttle:api as it's not defined
            $middleware->appendToGroup('api', \Illuminate\Routing\Middleware\SubstituteBindings::class);
        } else {
            // Non-SPA mode: just prepend Sanctum middleware
            $middleware->api(prepend: [
                \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            ]);
        }
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();