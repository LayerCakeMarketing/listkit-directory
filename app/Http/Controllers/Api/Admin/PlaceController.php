<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Place;
use App\Models\Location;
use App\Services\PlaceRegionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlaceController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:255',
            'type' => 'nullable|in:business_b2b,business_b2c,religious_org,point_of_interest,area_of_interest,service,online',
            'category_id' => 'nullable|exists:categories,id',
            'status' => 'nullable|in:draft,pending_review,published,archived',
            'sort_by' => 'nullable|in:title,created_at,updated_at,status,state,city,neighborhood,view_count,list_count',
            'sort_order' => 'nullable|in:asc,desc',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Place::with(['category', 'region', 'location', 'createdBy', 'owner', 'stateRegion', 'cityRegion', 'neighborhoodRegion']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('location', function ($q) use ($search) {
                    $q->where('city', 'like', "%{$search}%")
                      ->orWhere('state', 'like', "%{$search}%");
                  });
            });
        }

        // Filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Add special filters
        if ($request->filled('is_featured')) {
            $query->where('is_featured', true);
        }
        
        if ($request->filled('is_verified')) {
            $query->where('is_verified', true);
        }
        
        if ($request->filled('missing_regions')) {
            $query->where(function($q) {
                $q->whereNull('state_region_id')
                  ->orWhereNull('city_region_id');
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Handle location-based sorting
        if (in_array($sortBy, ['state', 'city', 'neighborhood'])) {
            $query->leftJoin('locations', 'directory_entries.id', '=', 'locations.directory_entry_id')
                  ->orderBy('locations.' . $sortBy, $sortOrder)
                  ->select('directory_entries.*');
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $entries = $query->paginate($request->get('limit', 20));
        
        // Transform the data to include canonical URL
        $entries->getCollection()->transform(function ($place) {
            $data = $place->toArray();
            $data['canonical_url'] = $place->getCanonicalUrl();
            $data['short_url'] = $place->getShortUrl();
            $data['has_regions'] = !empty($place->state_region_id) && !empty($place->city_region_id);
            return $data;
        });

        return response()->json($entries);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:business_b2b,business_b2c,religious_org,point_of_interest,area_of_interest,service,online',
            'category_id' => 'required|exists:categories,id',
            'region_id' => 'nullable|exists:regions,id',
            'tags' => 'nullable|array',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website_url' => 'nullable|url|max:255',
            'social_links' => 'nullable|array',
            'status' => 'nullable|in:draft,pending_review,published',
            
            // Location data (if physical location)
            'location' => 'nullable|array',
            'location.address_line1' => 'required_with:location|string|max:255',
            'location.city' => 'required_with:location|string|max:255',
            'location.state' => 'required_with:location|string|max:2',
            'location.zip_code' => 'required_with:location|string|max:10',
            'location.latitude' => 'required_with:location|numeric|between:-90,90',
            'location.longitude' => 'required_with:location|numeric|between:-180,180',
            'location.hours_of_operation' => 'nullable|array',
            'location.amenities' => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            // Create directory entry
            $entry = Place::create([
                'title' => $validated['title'],
                'slug' => Str::slug($validated['title']),
                'description' => $validated['description'] ?? null,
                'type' => $validated['type'],
                'category_id' => $validated['category_id'],
                'region_id' => $validated['region_id'] ?? null,
                'tags' => $validated['tags'] ?? [],
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'] ?? null,
                'website_url' => $validated['website_url'] ?? null,
                'social_links' => $validated['social_links'] ?? [],
                'created_by_user_id' => auth()->id(),
                'status' => $validated['status'] ?? (auth()->user()->canPublishContent() ? 'published' : 'pending_review'),
                'published_at' => ($validated['status'] ?? 'published') === 'published' ? now() : null,
            ]);

            // Create location if provided
            if ($request->filled('location') && in_array($entry->type, ['business_b2b', 'business_b2c', 'religious_org', 'point_of_interest', 'area_of_interest'])) {
                $location = Location::create([
                    'directory_entry_id' => $entry->id,
                    ...$request->location,
                ]);
                
                // Associate with regions
                $placeRegionService = app(PlaceRegionService::class);
                $placeRegionService->associatePlaceWithRegions($entry->fresh(['location']));
            }

            DB::commit();

            return response()->json([
                'message' => 'Directory entry created successfully',
                'entry' => $entry->load(['location', 'category'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create entry: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, Place $place)
    {
        // Check permissions
        if (!auth()->user()->canManageContent() && $place->created_by_user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|required|in:business_b2b,business_b2c,religious_org,point_of_interest,area_of_interest,service,online',
            'category_id' => 'sometimes|required|exists:categories,id',
            'status' => 'sometimes|required|in:draft,pending_review,published,archived',
            // ... other fields
        ]);

        DB::beginTransaction();
        try {
            if (isset($validated['title']) && $validated['title'] !== $place->title) {
                $validated['slug'] = Str::slug($validated['title']);
            }

            $place->update($validated);

            // Update location if provided
            if ($request->has('location') && $place->hasPhysicalLocation()) {
                if ($place->location) {
                    $place->location->update($request->location);
                } else {
                    Location::create([
                        'directory_entry_id' => $place->id,
                        ...$request->location,
                    ]);
                }
                
                // Re-associate with regions
                $placeRegionService = app(PlaceRegionService::class);
                $placeRegionService->associatePlaceWithRegions($place->fresh(['location']));
            }

            DB::commit();

            return response()->json([
                'message' => 'Directory entry updated successfully',
                'entry' => $place->fresh()->load(['location', 'category'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to update entry: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(Place $place)
    {
        if (!auth()->user()->canManageContent()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $place->delete();

        return response()->json(['message' => 'Directory entry deleted successfully']);
    }

    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'entry_ids' => 'required|array',
            'entry_ids.*' => 'exists:directory_entries,id',
            'action' => 'required|in:publish,archive,delete,update_status,toggle_featured,update_regions',
            'status' => 'nullable|in:published,draft,pending_review,archived',
        ]);

        if (!auth()->user()->canManageContent()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $entries = Place::whereIn('id', $validated['entry_ids']);

        switch ($validated['action']) {
            case 'publish':
                $entries->update(['status' => 'published', 'published_at' => now()]);
                break;
            case 'archive':
                $entries->update(['status' => 'archived']);
                break;
            case 'delete':
                $entries->delete();
                break;
            case 'update_status':
                if ($validated['status']) {
                    $updateData = ['status' => $validated['status']];
                    if ($validated['status'] === 'published') {
                        $updateData['published_at'] = now();
                    }
                    $entries->update($updateData);
                }
                break;
            case 'toggle_featured':
                // Toggle featured status for each entry
                foreach ($entries->get() as $entry) {
                    $entry->update(['is_featured' => !$entry->is_featured]);
                }
                break;
            case 'update_regions':
                // Update regions for places with location data
                $updated = 0;
                $placeRegionService = app(PlaceRegionService::class);
                foreach ($entries->with('location')->get() as $place) {
                    if ($place->location) {
                        try {
                            $placeRegionService->associatePlaceWithRegions($place);
                            $updated++;
                        } catch (\Exception $e) {
                            \Log::error('Failed to update regions for place', [
                                'place_id' => $place->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }
                return response()->json([
                    'message' => 'Regions updated successfully',
                    'updated' => $updated
                ]);
        }

        return response()->json(['message' => 'Bulk action completed successfully']);
    }

    public function show(Place $place)
    {
        // Load relationships needed for editing
        $place->load(['category.parent', 'location', 'stateRegion', 'cityRegion', 'neighborhoodRegion', 'createdBy', 'owner']);

        return response()->json($place);
    }

    public function stats()
    {
        $stats = [
            'total' => Place::count(),
            'published' => Place::where('status', 'published')->count(),
            'draft' => Place::where('status', 'draft')->count(),
            'pending_review' => Place::where('status', 'pending_review')->count(),
            'featured' => Place::where('is_featured', true)->count(),
            'verified' => Place::where('is_verified', true)->count(),
            'with_regions' => Place::whereNotNull('state_region_id')
                                   ->whereNotNull('city_region_id')
                                   ->count(),
            'missing_regions' => Place::where(function($q) {
                                    $q->whereNull('state_region_id')
                                      ->orWhereNull('city_region_id');
                                 })->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Update canonical URLs for all places missing regions
     */
    public function updateCanonicalUrls(Request $request)
    {
        if (!auth()->user()->canManageContent()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $places = Place::with(['location', 'category'])
            ->where(function($q) {
                $q->whereNull('state_region_id')
                  ->orWhereNull('city_region_id');
            })
            ->whereHas('location')
            ->get();

        $updated = 0;
        $errors = 0;
        $placeRegionService = app(PlaceRegionService::class);

        foreach ($places as $place) {
            try {
                $placeRegionService->associatePlaceWithRegions($place);
                $updated++;
            } catch (\Exception $e) {
                \Log::error('Error updating place regions', [
                    'place_id' => $place->id,
                    'error' => $e->getMessage()
                ]);
                $errors++;
            }
        }

        return response()->json([
            'message' => 'Canonical URLs updated',
            'updated' => $updated,
            'errors' => $errors,
            'total' => $places->count()
        ]);
    }

}