<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Models\Place;
use App\Services\LocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RegionController extends Controller
{
    protected LocationService $locationService;
    
    public function __construct(LocationService $locationService)
    {
        $this->locationService = $locationService;
    }
    
    /**
     * Search regions by name
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2'
        ]);
        
        $query = $request->get('q');
        
        $regions = Region::where('name', 'ilike', "%{$query}%")
            ->with(['parent.parent'])
            ->orderByRaw("
                CASE 
                    WHEN level = 2 THEN 1
                    WHEN level = 3 THEN 2
                    WHEN level = 1 THEN 3
                    ELSE 4
                END
            ")
            ->orderByRaw("CASE WHEN name ILIKE ? THEN 0 ELSE 1 END", [$query . '%'])
            ->orderBy('name')
            ->limit(20)
            ->get();
            
        return response()->json([
            'data' => $regions
        ]);
    }
    
    /**
     * Get popular/featured regions
     */
    public function popular(Request $request)
    {
        $regions = Cache::remember('popular_regions', 3600, function () {
            return Region::where('level', 2) // Cities
                ->where('is_featured', true)
                ->with(['parent'])
                ->withCount([
                    'cityEntries as places_count' => function ($q) {
                        $q->where('status', 'published');
                    }
                ])
                ->orderBy('places_count', 'desc')
                ->limit(10)
                ->get();
        });
        
        // If no featured regions, get regions with most places
        if ($regions->isEmpty()) {
            $regions = Region::where('level', 2)
                ->with(['parent'])
                ->withCount([
                    'cityEntries as places_count' => function ($q) {
                        $q->where('status', 'published');
                    }
                ])
                ->having('places_count', '>', 0)
                ->orderBy('places_count', 'desc')
                ->limit(10)
                ->get();
        }
        
        return response()->json([
            'data' => $regions
        ]);
    }
    
    /**
     * Display a listing of regions.
     */
    public function index(Request $request)
    {
        $query = Region::query()
            ->with(['parent'])
            ->withCount('entries');

        // Filter by level
        if ($request->has('level')) {
            $query->where('level', $request->level);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by parent
        if ($request->has('parent_id')) {
            $parentId = $request->parent_id;
            // Validate parent_id is not undefined/null string
            if ($parentId && $parentId !== 'undefined' && $parentId !== 'null') {
                $query->where('parent_id', $parentId);
            } elseif ($parentId === '' || $parentId === 'null') {
                $query->whereNull('parent_id');
            }
            // If parent_id is 'undefined', ignore the filter
        }

        // Filter by has_entries
        if ($request->has('has_entries') && $request->has_entries) {
            $query->having('entries_count', '>', 0);
        }

        // Include counts
        if ($request->has('with_counts') && $request->with_counts) {
            $query->withCount('children');
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        
        if ($sortBy === 'entries_count') {
            $query->orderBy('entries_count', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $limit = $request->get('limit', 50);
        $regions = $query->paginate($limit);

        // Add display_name to each region
        $regions->getCollection()->transform(function ($region) {
            $region->display_name = $region->display_name;
            return $region;
        });

        return response()->json($regions);
    }

    /**
     * Display the specified region.
     */
    public function show($id)
    {
        // Validate the ID parameter
        if (!$id || $id === 'undefined' || $id === 'null' || !is_numeric($id)) {
            return response()->json([
                'error' => 'Invalid region ID provided'
            ], 400);
        }

        $region = Cache::remember("region.{$id}", 3600, function () use ($id) {
            return Region::with(['parent', 'children'])
                ->withCount('entries')
                ->findOrFail($id);
        });

        return response()->json(['data' => $region]);
    }

    /**
     * Display region by slug hierarchy.
     */
    public function showBySlug($state, $city = null, $neighborhood = null)
    {
        $cacheKey = "region.slug.{$state}";
        if ($city) $cacheKey .= ".{$city}";
        if ($neighborhood) $cacheKey .= ".{$neighborhood}";

        $region = Cache::remember($cacheKey, 3600, function () use ($state, $city, $neighborhood) {
            if ($neighborhood && $city) {
                // Level 3 - Neighborhood
                return Region::where(function($q) use ($neighborhood) {
                        $q->where('slug', $neighborhood)
                          ->orWhere('name', $neighborhood);
                    })
                    ->where('level', 3)
                    ->whereHas('parent', function ($q) use ($city) {
                        $q->where(function($q2) use ($city) {
                            $q2->where('slug', $city)
                               ->orWhere('name', $city);
                        })->where('level', 2);
                    })
                    ->with(['parent.parent', 'parent', 'children'])
                    ->withCount(['neighborhoodEntries as entries_count' => function($q) {
                        $q->where('status', 'published');
                    }])
                    ->firstOrFail();
            } elseif ($city) {
                // Level 2 - City
                return Region::where(function($q) use ($city) {
                        $q->where('slug', $city)
                          ->orWhere('name', $city);
                    })
                    ->where('level', 2)
                    ->whereHas('parent', function ($q) use ($state) {
                        $q->where(function($q2) use ($state) {
                            $q2->where('slug', $state)
                               ->orWhere('name', $state);
                        })->where('level', 1);
                    })
                    ->with(['parent', 'children'])
                    ->withCount(['neighborhoodEntries as entries_count' => function($q) {
                        $q->where('status', 'published');
                    }])
                    ->firstOrFail();
            } else {
                // Level 1 - State
                return Region::where(function($q) use ($state) {
                        $q->where('slug', $state)
                          ->orWhere('name', $state);
                    })
                    ->where('level', 1)
                    ->with(['children'])
                    ->withCount(['stateEntries as entries_count' => function($q) {
                        $q->where('status', 'published');
                    }])
                    ->firstOrFail();
            }
        });

        // Add breadcrumb data
        $region->parent_state = null;
        $region->parent_city = null;
        
        if ($region->level === 3) {
            $region->parent_city = $region->parent;
            $region->parent_state = $region->parent->parent;
        } elseif ($region->level === 2) {
            $region->parent_state = $region->parent;
        }

        // Add URL for frontend routing
        $region->url = $this->buildRegionUrl($region);
        
        // Add display_name
        $region->display_name = $region->display_name;

        // Return city and state separately for city routes
        if ($city && !$neighborhood) {
            return response()->json([
                'city' => $region,
                'state' => $region->parent,
                'data' => $region // Keep for backward compatibility
            ]);
        }
        
        return response()->json(['data' => $region]);
    }

    /**
     * Get entries for a region.
     */
    public function entries($id, Request $request)
    {
        $region = Region::findOrFail($id);
        
        $query = Place::query()
            ->where('status', 'published')
            ->with(['category', 'location']);

        // Apply region filter based on level
        switch ($region->level) {
            case 1: // State
                $query->where('state_region_id', $id);
                break;
            case 2: // City
                $query->where('city_region_id', $id);
                break;
            case 3: // Neighborhood
                $query->where('neighborhood_region_id', $id);
                break;
        }

        // Apply category filter
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Apply search
        if ($request->has('search')) {
            $query->where('title', 'ilike', '%' . $request->search . '%');
        }

        $entries = $query->orderBy('is_featured', 'desc')
            ->orderBy('title')
            ->paginate(20);

        return response()->json($entries);
    }

    /**
     * Get child regions.
     */
    public function children($id)
    {
        // Validate the ID parameter
        if (!$id || $id === 'undefined' || $id === 'null' || !is_numeric($id)) {
            return response()->json([
                'error' => 'Invalid region ID provided',
                'data' => []
            ], 400);
        }

        $children = Cache::remember("region.{$id}.children", 3600, function () use ($id) {
            return Region::where('parent_id', $id)
                ->with('parent.parent') // Load parent relationships for URL building
                ->withCount('entries')
                ->orderBy('name')
                ->get()
                ->map(function ($child) {
                    $child->url = $this->buildRegionUrl($child);
                    $child->display_name = $child->display_name;
                    return $child;
                });
        });

        return response()->json(['data' => $children]);
    }

    /**
     * Get featured entries for a region.
     */
    public function featured($id)
    {
        $region = Region::findOrFail($id);
        
        $featured = $region->featuredEntries()
            ->with(['category', 'location'])
            ->orderBy('pivot_created_at', 'desc')
            ->get();

        return response()->json(['data' => $featured]);
    }

    /**
     * Build the full URL for a region.
     */
    private function buildRegionUrl($region)
    {
        $segments = ['/local'];
        
        if ($region->level === 1) {
            $segments[] = $region->slug;
        } elseif ($region->level === 2) {
            $segments[] = $region->parent->slug;
            $segments[] = $region->slug;
        } elseif ($region->level === 3) {
            $segments[] = $region->parent->parent->slug;
            $segments[] = $region->parent->slug;
            $segments[] = $region->slug;
        }
        
        return implode('/', $segments);
    }
    
    /**
     * Detect user's location from IP
     */
    public function detectLocation(Request $request)
    {
        $location = $this->locationService->detectLocationFromIP($request->ip());
        
        return response()->json([
            'location' => $location,
            'detected_via' => 'ip'
        ]);
    }
}