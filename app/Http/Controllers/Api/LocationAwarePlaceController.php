<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Place;
use App\Models\Category;
use App\Models\Region;
use App\Models\UserList;
use App\Services\LocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocationAwarePlaceController extends Controller
{
    protected LocationService $locationService;
    
    public function __construct(LocationService $locationService)
    {
        $this->locationService = $locationService;
    }
    
    /**
     * Display location-aware places index
     */
    public function index(Request $request)
    {
        $currentLocation = $this->locationService->getCurrentLocation();
        $locationHierarchy = $this->locationService->getLocationHierarchy($currentLocation);
        
        $query = Place::with(['category', 'location', 'stateRegion', 'cityRegion'])
            ->published();
            
        // Apply location filter unless viewing all
        if (!$request->has('all') && $currentLocation) {
            // Get all region IDs in the hierarchy (state, city, neighborhood)
            $regionIds = collect($locationHierarchy)->pluck('id')->toArray();
            
            // Also get all child regions if viewing at state or city level
            if ($currentLocation->level < 3) {
                $childRegionIds = $currentLocation->children()->pluck('id')->toArray();
                $regionIds = array_merge($regionIds, $childRegionIds);
            }
            
            // Check against the hierarchical region fields
            $query->where(function ($q) use ($regionIds) {
                $q->whereIn('state_region_id', $regionIds)
                  ->orWhereIn('city_region_id', $regionIds)
                  ->orWhereIn('neighborhood_region_id', $regionIds);
            });
        }
        
        // Apply filters
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        
        if ($request->filled('category')) {
            $category = Category::where('slug', $request->category)->first();
            if ($category) {
                $query->where('category_id', $category->id);
            }
        }
        
        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->q}%")
                  ->orWhere('description', 'like', "%{$request->q}%");
            });
        }
        
        // Get featured places for current location
        $featuredPlaces = $this->getFeaturedPlaces($currentLocation);
        
        // Get curated lists for current location
        $curatedLists = $this->getCuratedLists($currentLocation);
        
        // Regular places (exclude featured ones to avoid duplication)
        if ($featuredPlaces->isNotEmpty()) {
            $query->whereNotIn('id', $featuredPlaces->pluck('id'));
        }
        
        // Execute query and check if we need fallback
        $places = $query->paginate(20);
        $fallbackLocation = null;
        $fallbackMessage = null;
        
        // If no results and we have location filtering, try parent regions
        if ($places->isEmpty() && !$request->has('all') && $currentLocation) {
            $fallbackLocation = $this->findPlacesWithFallback($query, $currentLocation, $locationHierarchy);
            if ($fallbackLocation) {
                $places = $fallbackLocation['places'];
                $fallbackMessage = $fallbackLocation['message'];
            }
        }
        
        // Get categories with counts for current location
        $categories = $this->getCategoriesWithCounts($currentLocation);
        
        // Get current category if filtering by category
        $currentCategory = null;
        if ($request->filled('category')) {
            $currentCategory = Category::where('slug', $request->category)->first();
        }
        
        return response()->json([
            'location' => [
                'current' => $currentLocation,
                'hierarchy' => $locationHierarchy,
                'breadcrumb' => $this->locationService->getLocationBreadcrumb(),
                'detected_via' => session()->has('selected_location_id') ? 'manual' : 'auto',
                'fallback_message' => $fallbackMessage
            ],
            'featured_places' => $featuredPlaces,
            'curated_lists' => $curatedLists,
            'places' => $places,
            'categories' => $categories,
            'category' => $currentCategory
        ]);
    }
    
    /**
     * Display places by category with location awareness
     */
    public function byCategory(Request $request, $categorySlug)
    {
        $category = Category::where('slug', $categorySlug)->firstOrFail();
        $currentLocation = $this->locationService->getCurrentLocation();
        $locationHierarchy = $this->locationService->getLocationHierarchy($currentLocation);
        
        $query = Place::with(['location', 'category', 'stateRegion', 'cityRegion'])
            ->where('category_id', $category->id)
            ->published();
            
        // Apply location filter unless viewing all
        if (!$request->has('all') && $currentLocation) {
            $regionIds = collect($locationHierarchy)->pluck('id')->toArray();
            
            if ($currentLocation->level < 3) {
                $childRegionIds = $currentLocation->children()->pluck('id')->toArray();
                $regionIds = array_merge($regionIds, $childRegionIds);
            }
            
            // Check against the hierarchical region fields
            $query->where(function ($q) use ($regionIds) {
                $q->whereIn('state_region_id', $regionIds)
                  ->orWhereIn('city_region_id', $regionIds)
                  ->orWhereIn('neighborhood_region_id', $regionIds);
            });
        }
        
        // Get featured places for this category and location
        $featuredPlaces = $this->getFeaturedPlaces($currentLocation, $category->id);
        
        // Get curated lists for this category and location
        $curatedLists = $this->getCuratedLists($currentLocation, $category->id);
        
        if ($featuredPlaces->isNotEmpty()) {
            $query->whereNotIn('id', $featuredPlaces->pluck('id'));
        }
        
        // Execute query and check if we need fallback
        $places = $query->paginate(20);
        $fallbackLocation = null;
        $fallbackMessage = null;
        
        // If no results and we have location filtering, try parent regions
        if ($places->isEmpty() && !$request->has('all') && $currentLocation) {
            // Clone the query for fallback
            $fallbackQuery = Place::with(['location', 'category', 'stateRegion', 'cityRegion'])
                ->where('category_id', $category->id)
                ->published();
                
            $fallbackLocation = $this->findPlacesWithFallback($fallbackQuery, $currentLocation, $locationHierarchy);
            if ($fallbackLocation) {
                $places = $fallbackLocation['places'];
                $fallbackMessage = $fallbackLocation['message'];
            }
        }
        
        return response()->json([
            'category' => $category,
            'location' => [
                'current' => $currentLocation,
                'hierarchy' => $locationHierarchy,
                'breadcrumb' => $this->locationService->getLocationBreadcrumb(),
                'detected_via' => session()->has('selected_location_id') ? 'manual' : 'auto',
                'fallback_message' => $fallbackMessage
            ],
            'featured_places' => $featuredPlaces,
            'curated_lists' => $curatedLists,
            'places' => $places
        ]);
    }
    
    /**
     * Browse by state/region
     */
    public function browseByState($stateSlug)
    {
        $state = Region::where('slug', $stateSlug)
            ->where('level', 1)
            ->firstOrFail();
            
        // Set this as the current location for this request
        $this->locationService->setLocation($state->id);
        
        // Get cities in this state
        $cities = $state->children()
            ->withCount(['places' => function ($q) {
                $q->published();
            }])
            ->having('places_count', '>', 0)
            ->orderBy('name')
            ->get();
            
        // Get featured places for the state
        $featuredPlaces = $this->getFeaturedPlaces($state);
        
        // Get recent places
        $childRegionIds = $state->children()->pluck('id')->toArray();
        $regionIds = array_merge([$state->id], $childRegionIds);
        
        $recentPlaces = Place::with(['category', 'location', 'stateRegion', 'cityRegion'])
            ->where(function ($q) use ($regionIds) {
                $q->whereIn('state_region_id', $regionIds)
                  ->orWhereIn('city_region_id', $regionIds)
                  ->orWhereIn('neighborhood_region_id', $regionIds);
            })
            ->published()
            ->latest()
            ->limit(12)
            ->get();
            
        // Get categories with counts
        $categories = $this->getCategoriesWithCounts($state);
        
        return response()->json([
            'region' => $state,
            'cities' => $cities,
            'featured_places' => $featuredPlaces,
            'recent_entries' => $recentPlaces,
            'categories' => $categories
        ]);
    }
    
    /**
     * Browse by city
     */
    public function browseByCity($stateSlug, $citySlug)
    {
        $state = Region::where('slug', $stateSlug)
            ->where('level', 1)
            ->firstOrFail();
            
        $city = $state->children()
            ->where('slug', $citySlug)
            ->firstOrFail();
            
        // Set this as the current location
        $this->locationService->setLocation($city->id);
        
        // Get neighborhoods
        $neighborhoods = $city->children()
            ->withCount(['places' => function ($q) {
                $q->published();
            }])
            ->having('places_count', '>', 0)
            ->orderBy('name')
            ->get();
            
        // Get featured places
        $featuredPlaces = $this->getFeaturedPlaces($city);
        
        // Get places
        $places = Place::with(['category', 'location', 'stateRegion', 'cityRegion'])
            ->whereHas('regions', function ($q) use ($city) {
                $q->where('regions.id', $city->id);
            })
            ->published()
            ->paginate(20);
            
        $categories = $this->getCategoriesWithCounts($city);
        
        return response()->json([
            'state' => $state,
            'city' => $city,
            'neighborhoods' => $neighborhoods,
            'featured_places' => $featuredPlaces,
            'places' => $places,
            'categories' => $categories
        ]);
    }
    
    /**
     * Set user location
     */
    public function setLocation(Request $request)
    {
        $request->validate([
            'region_id' => 'required|exists:regions,id'
        ]);
        
        $success = $this->locationService->setLocation($request->region_id);
        
        if ($success) {
            $location = Region::find($request->region_id);
            return response()->json([
                'success' => true,
                'location' => $location,
                'breadcrumb' => $this->locationService->getLocationBreadcrumb()
            ]);
        }
        
        return response()->json(['success' => false], 400);
    }
    
    /**
     * Clear location (use auto-detection)
     */
    public function clearLocation()
    {
        $this->locationService->clearLocation();
        
        return response()->json([
            'success' => true,
            'location' => $this->locationService->getCurrentLocation(),
            'breadcrumb' => $this->locationService->getLocationBreadcrumb()
        ]);
    }
    
    /**
     * Get featured places for a region
     */
    protected function getFeaturedPlaces(?Region $region, ?int $categoryId = null)
    {
        if (!$region) {
            return collect();
        }
        
        // Get featured places for this specific region
        $query = $region->featuredEntries()
            ->with(['category', 'stateRegion', 'cityRegion']);
            
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        
        $featuredPlaces = $query->orderBy('region_featured_entries.priority', 'desc')
            ->limit(6)
            ->get();
            
        // If no featured places in this region and it's a neighborhood/city, check parent
        if ($featuredPlaces->isEmpty() && $region->parent_id) {
            $parentRegion = Region::find($region->parent_id);
            if ($parentRegion) {
                $query = $parentRegion->featuredEntries()
                    ->with(['category', 'stateRegion', 'cityRegion']);
                    
                if ($categoryId) {
                    $query->where('category_id', $categoryId);
                }
                
                $featuredPlaces = $query->orderBy('region_featured_entries.priority', 'desc')
                    ->limit(6)
                    ->get();
            }
        }
        
        return $featuredPlaces;
    }
    
    /**
     * Get curated lists for a region
     */
    protected function getCuratedLists(?Region $region, ?int $categoryId = null)
    {
        if (!$region) {
            return collect();
        }
        
        // Get curated lists for this specific region
        $query = UserList::where('type', 'curated')
            ->where('status', 'active')
            ->where('is_draft', false)
            ->where('is_region_specific', true)
            ->where('region_id', $region->id);
            
        if ($categoryId) {
            $query->where(function ($q) use ($categoryId) {
                $q->where('is_category_specific', true)
                  ->where('category_id', $categoryId);
            });
        }
        
        $curatedLists = $query->orderBy('order_index')
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();
            
        // If no curated lists in this region, get general curated lists
        if ($curatedLists->isEmpty()) {
            $query = UserList::where('type', 'curated')
                ->where('status', 'active')
                ->where('is_draft', false)
                ->where('is_region_specific', false);
                
            if ($categoryId) {
                $query->where('is_category_specific', true)
                      ->where('category_id', $categoryId);
            } else {
                $query->where('is_category_specific', false);
            }
            
            $curatedLists = $query->orderBy('order_index')
                ->orderBy('created_at', 'desc')
                ->limit(3)
                ->get();
        }
        
        // Load places for each list
        foreach ($curatedLists as $list) {
            if ($list->place_ids) {
                $list->places = Place::whereIn('id', $list->place_ids)
                    ->with(['category', 'stateRegion', 'cityRegion'])
                    ->orderByRaw('ARRAY_POSITION(ARRAY[' . implode(',', $list->place_ids) . '], id)')
                    ->limit(4) // Show first 4 places in each list
                    ->get();
            }
        }
        
        return $curatedLists;
    }
    
    /**
     * Get categories with location-aware counts
     */
    protected function getCategoriesWithCounts(?Region $region)
    {
        $query = Category::query();
        
        if ($region) {
            $regionIds = [$region->id];
            if ($region->level < 3) {
                $childRegionIds = $region->children()->pluck('id')->toArray();
                $regionIds = array_merge($regionIds, $childRegionIds);
            }
            
            $query->withCount(['places' => function ($q) use ($regionIds) {
                $q->published()
                  ->whereHas('regions', function ($q2) use ($regionIds) {
                      $q2->whereIn('regions.id', $regionIds);
                  });
            }]);
        } else {
            $query->withCount(['places' => function ($q) {
                $q->published();
            }]);
        }
        
        return $query->where(function ($q) {
                $q->has('places');
            })
            ->orderBy('places_count', 'desc')
            ->get();
    }
    
    /**
     * Find places with hierarchical fallback
     */
    protected function findPlacesWithFallback($baseQuery, Region $currentLocation, $locationHierarchy)
    {
        // Try parent city if in neighborhood
        if ($currentLocation->level === 3 && $currentLocation->parent_id) {
            $parentCity = Region::find($currentLocation->parent_id);
            if ($parentCity) {
                $regionIds = [$parentCity->id];
                $childRegionIds = $parentCity->children()->pluck('id')->toArray();
                $regionIds = array_merge($regionIds, $childRegionIds);
                
                $query = clone $baseQuery;
                $query->where(function ($q) use ($regionIds) {
                    $q->whereIn('state_region_id', $regionIds)
                      ->orWhereIn('city_region_id', $regionIds)
                      ->orWhereIn('neighborhood_region_id', $regionIds);
                });
                
                $places = $query->paginate(20);
                if (!$places->isEmpty()) {
                    return [
                        'places' => $places,
                        'message' => "No places found in {$currentLocation->name}. Showing places from {$parentCity->name} instead."
                    ];
                }
            }
        }
        
        // Try state if in city or if city fallback failed
        if ($currentLocation->level >= 2) {
            $state = $currentLocation->level === 2 ? 
                Region::find($currentLocation->parent_id) : 
                Region::find($currentLocation->parent->parent_id ?? null);
                
            if ($state) {
                $query = clone $baseQuery;
                $query->where('state_region_id', $state->id);
                
                $places = $query->paginate(20);
                if (!$places->isEmpty()) {
                    return [
                        'places' => $places,
                        'message' => "No places found in {$currentLocation->name}. Showing places from {$state->name} instead."
                    ];
                }
            }
        }
        
        // Last resort: show all places
        $query = clone $baseQuery;
        $places = $query->paginate(20);
        
        if (!$places->isEmpty()) {
            return [
                'places' => $places,
                'message' => "No places found in {$currentLocation->name}. Showing all available places."
            ];
        }
        
        return null;
    }
}