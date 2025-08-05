<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GeocodingService;
use Illuminate\Http\Request;

class GeocodingController extends Controller
{
    protected $geocodingService;
    
    public function __construct(GeocodingService $geocodingService)
    {
        $this->geocodingService = $geocodingService;
    }
    
    /**
     * Validate an address
     */
    public function validateAddress(Request $request)
    {
        $request->validate([
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:50',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:2',
        ]);
        
        $result = $this->geocodingService->validateAddress($request->all());
        
        return response()->json($result);
    }
    
    /**
     * Geocode an address
     */
    public function geocode(Request $request)
    {
        $request->validate([
            'address' => 'required|string|max:500',
            'country' => 'nullable|string|max:2',
        ]);
        
        $result = $this->geocodingService->geocodeAddress(
            $request->address,
            $request->only('country')
        );
        
        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }
    
    /**
     * Reverse geocode coordinates
     */
    public function reverseGeocode(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);
        
        $result = $this->geocodingService->reverseGeocode(
            $request->lat,
            $request->lng
        );
        
        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'No address found for coordinates'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }
}