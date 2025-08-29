<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSuspended
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->is_suspended) {
            auth()->logout();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your account has been suspended. Please check your email for more information.',
                    'suspended' => true,
                    'reason' => auth()->user()->suspension_reason
                ], 403);
            }
            
            return redirect('/login')->with('error', 'Your account has been suspended. Please check your email for more information.');
        }
        
        return $next($request);
    }
}