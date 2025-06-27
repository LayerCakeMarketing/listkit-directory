<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            auth()->user()->updateLastActive();
        }

        return $next($request);
    }
}
