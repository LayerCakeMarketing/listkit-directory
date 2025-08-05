<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GeocodingService
{
    protected $mapboxToken;
    protected $baseUrl = 'https://api.mapbox.com/geocoding/v5/mapbox.places';
    
    public function __construct()
    {
        $this->mapboxToken = config('services.mapbox.secret_token');
    }
    
    /**
     * Geocode an address and return coordinates with validation
     */
    public function geocodeAddress(string $address, array $options = []): ?array
    {
        if (empty($address)) {
            return null;
        }
        
        // Check cache first
        $cacheKey = 'geocode:' . md5($address . json_encode($options));
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        try {
            $params = [
                'access_token' => $this->mapboxToken,
                'limit' => 1,
                'types' => $options['types'] ?? 'address,poi',
            ];
            
            // Add country bias if provided
            if (isset($options['country'])) {
                $params['country'] = $options['country'];
            }
            
            // Add proximity bias if coordinates provided
            if (isset($options['proximity'])) {
                $params['proximity'] = $options['proximity'];
            }
            
            $response = Http::get($this->baseUrl . '/' . urlencode($address) . '.json', $params);
            
            if (!$response->successful()) {
                Log::error('Geocoding API error', [
                    'address' => $address,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return null;
            }
            
            $data = $response->json();
            
            if (empty($data['features'])) {
                // No results found
                Cache::put($cacheKey, false, now()->addDays(7)); // Cache negative results
                return null;
            }
            
            $feature = $data['features'][0];
            
            // Extract components
            $result = [
                'coordinates' => [
                    'lat' => $feature['center'][1],
                    'lng' => $feature['center'][0]
                ],
                'formatted_address' => $feature['place_name'],
                'confidence' => $feature['relevance'] ?? 0,
                'place_type' => $feature['place_type'][0] ?? 'unknown',
                'components' => $this->extractAddressComponents($feature),
                'bbox' => $feature['bbox'] ?? null,
            ];
            
            // Cache for 30 days
            Cache::put($cacheKey, $result, now()->addDays(30));
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Geocoding exception', [
                'address' => $address,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Validate and standardize an address
     */
    public function validateAddress(array $addressData): array
    {
        // Build address string from components
        $addressParts = array_filter([
            $addressData['address_line1'] ?? '',
            $addressData['address_line2'] ?? '',
            $addressData['city'] ?? '',
            $addressData['state'] ?? '',
            $addressData['zip_code'] ?? '',
            $addressData['country'] ?? 'USA'
        ]);
        
        $fullAddress = implode(', ', $addressParts);
        
        $result = $this->geocodeAddress($fullAddress, [
            'country' => strtolower($addressData['country'] ?? 'us'),
            'types' => 'address'
        ]);
        
        if (!$result) {
            return [
                'valid' => false,
                'errors' => ['Address could not be validated'],
                'suggestions' => []
            ];
        }
        
        // Check confidence score
        $isValid = $result['confidence'] >= 0.8;
        
        return [
            'valid' => $isValid,
            'confidence' => $result['confidence'],
            'standardized' => $result['components'],
            'coordinates' => $result['coordinates'],
            'formatted_address' => $result['formatted_address'],
            'errors' => $isValid ? [] : ['Low confidence match - please verify address'],
            'suggestions' => [] // Could implement multiple suggestions in future
        ];
    }
    
    /**
     * Reverse geocode coordinates to get address
     */
    public function reverseGeocode(float $lat, float $lng): ?array
    {
        $cacheKey = "reverse:$lat,$lng";
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        try {
            $response = Http::get("$this->baseUrl/$lng,$lat.json", [
                'access_token' => $this->mapboxToken,
                'types' => 'address,poi'
            ]);
            
            if (!$response->successful()) {
                return null;
            }
            
            $data = $response->json();
            
            if (empty($data['features'])) {
                return null;
            }
            
            $feature = $data['features'][0];
            $result = [
                'formatted_address' => $feature['place_name'],
                'components' => $this->extractAddressComponents($feature)
            ];
            
            Cache::put($cacheKey, $result, now()->addDays(30));
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Reverse geocoding error', [
                'coordinates' => "$lat,$lng",
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Batch geocode multiple addresses
     */
    public function batchGeocode(array $addresses, array $options = []): array
    {
        $results = [];
        
        // Mapbox doesn't have native batch geocoding, so we'll process sequentially
        // with a small delay to respect rate limits
        foreach ($addresses as $key => $address) {
            $results[$key] = $this->geocodeAddress($address, $options);
            
            // Add small delay to avoid rate limiting (max 600/min)
            usleep(100000); // 0.1 seconds
        }
        
        return $results;
    }
    
    /**
     * Extract address components from Mapbox response
     */
    protected function extractAddressComponents(array $feature): array
    {
        $components = [
            'address' => null,
            'city' => null,
            'state' => null,
            'zip_code' => null,
            'country' => null,
        ];
        
        // Parse the context array
        if (isset($feature['context'])) {
            foreach ($feature['context'] as $context) {
                if (strpos($context['id'], 'postcode') === 0) {
                    $components['zip_code'] = $context['text'];
                } elseif (strpos($context['id'], 'place') === 0) {
                    $components['city'] = $context['text'];
                } elseif (strpos($context['id'], 'region') === 0) {
                    $components['state'] = $context['text'];
                } elseif (strpos($context['id'], 'country') === 0) {
                    $components['country'] = $context['text'];
                }
            }
        }
        
        // Extract street address from properties
        if (isset($feature['properties']['address'])) {
            $components['address'] = $feature['properties']['address'];
        } elseif (isset($feature['text'])) {
            $components['address'] = $feature['text'];
        }
        
        return $components;
    }
    
    /**
     * Calculate distance between two points
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2, string $unit = 'miles'): float
    {
        $earthRadius = ($unit === 'miles') ? 3959 : 6371; // Miles or kilometers
        
        $latDiff = deg2rad($lat2 - $lat1);
        $lngDiff = deg2rad($lng2 - $lng1);
        
        $a = sin($latDiff / 2) * sin($latDiff / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lngDiff / 2) * sin($lngDiff / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }
}