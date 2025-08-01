<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Place;

class CheckPlaceSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $requiredFeature = null): Response
    {
        $place = $request->route('place');
        
        if (!$place instanceof Place) {
            return response()->json(['message' => 'Place not found'], 404);
        }

        $user = auth()->user();
        
        // Check if user can manage this place
        if (!$place->canBeManaged($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // If a specific feature is required, check tier access
        if ($requiredFeature && !$place->hasTierAccess($requiredFeature)) {
            return response()->json([
                'message' => 'This feature requires a higher subscription tier',
                'required_feature' => $requiredFeature,
                'current_tier' => $place->subscription_tier,
                'upgrade_url' => '/places/' . $place->id . '/subscription'
            ], 402);
        }

        return $next($request);
    }
}