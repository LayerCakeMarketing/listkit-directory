<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ServeSPA
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
        // If SPA mode is enabled and this is not an API request
        if (config('spa.enabled') && !$request->is('api/*') && !$request->is('sanctum/*')) {
            return response()->view('spa');
        }

        return $next($request);
    }
}