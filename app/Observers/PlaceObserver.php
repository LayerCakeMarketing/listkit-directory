<?php

namespace App\Observers;

use App\Models\Place;
use App\Services\GeocodingService;
use Illuminate\Support\Facades\Log;

class PlaceObserver
{
    protected $geocodingService;
    
    public function __construct(GeocodingService $geocodingService)
    {
        $this->geocodingService = $geocodingService;
    }
    
    /**
     * Handle the Place "saving" event.
     */
    public function saving(Place $place)
    {
        // Check if address has changed and needs geocoding
        if ($this->shouldGeocode($place)) {
            $this->geocodePlace($place);
        }
    }
    
    /**
     * Determine if place needs geocoding
     */
    protected function shouldGeocode(Place $place): bool
    {
        // Skip if coordinates are manually set and verified
        if ($place->location_verified) {
            return false;
        }
        
        // Check if address fields have changed
        $addressFields = ['address', 'city', 'state', 'zip_code'];
        $addressChanged = false;
        
        foreach ($addressFields as $field) {
            if ($place->isDirty($field)) {
                $addressChanged = true;
                break;
            }
        }
        
        // Geocode if:
        // 1. Address changed
        // 2. No coordinates yet
        // 3. Coordinates exist but address changed (re-geocode)
        return $addressChanged || 
               (!$place->latitude || !$place->longitude) && 
               ($place->address || $place->city);
    }
    
    /**
     * Geocode the place
     */
    protected function geocodePlace(Place $place)
    {
        // Build address string
        $addressParts = array_filter([
            $place->address,
            $place->city,
            $place->state,
            $place->zip_code,
            'USA' // Default country
        ]);
        
        if (empty($addressParts)) {
            return;
        }
        
        $fullAddress = implode(', ', $addressParts);
        
        try {
            // Use proximity bias if place has existing coordinates
            $options = [];
            if ($place->latitude && $place->longitude) {
                $options['proximity'] = "{$place->longitude},{$place->latitude}";
            }
            
            $result = $this->geocodingService->geocodeAddress($fullAddress, $options);
            
            if ($result && $result['confidence'] >= 0.5) {
                // Update coordinates
                $place->latitude = $result['coordinates']['lat'];
                $place->longitude = $result['coordinates']['lng'];
                
                // Update address components if better quality
                if ($result['confidence'] >= 0.8) {
                    $components = $result['components'];
                    
                    // Only update if component is not already set
                    if (!$place->city && $components['city']) {
                        $place->city = $components['city'];
                    }
                    if (!$place->state && $components['state']) {
                        $place->state = $components['state'];
                    }
                    if (!$place->zip_code && $components['zip_code']) {
                        $place->zip_code = $components['zip_code'];
                    }
                }
                
                // Mark as geocoded but not manually verified
                $place->location_updated_at = now();
                $place->location_verified = false;
                
                Log::info('Place geocoded successfully', [
                    'place_id' => $place->id,
                    'address' => $fullAddress,
                    'confidence' => $result['confidence']
                ]);
            } else {
                Log::warning('Geocoding failed or low confidence', [
                    'place_id' => $place->id,
                    'address' => $fullAddress,
                    'result' => $result
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Geocoding error', [
                'place_id' => $place->id,
                'address' => $fullAddress,
                'error' => $e->getMessage()
            ]);
        }
    }
}