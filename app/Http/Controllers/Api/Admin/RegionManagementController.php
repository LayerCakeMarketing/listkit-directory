<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Models\Place;
use App\Services\CloudflareImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class RegionManagementController extends Controller
{
    /**
     * Display a listing of regions.
     */
    public function index(Request $request)
    {
        // Base query with relationships
        $with = ['parent', 'children'];
        
        // Add additional relationships if requested
        if ($request->has('with')) {
            $requestedWith = explode(',', $request->with);
            foreach ($requestedWith as $relation) {
                // Handle nested parent relationship
                if ($relation === 'parent.parent') {
                    $with[] = 'parent.parent';
                } elseif ($relation === 'featuredEntries') {
                    $with[] = 'featuredEntries.category';
                } elseif ($relation === 'featuredLists') {
                    $with[] = 'featuredLists.user';
                } elseif (!in_array($relation, $with)) {
                    $with[] = $relation;
                }
            }
        }
        
        $query = Region::with($with)
            ->withCount([
                'children as children_count'
            ])
            ->select('regions.*');
            
        // Add custom place count based on region level
        $query->selectRaw("
            CASE 
                WHEN regions.level = 1 THEN (SELECT COUNT(*) FROM directory_entries WHERE directory_entries.state_region_id = regions.id AND directory_entries.status = 'published')
                WHEN regions.level = 2 THEN (SELECT COUNT(*) FROM directory_entries WHERE directory_entries.city_region_id = regions.id AND directory_entries.status = 'published')
                WHEN regions.level = 3 THEN (SELECT COUNT(*) FROM directory_entries WHERE directory_entries.neighborhood_region_id = regions.id AND directory_entries.status = 'published')
                ELSE 0
            END as entries_count
        ");

        // Search
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Filter by level
        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by parent
        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }

        // Filter by featured
        if ($request->filled('is_featured')) {
            $query->where('is_featured', true);
        }

        // Filter by has_content
        if ($request->filled('has_content')) {
            $query->where(function($q) {
                $q->whereNotNull('intro_text')
                  ->orWhereNotNull('cover_image');
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        
        switch ($sortBy) {
            case 'cached_place_count':
                $query->orderBy('cached_place_count', $sortOrder);
                break;
            case 'created_at':
                $query->orderBy('created_at', $sortOrder);
                break;
            case 'display_priority':
                $query->orderBy('display_priority', $sortOrder);
                break;
            default:
                $query->orderBy('name', $sortOrder);
        }

        // Get all or paginate
        if ($request->has('limit')) {
            $regions = $query->limit($request->limit)->get();
            return response()->json(['data' => $regions]);
        } else {
            $regions = $query->paginate($request->get('per_page', 15));
            // Return paginated data - Laravel's paginate() already returns the correct structure
            return $regions;
        }
    }

    /**
     * Get statistics for regions.
     */
    public function stats()
    {
        $stats = [
            'total' => Region::count(),
            'states' => Region::where('level', 1)->count(),
            'cities' => Region::where('level', 2)->count(),
            'neighborhoods' => Region::where('level', 3)->count(),
            'state_parks' => Region::where('level', 4)->count(),
            'national_parks' => Region::where('level', 5)->count(),
            'with_places' => Region::whereHas('directoryEntries', function($q) {
                $q->where('status', 'published');
            })->count(),
            'featured' => Region::where('is_featured', true)->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Store a newly created region.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:regions,slug',
            'type' => 'nullable|string|in:state,city,county,neighborhood,district,custom,national_park,state_park,regional_park,local_park',
            'level' => 'required|in:1,2,3,4,5',
            'parent_id' => 'nullable|exists:regions,id',
            'intro_text' => 'nullable|string',
            'is_featured' => 'boolean',
            'display_priority' => 'nullable|integer|min:0',
            'cover_image' => 'nullable|string',
            'cloudflare_image_id' => 'nullable|string',
            'designation' => 'nullable|string|in:resort_town,beach_town,ski_town,historic_town,college_town,arts_district,financial_district,tech_hub',
            'facts' => 'nullable|array',
            'state_symbols' => 'nullable|array',
            'geojson' => 'nullable|array',
            'polygon_coordinates' => 'nullable|string',
            // Park-specific fields
            'park_system' => 'nullable|string|max:255',
            'park_designation' => 'nullable|string|max:255',
            'area_acres' => 'nullable|numeric|min:0',
            'established_date' => 'nullable|date',
            'park_features' => 'nullable|array',
            'park_activities' => 'nullable|array',
            'park_website' => 'nullable|url',
            'park_phone' => 'nullable|string|max:20',
            'operating_hours' => 'nullable|array',
            'entrance_fees' => 'nullable|array',
            'reservations_required' => 'boolean',
            'difficulty_level' => 'nullable|string|in:easy,moderate,difficult,varies',
            'accessibility_features' => 'nullable|array',
        ]);

        // Validate parent relationship
        if ($validated['level'] > 1 && empty($validated['parent_id'])) {
            return response()->json([
                'message' => 'Parent region is required for non-state regions',
                'errors' => ['parent_id' => ['Parent region is required for non-state regions']]
            ], 422);
        }

        if (!empty($validated['parent_id'])) {
            $parent = Region::find($validated['parent_id']);
            
            // Special handling for parks - they can be at the same level as cities (under states)
            if (in_array($validated['level'], [4, 5])) {
                // Parks must have a state (level 1) as parent
                if ($parent->level != 1) {
                    return response()->json([
                        'message' => 'Parks must have a state as their parent region',
                        'errors' => ['parent_id' => ['Parks must have a state as their parent region']]
                    ], 422);
                }
            } else {
                // Regular hierarchy validation
                if ($parent->level >= $validated['level']) {
                    return response()->json([
                        'message' => 'Parent region must be of a higher level',
                        'errors' => ['parent_id' => ['Parent region must be of a higher level']]
                    ], 422);
                }
            }
        }

        // Auto-generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // Set type based on level if not explicitly provided
        if (empty($validated['type'])) {
            $validated['type'] = match($validated['level']) {
                1 => 'state',
                2 => 'city',
                3 => 'neighborhood',
                4 => 'state_park',
                5 => 'national_park',
                default => 'custom'
            };
        }

        $region = Region::create($validated);

        return response()->json($region->load('parent'), 201);
    }

    /**
     * Display the specified region.
     */
    public function show($id)
    {
        $region = Region::with(['parent', 'children', 'featuredEntries'])
            ->withCount('directoryEntries')
            ->findOrFail($id);

        return response()->json($region);
    }

    /**
     * Update the specified region.
     */
    public function update(Request $request, $id)
    {
        $region = Region::findOrFail($id);

        // Prepare validation rules
        $validationRules = [
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|string|in:state,city,county,neighborhood,district,custom,national_park,state_park,regional_park,local_park',
            'level' => 'sometimes|required|in:1,2,3,4,5',
            'parent_id' => 'nullable|exists:regions,id',
            'intro_text' => 'nullable|string',
            'is_featured' => 'boolean',
            'display_priority' => 'nullable|integer|min:0',
            'cover_image' => 'nullable|string', // Can be URL or file upload
            'cloudflare_image_id' => 'nullable|string',
            'designation' => 'nullable|string|in:resort_town,beach_town,ski_town,historic_town,college_town,arts_district,financial_district,tech_hub',
            'facts' => 'nullable|array',
            'state_symbols' => 'nullable|array',
            'geojson' => 'nullable|array',
            'polygon_coordinates' => 'nullable|string',
            // Park-specific fields
            'park_system' => 'nullable|string|max:255',
            'park_designation' => 'nullable|string|max:255',
            'area_acres' => 'nullable|numeric|min:0',
            'established_date' => 'nullable|date',
            'park_features' => 'nullable|array',
            'park_activities' => 'nullable|array',
            'park_website' => 'nullable|url',
            'park_phone' => 'nullable|string|max:20',
            'operating_hours' => 'nullable|array',
            'entrance_fees' => 'nullable|array',
            'reservations_required' => 'boolean',
            'difficulty_level' => 'nullable|string|in:easy,moderate,difficult,varies',
            'accessibility_features' => 'nullable|array',
        ];

        // Handle slug validation conditionally
        if ($request->has('slug')) {
            $requestedSlug = $request->input('slug');
            
            // Only apply unique validation if the slug is actually being changed
            if ($requestedSlug !== $region->slug) {
                $validationRules['slug'] = 'required|string|max:255|unique:regions,slug,' . $id;
            } else {
                // If slug is the same, just validate format but skip uniqueness
                $validationRules['slug'] = 'required|string|max:255';
            }
        }

        $validated = $request->validate($validationRules);

        // Validate parent relationship if level is being changed
        if (isset($validated['level']) && $validated['level'] > 1 && empty($validated['parent_id']) && empty($region->parent_id)) {
            return response()->json([
                'message' => 'Parent region is required for non-state regions',
                'errors' => ['parent_id' => ['Parent region is required for non-state regions']]
            ], 422);
        }

        if (!empty($validated['parent_id'])) {
            $parent = Region::find($validated['parent_id']);
            $level = $validated['level'] ?? $region->level;
            
            // Special handling for parks - they can be at the same level as cities (under states)
            if (in_array($level, [4, 5])) {
                // Parks must have a state (level 1) as parent
                if ($parent->level != 1) {
                    return response()->json([
                        'message' => 'Parks must have a state as their parent region',
                        'errors' => ['parent_id' => ['Parks must have a state as their parent region']]
                    ], 422);
                }
            } else {
                // Regular hierarchy validation
                if ($parent->level >= $level) {
                    return response()->json([
                        'message' => 'Parent region must be of a higher level',
                        'errors' => ['parent_id' => ['Parent region must be of a higher level']]
                    ], 422);
                }
            }
        }

        // Update type if level is changed and type is not explicitly provided
        if (isset($validated['level']) && !isset($validated['type'])) {
            $validated['type'] = match($validated['level']) {
                1 => 'state',
                2 => 'city',
                3 => 'neighborhood',
                4 => 'state_park',
                5 => 'national_park',
                default => 'custom'
            };
        }

        // No need for image upload handling since CloudflareDragDropUploader handles it directly
        // Images are uploaded via the frontend component and we just receive the cloudflare_image_id

        // Log the cloudflare_image_id for debugging
        if (isset($validated['cloudflare_image_id'])) {
            \Log::info('Updating region cloudflare_image_id', [
                'region_id' => $region->id,
                'cloudflare_image_id' => $validated['cloudflare_image_id']
            ]);
        }
        
        // Log state symbols for debugging
        if (isset($validated['state_symbols'])) {
            \Log::info('Updating region state_symbols', [
                'region_id' => $region->id,
                'state_symbols' => $validated['state_symbols']
            ]);
        }

        $region->update($validated);
        
        // Refresh the model to get the latest data including casts
        $region->refresh();
        
        // Clear the cache for this region
        \Cache::forget("region.by-slug.{$region->slug}");
        \Cache::forget("region.{$region->id}");
        
        // Also clear any parent region caches if this is a child
        if ($region->parent) {
            \Cache::forget("region.{$region->parent->id}.children");
        }

        return response()->json($region->load('parent'));
    }

    /**
     * Remove the specified region.
     */
    public function destroy($id)
    {
        $region = Region::findOrFail($id);

        // Check if region has children
        if ($region->children()->count() > 0) {
            return response()->json(['error' => 'Cannot delete region with child regions'], 422);
        }

        // Check if region has entries based on the region level
        $entryCount = 0;
        switch ($region->level) {
            case 1: // State
                $entryCount = Place::where('state_region_id', $region->id)->count();
                break;
            case 2: // City
                $entryCount = Place::where('city_region_id', $region->id)->count();
                break;
            case 3: // Neighborhood
                $entryCount = Place::where('neighborhood_region_id', $region->id)->count();
                break;
        }
        
        // Also check old region_id field for backward compatibility
        $oldEntryCount = Place::where('region_id', $region->id)->count();
        $totalEntryCount = $entryCount + $oldEntryCount;

        if ($totalEntryCount > 0) {
            return response()->json([
                'error' => "Cannot delete region with {$totalEntryCount} associated entries. Please reassign or delete these entries first.",
                'message' => "Cannot delete region with {$totalEntryCount} associated entries. Please reassign or delete these entries first."
            ], 422);
        }

        $region->delete();

        return response()->json(['message' => 'Region deleted successfully']);
    }

    /**
     * Manage featured entries for a region.
     */
    public function manageFeatured(Request $request, $id)
    {
        $region = Region::findOrFail($id);

        $validated = $request->validate([
            'entries' => 'required|array',
            'entries.*.directory_entry_id' => 'required|exists:directory_entries,id',
            'entries.*.priority' => 'required|integer|min:0',
            'entries.*.label' => 'nullable|string|max:255',
            'entries.*.tagline' => 'nullable|string|max:500',
            'entries.*.featured_until' => 'nullable|date',
            'entries.*.is_active' => 'boolean',
        ]);

        // Clear existing featured entries
        $region->featuredEntries()->detach();

        // Add new featured entries
        foreach ($validated['entries'] as $entry) {
            $region->featuredEntries()->attach($entry['directory_entry_id'], [
                'priority' => $entry['priority'],
                'label' => $entry['label'] ?? null,
                'tagline' => $entry['tagline'] ?? null,
                'featured_until' => $entry['featured_until'] ?? null,
                'is_active' => $entry['is_active'] ?? true,
            ]);
        }

        return response()->json(['message' => 'Featured entries updated successfully']);
    }

    /**
     * Assign entries to a region.
     */
    public function assignEntries(Request $request, $id)
    {
        $region = Region::findOrFail($id);

        $validated = $request->validate([
            'entry_ids' => 'required|array',
            'entry_ids.*' => 'required|exists:directory_entries,id',
        ]);

        // Update entries based on region level
        $updateData = match($region->level) {
            1 => ['state_region_id' => $region->id],
            2 => ['city_region_id' => $region->id],
            3 => ['neighborhood_region_id' => $region->id],
            default => []
        };

        if (!empty($updateData)) {
            Place::whereIn('id', $validated['entry_ids'])->update($updateData);
        }

        return response()->json(['message' => 'Entries assigned successfully']);
    }

    /**
     * Get places associated with a region
     */
    public function places(Request $request, $id)
    {
        $region = Region::findOrFail($id);
        
        $query = Place::with(['category', 'location', 'stateRegion', 'cityRegion', 'neighborhoodRegion']);
        
        // Filter by region based on level
        switch ($region->level) {
            case 1: // State
                $query->where('state_region_id', $region->id);
                break;
            case 2: // City
                $query->where('city_region_id', $region->id);
                break;
            case 3: // Neighborhood
                $query->where('neighborhood_region_id', $region->id);
                break;
        }
        
        // Also include old region_id for backward compatibility
        $query->orWhere('region_id', $region->id);
        
        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        // Sorting
        $sortBy = $request->get('sort_by', 'title');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);
        
        $places = $query->paginate($request->get('per_page', 20));
        
        return response()->json($places);
    }

    /**
     * Remove a place from a region
     */
    public function removePlace(Request $request, $regionId, $placeId)
    {
        $region = Region::findOrFail($regionId);
        $place = Place::findOrFail($placeId);
        
        // Remove association based on region level
        $updateData = match($region->level) {
            1 => ['state_region_id' => null],
            2 => ['city_region_id' => null],
            3 => ['neighborhood_region_id' => null],
            default => []
        };
        
        if (!empty($updateData)) {
            // Only update if the place is actually associated with this region
            $shouldUpdate = match($region->level) {
                1 => $place->state_region_id == $region->id,
                2 => $place->city_region_id == $region->id,
                3 => $place->neighborhood_region_id == $region->id,
                default => false
            };
            
            if ($shouldUpdate) {
                $place->update($updateData);
                return response()->json(['message' => 'Place removed from region successfully']);
            }
        }
        
        // Also check old region_id
        if ($place->region_id == $region->id) {
            $place->update(['region_id' => null]);
            return response()->json(['message' => 'Place removed from region successfully']);
        }
        
        return response()->json(['error' => 'Place is not associated with this region'], 422);
    }
    
    /**
     * Add a featured place to a region
     */
    public function addFeaturedPlace(Request $request, $regionId)
    {
        $region = Region::findOrFail($regionId);
        
        $validated = $request->validate([
            'place_id' => 'required|exists:directory_entries,id',
            'priority' => 'nullable|integer|min:0|max:100',
            'label' => 'nullable|string|max:255',
            'tagline' => 'nullable|string|max:500',
            'featured_until' => 'nullable|date',
            'category_id' => 'nullable|exists:categories,id'
        ]);
        
        // Check if already featured
        if ($region->featuredEntries()->where('directory_entry_id', $validated['place_id'])->exists()) {
            return response()->json(['error' => 'Place is already featured in this region'], 422);
        }
        
        // Add as featured
        $pivotData = [
            'priority' => $validated['priority'] ?? 0,
            'label' => $validated['label'] ?? null,
            'tagline' => $validated['tagline'] ?? null,
            'featured_until' => $validated['featured_until'] ?? null,
            'is_active' => true,
        ];
        
        // If category_id is provided, store it in custom_data
        if (!empty($validated['category_id'])) {
            $pivotData['custom_data'] = json_encode(['category_id' => $validated['category_id']]);
        }
        
        $region->featuredEntries()->attach($validated['place_id'], $pivotData);
        
        return response()->json(['message' => 'Place added as featured successfully']);
    }
    
    /**
     * Update a featured place's priority
     */
    public function updateFeaturedPlace(Request $request, $regionId, $placeId)
    {
        $region = Region::findOrFail($regionId);
        
        $validated = $request->validate([
            'priority' => 'required|integer|min:0|max:100',
            'label' => 'nullable|string|max:255',
            'tagline' => 'nullable|string|max:500',
            'featured_until' => 'nullable|date',
        ]);
        
        $region->featuredEntries()->updateExistingPivot($placeId, $validated);
        
        return response()->json(['message' => 'Featured place updated successfully']);
    }
    
    /**
     * Remove a featured place from a region
     */
    public function removeFeaturedPlace($regionId, $placeId)
    {
        $region = Region::findOrFail($regionId);
        $region->featuredEntries()->detach($placeId);
        
        return response()->json(['message' => 'Featured place removed successfully']);
    }
    
    /**
     * Add a featured list to a region
     */
    public function addFeaturedList(Request $request, $regionId)
    {
        $region = Region::findOrFail($regionId);
        
        $validated = $request->validate([
            'list_id' => 'required|exists:lists,id',
            'priority' => 'nullable|integer|min:0|max:100',
            'is_active' => 'nullable|boolean'
        ]);
        
        // Check if already featured
        if ($region->featuredLists()->where('list_id', $validated['list_id'])->exists()) {
            return response()->json(['error' => 'List is already featured in this region'], 422);
        }
        
        // Add as featured
        $region->featuredLists()->attach($validated['list_id'], [
            'priority' => $validated['priority'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);
        
        return response()->json(['message' => 'List added as featured successfully']);
    }
    
    /**
     * Remove a featured list from a region
     */
    public function removeFeaturedList($regionId, $listId)
    {
        $region = Region::findOrFail($regionId);
        $region->featuredLists()->detach($listId);
        
        return response()->json(['message' => 'Featured list removed successfully']);
    }
    
    /**
     * Get duplicate regions
     */
    public function duplicates(Request $request)
    {
        // Find duplicates
        $duplicates = Region::select('name', 'type', 'parent_id', DB::raw('COUNT(*) as duplicate_count'))
            ->groupBy('name', 'type', 'parent_id')
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('type')
            ->orderBy('name')
            ->get();
            
        $results = [];
        
        foreach ($duplicates as $duplicate) {
            // Get all regions matching this duplicate
            $regions = Region::where('name', $duplicate->name)
                ->where('type', $duplicate->type)
                ->when($duplicate->parent_id, function($query) use ($duplicate) {
                    return $query->where('parent_id', $duplicate->parent_id);
                }, function($query) {
                    return $query->whereNull('parent_id');
                })
                ->with(['parent', 'children'])
                ->withCount([
                    'stateEntries as state_places_count',
                    'cityEntries as city_places_count', 
                    'neighborhoodEntries as neighborhood_places_count',
                    'children'
                ])
                ->get()
                ->map(function($region) {
                    $region->total_places = $region->state_places_count + 
                                           $region->city_places_count + 
                                           $region->neighborhood_places_count;
                    return $region;
                });
                
            $results[] = [
                'name' => $duplicate->name,
                'type' => $duplicate->type,
                'count' => $duplicate->duplicate_count,
                'regions' => $regions
            ];
        }
        
        return response()->json(['duplicates' => $results]);
    }
    
    /**
     * Merge duplicate regions
     */
    public function mergeDuplicates(Request $request)
    {
        $validated = $request->validate([
            'keep_id' => 'required|exists:regions,id',
            'merge_ids' => 'required|array',
            'merge_ids.*' => 'exists:regions,id'
        ]);
        
        DB::beginTransaction();
        
        try {
            $keepRegion = Region::findOrFail($validated['keep_id']);
            $mergeRegions = Region::whereIn('id', $validated['merge_ids'])->get();
            
            foreach ($mergeRegions as $mergeRegion) {
                // Update places that reference this region
                Place::where('state_region_id', $mergeRegion->id)
                    ->update(['state_region_id' => $keepRegion->id]);
                    
                Place::where('city_region_id', $mergeRegion->id)
                    ->update(['city_region_id' => $keepRegion->id]);
                    
                Place::where('neighborhood_region_id', $mergeRegion->id)
                    ->update(['neighborhood_region_id' => $keepRegion->id]);
                
                // Update child regions
                Region::where('parent_id', $mergeRegion->id)
                    ->update(['parent_id' => $keepRegion->id]);
                
                // Update place_regions pivot table
                DB::table('place_regions')
                    ->where('region_id', $mergeRegion->id)
                    ->update(['region_id' => $keepRegion->id]);
                
                // Update featured places
                DB::table('region_featured_entries')
                    ->where('region_id', $mergeRegion->id)
                    ->update(['region_id' => $keepRegion->id]);
                
                // Delete the duplicate region
                $mergeRegion->delete();
            }
            
            DB::commit();
            
            return response()->json([
                'message' => 'Regions merged successfully',
                'kept_region' => $keepRegion->fresh()->load(['parent', 'children'])
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to merge regions: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get featured regions for a specific region.
     */
    public function getFeaturedRegions($id)
    {
        $region = Region::with(['allFeaturedRegions.parent'])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'featured_regions' => $region->allFeaturedRegions->map(function ($featuredRegion) {
                return [
                    'id' => $featuredRegion->id,
                    'name' => $featuredRegion->name,
                    'full_name' => $featuredRegion->full_name,
                    'slug' => $featuredRegion->slug,
                    'type' => $featuredRegion->type,
                    'level' => $featuredRegion->level,
                    'cover_image_url' => $featuredRegion->getCoverImageUrl(),
                    'intro_text' => $featuredRegion->intro_text,
                    'cached_place_count' => $featuredRegion->cached_place_count,
                    'parent' => $featuredRegion->parent ? [
                        'id' => $featuredRegion->parent->id,
                        'name' => $featuredRegion->parent->name,
                        'slug' => $featuredRegion->parent->slug,
                    ] : null,
                    'center_point' => $featuredRegion->center_point,
                    'pivot' => [
                        'display_order' => $featuredRegion->pivot->display_order,
                        'is_active' => $featuredRegion->pivot->is_active,
                        'label' => $featuredRegion->pivot->label,
                        'description' => $featuredRegion->pivot->description,
                        'featured_until' => $featuredRegion->pivot->featured_until,
                    ]
                ];
            })
        ]);
    }

    /**
     * Add a featured region.
     */
    public function addFeaturedRegion(Request $request, $id)
    {
        $request->validate([
            'featured_region_id' => 'required|exists:regions,id',
            'label' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'display_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'featured_until' => 'nullable|date',
        ]);

        $region = Region::findOrFail($id);
        $featuredRegionId = $request->featured_region_id;

        // Prevent featuring the same region or parent regions
        if ($featuredRegionId == $id) {
            return response()->json([
                'success' => false,
                'message' => 'A region cannot feature itself.'
            ], 422);
        }

        // Check if it's already featured
        if ($region->allFeaturedRegions()->where('featured_region_id', $featuredRegionId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This region is already featured.'
            ], 422);
        }

        // Get the next display order
        $maxOrder = $region->allFeaturedRegions()->max('display_order') ?? -1;

        // Attach the featured region
        $region->allFeaturedRegions()->attach($featuredRegionId, [
            'label' => $request->label,
            'description' => $request->description,
            'display_order' => $request->display_order ?? ($maxOrder + 1),
            'is_active' => $request->is_active ?? true,
            'featured_until' => $request->featured_until,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Region featured successfully.'
        ]);
    }

    /**
     * Update a featured region.
     */
    public function updateFeaturedRegion(Request $request, $id, $featuredRegionId)
    {
        $request->validate([
            'label' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'display_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'featured_until' => 'nullable|date',
        ]);

        $region = Region::findOrFail($id);

        $region->allFeaturedRegions()->updateExistingPivot($featuredRegionId, [
            'label' => $request->label,
            'description' => $request->description,
            'display_order' => $request->display_order,
            'is_active' => $request->is_active,
            'featured_until' => $request->featured_until,
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Featured region updated successfully.'
        ]);
    }

    /**
     * Remove a featured region.
     */
    public function removeFeaturedRegion($id, $featuredRegionId)
    {
        $region = Region::findOrFail($id);
        $region->allFeaturedRegions()->detach($featuredRegionId);

        return response()->json([
            'success' => true,
            'message' => 'Featured region removed successfully.'
        ]);
    }

    /**
     * Update the order of featured regions.
     */
    public function updateFeaturedRegionsOrder(Request $request, $id)
    {
        $request->validate([
            'regions' => 'required|array',
            'regions.*.id' => 'required|exists:regions,id',
            'regions.*.display_order' => 'required|integer',
        ]);

        $region = Region::findOrFail($id);

        foreach ($request->regions as $featuredRegion) {
            $region->allFeaturedRegions()->updateExistingPivot($featuredRegion['id'], [
                'display_order' => $featuredRegion['display_order'],
                'updated_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Featured regions order updated successfully.'
        ]);
    }

    /**
     * Search for regions to feature (excluding current region and its parents).
     */
    public function searchRegionsToFeature(Request $request, $id)
    {
        $request->validate([
            'search' => 'nullable|string',
            'type' => 'nullable|string',
        ]);

        $currentRegion = Region::with('parent.parent.parent')->findOrFail($id);
        
        // Get parent IDs to exclude
        $excludeIds = [$id];
        $parent = $currentRegion->parent;
        while ($parent) {
            $excludeIds[] = $parent->id;
            $parent = $parent->parent;
        }

        $query = Region::whereNotIn('id', $excludeIds)
            ->select('id', 'name', 'full_name', 'slug', 'type', 'level', 'parent_id', 'cached_place_count', 'center_point');

        if ($request->filled('search')) {
            $query->where('name', 'ilike', '%' . $request->search . '%');
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $regions = $query->with('parent:id,name,slug')
            ->orderBy('cached_place_count', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'regions' => $regions->map(function ($region) {
                return [
                    'id' => $region->id,
                    'name' => $region->name,
                    'full_name' => $region->full_name ?: $region->name,
                    'slug' => $region->slug,
                    'type' => $region->type,
                    'level' => $region->level,
                    'place_count' => $region->cached_place_count,
                    'parent_name' => $region->parent ? $region->parent->name : null,
                    'has_coordinates' => !empty($region->center_point),
                ];
            })
        ]);
    }
}