<?php

namespace App\Http\Controllers;

use App\Models\Region;
use App\Services\RegionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RegionController extends Controller
{
    protected $regionService;

    public function __construct(RegionService $regionService)
    {
        $this->regionService = $regionService;
    }

    /**
     * Display region landing page
     */
    public function show($path)
    {
        // Convert path string to array of segments
        $slugs = explode('/', $path);
        
        // Get region by path
        $region = $this->regionService->getRegionByPath($slugs);
        
        if (!$region) {
            abort(404, 'Region not found');
        }

        // Get region data
        $regionData = $region->toFrontend();
        
        // Get featured entries
        $featuredEntries = $this->regionService->getFeaturedEntries($region, 12);
        
        // Get region statistics
        $stats = $this->regionService->getRegionStats($region);
        
        // Get child regions
        $childRegions = $this->regionService->getChildRegions($region);
        
        // Get breadcrumbs
        $breadcrumbs = $this->buildBreadcrumbs($region);

        return Inertia::render('Region/Show', [
            'region' => $regionData,
            'featuredEntries' => $featuredEntries->map(fn($entry) => [
                'id' => $entry->id,
                'title' => $entry->title,
                'slug' => $entry->slug,
                'description' => $entry->description,
                'type' => $entry->type,
                'featured_image' => $entry->featured_image,
                'category' => $entry->category ? [
                    'id' => $entry->category->id,
                    'name' => $entry->category->name,
                    'slug' => $entry->category->slug
                ] : null,
                'location' => $entry->location ? [
                    'address' => $entry->location->address,
                    'city' => $entry->location->city,
                    'state' => $entry->location->state
                ] : null,
                'url' => $entry->getRegionUrl(),
                'pivot' => [
                    'label' => $entry->pivot->label,
                    'tagline' => $entry->pivot->tagline
                ]
            ]),
            'stats' => $stats,
            'childRegions' => $childRegions->map(fn($region) => [
                'id' => $region->id,
                'name' => $region->name,
                'slug' => $region->slug,
                'type' => $region->type,
                'place_count' => $region->cached_place_count,
                'url' => '/' . $region->getUrlPath()
            ]),
            'breadcrumbs' => $breadcrumbs
        ]);
    }

    /**
     * Display region entries with pagination
     */
    public function entries($path)
    {
        // Convert path string to array of segments
        $slugs = explode('/', $path);
        
        // Get region by path
        $region = $this->regionService->getRegionByPath($slugs);
        
        if (!$region) {
            abort(404, 'Region not found');
        }

        // Get filters from request
        $filters = request()->only(['category_id', 'type', 'is_featured', 'is_verified', 'sort_by']);
        
        // Get paginated entries
        $entries = $this->regionService->getRegionEntries($region, 20, $filters);
        
        return Inertia::render('Region/Entries', [
            'region' => $region->toFrontend(),
            'entries' => $entries,
            'filters' => $filters,
            'breadcrumbs' => $this->buildBreadcrumbs($region)
        ]);
    }

    /**
     * API endpoint for featured regions
     */
    public function featured(Request $request)
    {
        $level = $request->input('level');
        $limit = $request->input('limit', 10);
        
        $regions = $this->regionService->getFeaturedRegions($level, $limit);
        
        return response()->json([
            'data' => $regions->map(fn($region) => $region->toFrontend())
        ]);
    }

    /**
     * API endpoint for region search
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2'
        ]);
        
        $search = $request->input('q');
        $level = $request->input('level');
        $limit = $request->input('limit', 10);
        
        $regions = $this->regionService->searchRegions($search, $level, $limit);
        
        return response()->json([
            'data' => $regions->map(fn($region) => [
                'id' => $region->id,
                'name' => $region->name,
                'slug' => $region->slug,
                'type' => $region->type,
                'level' => $region->level,
                'place_count' => $region->cached_place_count,
                'url' => '/' . $region->getUrlPath(),
                'parent' => $region->parent ? [
                    'name' => $region->parent->name,
                    'slug' => $region->parent->slug
                ] : null
            ])
        ]);
    }

    /**
     * Build breadcrumb navigation
     */
    protected function buildBreadcrumbs(Region $region)
    {
        $breadcrumbs = [];
        $path = $region->path;
        
        foreach ($path as $ancestor) {
            $breadcrumbs[] = [
                'name' => $ancestor->name,
                'url' => '/' . $ancestor->getUrlPath()
            ];
        }
        
        return $breadcrumbs;
    }
}