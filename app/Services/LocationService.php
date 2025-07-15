<?php

namespace App\Services;

use App\Models\Region;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LocationService
{
    /**
     * Get the current user's location context
     */
    public function getCurrentLocation(): ?Region
    {
        try {
            // First check session for manually selected location
            $sessionLocationId = Session::get('selected_location_id');
            if ($sessionLocationId) {
                $location = Region::find($sessionLocationId);
                if ($location) {
                    return $location;
                }
            }
            
            // Check authenticated user's saved location
            if (auth()->check() && auth()->user()->default_region_id) {
                $location = Region::find(auth()->user()->default_region_id);
                if ($location) {
                    return $location;
                }
            }
            
            // Fall back to IP-based detection
            return $this->detectLocationFromIP();
        } catch (\Exception $e) {
            \Log::error('Error getting current location: ' . $e->getMessage());
            return $this->getDefaultLocation();
        }
    }
    
    /**
     * Detect location from IP address
     */
    public function detectLocationFromIP(?string $ip = null): ?Region
    {
        $ip = $ip ?? request()->ip();
        
        // Skip for local IPs
        if (in_array($ip, ['127.0.0.1', '::1', 'localhost'])) {
            return $this->getDefaultLocation();
        }
        
        // Check cache first
        $cacheKey = 'location_ip_' . $ip;
        $cachedLocation = Cache::get($cacheKey);
        if ($cachedLocation !== null) {
            return $cachedLocation ? Region::find($cachedLocation) : null;
        }
        
        try {
            // Use ip-api.com (free tier allows 45 requests per minute)
            $response = Http::timeout(3)->get("http://ip-api.com/json/{$ip}", [
                'fields' => 'status,country,regionName,city,lat,lon'
            ]);
            
            if ($response->successful() && $response->json('status') === 'success') {
                $data = $response->json();
                
                // Try to find matching region
                $region = $this->findRegionFromGeoData(
                    $data['regionName'] ?? null,
                    $data['city'] ?? null,
                    $data['lat'] ?? null,
                    $data['lon'] ?? null
                );
                
                // Cache for 24 hours
                Cache::put($cacheKey, $region?->id, 86400);
                
                return $region;
            }
        } catch (\Exception $e) {
            Log::warning('IP geolocation failed', ['ip' => $ip, 'error' => $e->getMessage()]);
        }
        
        // Cache the failure for 1 hour to avoid repeated API calls
        Cache::put($cacheKey, null, 3600);
        
        return $this->getDefaultLocation();
    }
    
    /**
     * Find region from geo data
     */
    private function findRegionFromGeoData(?string $state, ?string $city, ?float $lat, ?float $lon): ?Region
    {
        // First try exact match on city
        if ($city && $state) {
            $region = Region::where('name', $city)
                ->where('level', 2)
                ->whereHas('parent', function ($q) use ($state) {
                    $q->where('name', $state)->where('level', 1);
                })
                ->first();
                
            if ($region) {
                return $region;
            }
        }
        
        // Try state level
        if ($state) {
            $region = Region::where('name', $state)
                ->where('level', 1)
                ->first();
                
            if ($region) {
                return $region;
            }
        }
        
        // If we have coordinates, find nearest city
        if ($lat && $lon) {
            return $this->findNearestRegion($lat, $lon);
        }
        
        return null;
    }
    
    /**
     * Find nearest region based on coordinates
     */
    private function findNearestRegion(float $lat, float $lon): ?Region
    {
        // This assumes regions have latitude/longitude fields
        // Using Haversine formula for distance calculation
        $nearest = Region::where('level', 2) // Cities
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw("*, 
                (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * 
                cos(radians(longitude) - radians(?)) + sin(radians(?)) * 
                sin(radians(latitude)))) AS distance", [$lat, $lon, $lat])
            ->orderBy('distance')
            ->first();
            
        return $nearest;
    }
    
    /**
     * Get default location
     */
    public function getDefaultLocation(): ?Region
    {
        // Return a default region (e.g., California or USA)
        return Region::where('slug', 'california')->where('level', 1)->first();
    }
    
    /**
     * Set user's location
     */
    public function setLocation(int $regionId): bool
    {
        $region = Region::find($regionId);
        if (!$region) {
            return false;
        }
        
        // Store in session
        Session::put('selected_location_id', $regionId);
        Session::put('selected_location_name', $region->full_name);
        
        // Store for authenticated users
        if (auth()->check()) {
            auth()->user()->update(['default_region_id' => $regionId]);
        }
        
        return true;
    }
    
    /**
     * Clear selected location
     */
    public function clearLocation(): void
    {
        Session::forget(['selected_location_id', 'selected_location_name']);
    }
    
    /**
     * Get location hierarchy
     */
    public function getLocationHierarchy(?Region $region = null): array
    {
        $region = $region ?? $this->getCurrentLocation();
        
        if (!$region) {
            return [];
        }
        
        $hierarchy = [];
        $current = $region;
        
        // Load parent relationships to avoid N+1 queries
        if ($current) {
            $current->load('parent.parent');
        }
        
        while ($current) {
            array_unshift($hierarchy, $current);
            $current = $current->parent;
        }
        
        return $hierarchy;
    }
    
    /**
     * Get breadcrumb for current location
     */
    public function getLocationBreadcrumb(): string
    {
        $hierarchy = $this->getLocationHierarchy();
        
        if (empty($hierarchy)) {
            return 'All Locations';
        }
        
        return collect($hierarchy)->pluck('name')->implode(' › ');
    }
}