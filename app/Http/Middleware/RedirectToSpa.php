<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RedirectToSpa
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // If SPA mode is enabled and this is a web route that should be handled by SPA
        if (env('SPA_MODE', false) && !$request->is('api/*') && !$request->is('sanctum/*')) {
            // For auth routes, let the SPA handle them
            if ($request->is('login') || $request->is('register') || $request->is('logout')) {
                return response()->view('spa');
            }
        }

        return $next($request);
    }
}