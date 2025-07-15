<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRoleApi
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (!auth()->user()->hasAnyRole($roles)) {
            return response()->json(['message' => 'Forbidden. User does not have the required role.'], 403);
        }

        return $next($request);
    }
}