<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\GeocodingService;

class UserMapSettingsController extends Controller
{
    protected $geocodingService;
    
    public function __construct(GeocodingService $geocodingService)
    {
        $this->geocodingService = $geocodingService;
    }
    
    /**
     * Get user's default map settings
     */
    public function show(Request $request)
    {
        $user = $request->user();
        
        // If user has default map location set, return it
        if ($user->default_map_latitude && $user->default_map_longitude) {
            return response()->json([
                'has_default' => true,
                'latitude' => $user->default_map_latitude,
                'longitude' => $user->default_map_longitude,
                'zoom' => $user->default_map_zoom ?? 12,
                'location_name' => $user->default_map_location_name ?? $user->location
            ]);
        }
        
        // Try to geocode user's profile location
        if ($user->location) {
            try {
                $result = $this->geocodingService->geocodeAddress($user->location);
                if ($result && $result['confidence'] >= 0.5) {
                    return response()->json([
                        'has_default' => false,
                        'latitude' => $result['coordinates']['lat'],
                        'longitude' => $result['coordinates']['lng'],
                        'zoom' => 12,
                        'location_name' => $user->location,
                        'geocoded' => true
                    ]);
                }
            } catch (\Exception $e) {
                // Fall through to default
            }
        }
        
        // Return default San Francisco
        return response()->json([
            'has_default' => false,
            'latitude' => 37.7749,
            'longitude' => -122.4194,
            'zoom' => 12,
            'location_name' => 'San Francisco, CA',
            'geocoded' => false
        ]);
    }
    
    /**
     * Update user's default map settings
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'zoom' => 'nullable|integer|between:1,20',
            'location_name' => 'nullable|string|max:255'
        ]);
        
        $user = $request->user();
        $user->update([
            'default_map_latitude' => $validated['latitude'],
            'default_map_longitude' => $validated['longitude'],
            'default_map_zoom' => $validated['zoom'] ?? 12,
            'default_map_location_name' => $validated['location_name'] ?? null
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Default map location updated',
            'data' => [
                'latitude' => $user->default_map_latitude,
                'longitude' => $user->default_map_longitude,
                'zoom' => $user->default_map_zoom,
                'location_name' => $user->default_map_location_name
            ]
        ]);
    }
    
    /**
     * Clear user's default map settings
     */
    public function destroy(Request $request)
    {
        $user = $request->user();
        $user->update([
            'default_map_latitude' => null,
            'default_map_longitude' => null,
            'default_map_zoom' => 12,
            'default_map_location_name' => null
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Default map location cleared'
        ]);
    }
}