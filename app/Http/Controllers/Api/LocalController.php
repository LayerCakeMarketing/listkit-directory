<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Region;
use App\Models\LocalPageSetting;
use App\Models\Place;
use App\Models\UserList;
use Illuminate\Support\Facades\Cache;

class LocalController extends Controller
{
    /**
     * Display the local index page with location-aware content
     */
    public function index(Request $request)
    {
        // Get user's location from session or detect it
        $userLocation = $this->getUserLocation($request);
        
        // Get page settings for the index
        $pageSettings = LocalPageSetting::getForPage('index');
        
        // Get featured content based on location
        $featuredContent = $this->getFeaturedContent($userLocation, $pageSettings);
        
        // Get popular regions/states
        $popularRegions = $this->getPopularRegions();
        
        // Get location hierarchy if user has a location
        $locationHierarchy = null;
        if ($userLocation) {
            $locationHierarchy = $this->getLocationHierarchy($userLocation);
        }
        
        return response()->json([
            'location' => $userLocation ? [
                'current' => $userLocation,
                'hierarchy' => $locationHierarchy,
                'detected_via' => session('location_detected_via', 'auto')
            ] : null,
            'page_settings' => $pageSettings,
            'featured_lists' => $featuredContent['lists'],
            'featured_places' => $featuredContent['places'],
            'popular_regions' => $popularRegions,
            'content_sections' => $pageSettings?->content_sections ?? []
        ]);
    }
    
    /**
     * Get user's location from session or IP
     */
    private function getUserLocation(Request $request)
    {
        // First check if location is set in session
        $regionId = session('user_region_id');
        if ($regionId) {
            $region = Region::find($regionId);
            if ($region) {
                session(['location_detected_via' => 'manual']);
                return $region;
            }
        }
        
        // Try to detect from IP
        try {
            $ip = $request->ip();
            
            // Skip local IPs
            if (in_array($ip, ['127.0.0.1', '::1']) || str_starts_with($ip, '192.168.')) {
                // Default to a major city for local development
                $defaultRegion = Region::where('slug', 'san-francisco')
                    ->where('level', 2)
                    ->first();
                    
                if ($defaultRegion) {
                    session(['location_detected_via' => 'default']);
                    return $defaultRegion;
                }
            }
            
            // Use IP geolocation service
            $geoData = Cache::remember("geo_ip_{$ip}", 3600, function () use ($ip) {
                $response = @file_get_contents("https://ipapi.co/{$ip}/json/");
                return $response ? json_decode($response, true) : null;
            });
            
            if ($geoData && isset($geoData['city']) && isset($geoData['region'])) {
                // Try to find matching region in our database
                $region = Region::where('name', $geoData['city'])
                    ->where('level', 2) // City level
                    ->whereHas('parent', function ($q) use ($geoData) {
                        $q->where('name', $geoData['region']);
                    })
                    ->first();
                
                if (!$region && isset($geoData['region'])) {
                    // Try state level
                    $region = Region::where('name', $geoData['region'])
                        ->where('level', 1)
                        ->first();
                }
                
                if ($region) {
                    session(['location_detected_via' => 'ip']);
                    return $region;
                }
            }
        } catch (\Exception $e) {
            \Log::error('IP geolocation failed: ' . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Get featured content based on location
     */
    private function getFeaturedContent($userLocation, $pageSettings)
    {
        $lists = collect();
        $places = collect();
        
        // If we have page settings with featured content, use that
        if ($pageSettings) {
            $lists = $pageSettings->featured_lists;
            $places = $pageSettings->featured_places;
        }
        
        // If location-specific, get content for that location
        if ($userLocation) {
            // Get featured lists for the region
            $regionLists = UserList::query()
                ->public()
                ->where(function ($q) use ($userLocation) {
                    // Lists that have places in this region
                    $q->whereHas('items.directoryEntry', function ($q) use ($userLocation) {
                        if ($userLocation->level == 1) { // State
                            $q->where('state_region_id', $userLocation->id);
                        } elseif ($userLocation->level == 2) { // City
                            $q->where('city_region_id', $userLocation->id);
                        } elseif ($userLocation->level == 3) { // Neighborhood
                            $q->where('neighborhood_region_id', $userLocation->id);
                        }
                    });
                })
                ->withCount('items')
                ->with(['user:id,name,username,custom_url,avatar,avatar_cloudflare_id'])
                ->orderBy('items_count', 'desc')
                ->limit(6)
                ->get();
            
            // Get featured places for the region
            $regionPlaces = Place::query()
                ->published()
                ->where(function ($q) use ($userLocation) {
                    if ($userLocation->level == 1) { // State
                        $q->where('state_region_id', $userLocation->id);
                    } elseif ($userLocation->level == 2) { // City
                        $q->where('city_region_id', $userLocation->id);
                    } elseif ($userLocation->level == 3) { // Neighborhood
                        $q->where('neighborhood_region_id', $userLocation->id);
                    }
                })
                ->where('is_featured', true)
                ->with(['category', 'location', 'stateRegion', 'cityRegion'])
                ->orderBy('updated_at', 'desc')
                ->limit(9)
                ->get();
            
            // Merge with page settings content if available
            if ($lists->isEmpty()) {
                $lists = $regionLists;
            }
            if ($places->isEmpty()) {
                $places = $regionPlaces;
            }
        }
        
        return [
            'lists' => $lists,
            'places' => $places
        ];
    }
    
    /**
     * Get popular regions for browsing
     */
    private function getPopularRegions()
    {
        return Cache::remember('popular_regions', 3600, function () {
            return Region::query()
                ->select(['id', 'name', 'slug', 'level'])
                ->where('level', 1) // States
                ->withCount(['stateEntries as directory_entries_count'])
                ->whereHas('stateEntries') // This ensures only regions with entries
                ->orderBy('directory_entries_count', 'desc')
                ->limit(12)
                ->get();
        });
    }
    
    /**
     * Get location hierarchy for breadcrumbs
     */
    private function getLocationHierarchy($region)
    {
        $hierarchy = [];
        
        // Add current region
        $hierarchy[] = $region;
        
        // Add parent regions
        $current = $region;
        while ($current->parent) {
            $current = $current->parent;
            array_unshift($hierarchy, $current);
        }
        
        return $hierarchy;
    }
}
