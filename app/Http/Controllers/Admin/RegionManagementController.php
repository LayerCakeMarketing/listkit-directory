<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Models\Place;
use App\Services\RegionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RegionManagementController extends Controller
{
    protected $regionService;

    public function __construct(RegionService $regionService)
    {
        $this->regionService = $regionService;
        // Remove middleware from constructor since it's already defined in routes
    }

    /**
     * Display list of regions for management
     */
    public function index(Request $request)
    {
        $query = Region::query()
            ->withCount(['stateEntries', 'cityEntries', 'neighborhoodEntries'])
            ->with('parent');

        // Filter by level
        if ($request->has('level')) {
            $query->where('level', $request->level);
        }

        // Filter by featured status
        if ($request->boolean('featured_only')) {
            $query->where('is_featured', true);
        }

        // Search
        if ($request->filled('search')) {
            $query->where('name', 'ILIKE', '%' . $request->search . '%');
        }

        // Sort
        $sortField = $request->get('sort_by', 'name');
        $sortDirection = $request->get('sort_direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        $regions = $query->paginate(20);

        return Inertia::render('Admin/Regions/Index', [
            'regions' => $regions,
            'filters' => $request->only(['level', 'featured_only', 'search', 'sort_by', 'sort_direction'])
        ]);
    }

    /**
     * Show form for creating a new region
     */
    public function create(Request $request)
    {
        // Get parent regions for dropdown
        $parentRegions = [];
        
        if ($request->has('level')) {
            $level = (int) $request->level;
            
            if ($level == 2) {
                // For cities, show states
                $parentRegions = Region::where('level', 1)->orderBy('name')->get();
            } elseif ($level == 3) {
                // For neighborhoods, show cities (optionally filtered by state)
                $query = Region::where('level', 2);
                
                if ($request->has('state_id')) {
                    $query->where('parent_id', $request->state_id);
                }
                
                $parentRegions = $query->orderBy('name')->get();
            }
        }

        return Inertia::render('Admin/Regions/Create', [
            'level' => (int) $request->get('level', 1),
            'parentRegions' => $parentRegions,
            'states' => Region::where('level', 1)->orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created region
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:state,city,county,neighborhood,district',
            'level' => 'required|integer|in:1,2,3',
            'parent_id' => 'nullable|exists:regions,id',
            'intro_text' => 'nullable|string',
            'cover_image' => 'nullable|string',
            'metadata' => 'nullable|array',
            // Polygon data
            'boundaries_geojson' => 'nullable|string',
            'boundaries_simplified_geojson' => 'nullable|string',
        ]);

        // Create the region
        $region = new Region($validated);
        
        // Set parent_id based on level
        if ($validated['level'] > 1 && !empty($validated['parent_id'])) {
            $region->parent_id = $validated['parent_id'];
        }

        // Save first to get ID
        $region->save();

        // Handle polygon boundaries if provided
        if (!empty($validated['boundaries_geojson'])) {
            $this->updateRegionBoundaries($region, $validated['boundaries_geojson'], $validated['boundaries_simplified_geojson'] ?? null);
        }

        return redirect()->route('admin.regions.edit', $region)
            ->with('success', 'Region created successfully');
    }

    /**
     * Show form for editing a region
     */
    public function edit(Region $region)
    {
        $region->load(['parent.parent', 'children', 'featuredEntries']);
        
        // Get available entries for featuring
        $availableEntries = Place::query()
            ->where(function ($query) use ($region) {
                $query->where('state_region_id', $region->id)
                      ->orWhere('city_region_id', $region->id)
                      ->orWhere('neighborhood_region_id', $region->id);
            })
            ->where('status', 'published')
            ->select('id', 'title', 'slug', 'type')
            ->get();

        // Get GeoJSON for the region if it has boundaries
        $boundaries = null;
        if ($region->boundaries) {
            $boundaries = $region->getGeoJson();
        }

        return Inertia::render('Admin/Regions/Edit', [
            'region' => $region,
            'availableEntries' => $availableEntries,
            'boundaries' => $boundaries,
            'featuredEntries' => $region->allFeaturedEntries->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'title' => $entry->title,
                    'priority' => $entry->pivot->priority,
                    'label' => $entry->pivot->label,
                    'tagline' => $entry->pivot->tagline,
                    'featured_until' => $entry->pivot->featured_until,
                    'is_active' => $entry->pivot->is_active,
                ];
            }),
            'boundaries' => $boundaries,
        ]);
    }

    /**
     * Update region metadata
     */
    public function update(Request $request, Region $region)
    {
        $validated = $request->validate([
            'intro_text' => 'nullable|string|max:5000',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'is_featured' => 'boolean',
            'display_priority' => 'integer',
            'data_points' => 'nullable|array',
            'data_points.*.key' => 'required|string',
            'data_points.*.value' => 'required',
            'data_points.*.label' => 'required|string',
            'custom_fields' => 'nullable|array',
            'cover_image' => 'nullable|string',
            'boundaries_geojson' => 'nullable|string'
        ]);

        // Transform data_points from array to key-value object
        if (isset($validated['data_points'])) {
            $dataPoints = [];
            foreach ($validated['data_points'] as $point) {
                $dataPoints[$point['key']] = $point['value'];
            }
            $validated['data_points'] = $dataPoints;
        }

        // Handle boundaries update if provided
        if (!empty($validated['boundaries_geojson'])) {
            try {
                $geojson = json_decode($validated['boundaries_geojson']);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $this->updateRegionBoundaries($region, $validated['boundaries_geojson']);
                }
            } catch (\Exception $e) {
                // If JSON is invalid, skip boundary update
            }
        }

        // Remove boundaries_geojson from the update array since it's handled separately
        unset($validated['boundaries_geojson']);

        // Log the validated data for debugging
        \Log::info('Updating region with data:', $validated);
        \Log::info('Request data:', $request->all());
        \Log::info('Cover image in request:', ['cover_image' => $request->input('cover_image')]);

        $region->update($validated);

        return redirect()->route('admin.regions.edit', $region)
            ->with('success', 'Region updated successfully');
    }

    /**
     * Update region boundaries
     */
    public function updateBoundaries(Request $request, Region $region)
    {
        $validated = $request->validate([
            'boundaries_geojson' => 'required|string',
            'boundaries_simplified_geojson' => 'nullable|string',
        ]);

        $this->updateRegionBoundaries($region, $validated['boundaries_geojson'], $validated['boundaries_simplified_geojson'] ?? null);

        return response()->json(['message' => 'Boundaries updated successfully']);
    }

    /**
     * Helper to update region boundaries using PostGIS
     */
    protected function updateRegionBoundaries(Region $region, $geojson, $simplifiedGeojson = null)
    {
        // Parse the GeoJSON to extract just the geometry
        $geojsonData = json_decode($geojson, true);
        
        // If it's a FeatureCollection, extract the first feature's geometry
        if (isset($geojsonData['type']) && $geojsonData['type'] === 'FeatureCollection' && !empty($geojsonData['features'])) {
            $geometry = $geojsonData['features'][0]['geometry'];
            $geojson = json_encode($geometry);
        }
        
        // Update main boundaries
        DB::statement(
            "UPDATE regions SET boundaries = ST_GeomFromGeoJSON(?), centroid = ST_Centroid(ST_GeomFromGeoJSON(?)) WHERE id = ?",
            [$geojson, $geojson, $region->id]
        );

        // Update simplified boundaries if provided
        if ($simplifiedGeojson) {
            DB::statement(
                "UPDATE regions SET boundaries_simplified = ST_GeomFromGeoJSON(?) WHERE id = ?",
                [$simplifiedGeojson, $region->id]
            );
        } else {
            // Auto-generate simplified version
            DB::statement(
                "UPDATE regions SET boundaries_simplified = ST_Simplify(boundaries, 0.001) WHERE id = ?",
                [$region->id]
            );
        }
    }

    /**
     * Upload cover image
     */
    public function uploadCoverImage(Request $request, Region $region)
    {
        $request->validate([
            'cover_image' => 'required|image|max:5120' // 5MB max
        ]);

        // Delete old image if exists
        if ($region->cover_image && !filter_var($region->cover_image, FILTER_VALIDATE_URL)) {
            Storage::disk('public')->delete($region->cover_image);
        }

        // Store new image
        $path = $request->file('cover_image')->store('regions', 'public');
        
        $region->update(['cover_image' => $path]);

        return response()->json([
            'url' => Storage::url($path),
            'path' => $path
        ]);
    }

    /**
     * Remove cover image
     */
    public function removeCoverImage(Region $region)
    {
        if ($region->cover_image && !filter_var($region->cover_image, FILTER_VALIDATE_URL)) {
            Storage::disk('public')->delete($region->cover_image);
        }

        $region->update(['cover_image' => null]);

        return response()->json(['message' => 'Cover image removed']);
    }

    /**
     * Update featured entries
     */
    public function updateFeaturedEntries(Request $request, Region $region)
    {
        $validated = $request->validate([
            'entries' => 'array',
            'entries.*.directory_entry_id' => 'required|exists:directory_entries,id',
            'entries.*.priority' => 'required|integer|min:0',
            'entries.*.label' => 'nullable|string|max:50',
            'entries.*.tagline' => 'nullable|string|max:255',
            'entries.*.featured_until' => 'nullable|date',
            'entries.*.is_active' => 'boolean'
        ]);

        $this->regionService->updateFeaturedEntries($region, $validated['entries'] ?? []);

        return response()->json(['message' => 'Featured entries updated successfully']);
    }

    /**
     * Bulk update regions
     */
    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'region_ids' => 'required|array',
            'region_ids.*' => 'exists:regions,id',
            'action' => 'required|in:feature,unfeature,clear_cache'
        ]);

        $regions = Region::whereIn('id', $validated['region_ids'])->get();

        switch ($validated['action']) {
            case 'feature':
                Region::whereIn('id', $validated['region_ids'])->update(['is_featured' => true]);
                break;
            
            case 'unfeature':
                Region::whereIn('id', $validated['region_ids'])->update(['is_featured' => false]);
                break;
            
            case 'clear_cache':
                foreach ($regions as $region) {
                    $region->clearCache();
                }
                break;
        }

        return redirect()->back()->with('success', 'Bulk action completed successfully');
    }

    /**
     * Delete a region
     */
    public function destroy(Region $region)
    {
        // Check if region has children
        if ($region->children()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete region with child regions');
        }

        // Check if region has entries
        $entryCount = Place::where('state_region_id', $region->id)
            ->orWhere('city_region_id', $region->id)
            ->orWhere('neighborhood_region_id', $region->id)
            ->count();

        if ($entryCount > 0) {
            return redirect()->back()->with('error', "Cannot delete region with {$entryCount} associated entries");
        }

        $region->delete();

        return redirect()->route('admin.regions.index')->with('success', 'Region deleted successfully');
    }

    /**
     * Preview region page
     */
    public function preview(Region $region)
    {
        // Use the same data as the public view but in preview mode
        $regionData = $region->toFrontend();
        $featuredEntries = $this->regionService->getFeaturedEntries($region, 12);
        $stats = $this->regionService->getRegionStats($region);
        $childRegions = $this->regionService->getChildRegions($region);
        $breadcrumbs = $this->buildBreadcrumbs($region);

        return Inertia::render('Admin/Regions/Preview', [
            'region' => $regionData,
            'featuredEntries' => $featuredEntries,
            'stats' => $stats,
            'childRegions' => $childRegions,
            'breadcrumbs' => $breadcrumbs,
            'isPreview' => true
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