<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Place;
use App\Models\Location;
use App\Helpers\StateHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class PlaceController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'type' => 'nullable|in:business_b2b,business_b2c,religious_org,point_of_interest,area_of_interest,service,online',
            'category_id' => 'nullable|exists:categories,id',
            'region_id' => 'nullable|exists:regions,id',
            'status' => 'nullable|in:published,draft,pending_review',
            'q' => 'nullable|string|max:255',
            // Geospatial parameters
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:1|max:100',
            'bounds' => 'nullable|array',
            'bounds.north' => 'required_with:bounds|numeric|between:-90,90',
            'bounds.south' => 'required_with:bounds|numeric|between:-90,90',
            'bounds.east' => 'required_with:bounds|numeric|between:-180,180',
            'bounds.west' => 'required_with:bounds|numeric|between:-180,180',
        ]);

        $query = Place::with(['category', 'region', 'location', 'stateRegion', 'cityRegion', 'neighborhoodRegion']);

        // Geospatial filtering
        if ($request->filled(['lat', 'lng'])) {
            $query->select('directory_entries.*')
                ->selectDistanceTo($request->lat, $request->lng);
            
            if ($request->filled('radius')) {
                $query->withinRadius($request->lat, $request->lng, $request->radius);
            }
            
            $query->orderByDistance($request->lat, $request->lng);
        } elseif ($request->filled('bounds')) {
            $query->withinBoundingBox(
                $request->bounds['north'],
                $request->bounds['south'],
                $request->bounds['east'],
                $request->bounds['west']
            );
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->ofType($request->type);
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by region with hierarchical support
        if ($request->filled('region_id')) {
            $region = \App\Models\Region::find($request->region_id);
            if ($region) {
                // If include_nearby is set and region has coordinates, use radius search
                if ($request->boolean('include_nearby') && $region->lat && $region->lng) {
                    // Use 20 mile radius (configurable)
                    $radiusMiles = $request->input('nearby_radius', 20);
                    
                    $query->select('directory_entries.*')
                        ->selectDistanceTo($region->lat, $region->lng)
                        ->withinRadius($region->lat, $region->lng, $radiusMiles)
                        ->orderByDistance($region->lat, $region->lng);
                } else {
                    // Standard hierarchical filtering
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
                        default:
                            // Fallback to old region_id field
                            $query->where('region_id', $request->region_id);
                    }
                }
            }
        }

        // Filter by status (for admins/editors)
        if ($request->filled('status') && auth()->user()?->canManageContent()) {
            $query->where('status', $request->status);
        } else {
            // Regular users only see published entries
            $query->published();
        }

        // Search
        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->q}%")
                  ->orWhere('description', 'like', "%{$request->q}%");
            });
        }

        $entries = $query->paginate(20);

        // Get categories for filtering with location-aware counts
        $categoriesQuery = \App\Models\Category::whereNull('parent_id')
            ->with(['children']);
        
        // Add location-aware count based on the same filters
        if ($request->filled('region_id')) {
            $region = \App\Models\Region::find($request->region_id);
            if ($region) {
                if ($request->boolean('include_nearby') && $region->lat && $region->lng) {
                    // Count with radius
                    $radiusMiles = $request->input('nearby_radius', 20);
                    $categoriesQuery->withCount(['directoryEntries' => function ($q) use ($region, $radiusMiles) {
                        $q->where('status', 'published')
                          ->withinRadius($region->lat, $region->lng, $radiusMiles);
                    }]);
                } else {
                    // Count with hierarchical filtering
                    $categoriesQuery->withCount(['directoryEntries' => function ($q) use ($region) {
                        $q->where('status', 'published');
                        switch ($region->level) {
                            case 1: // State
                                $q->where('state_region_id', $region->id);
                                break;
                            case 2: // City
                                $q->where('city_region_id', $region->id);
                                break;
                            case 3: // Neighborhood
                                $q->where('neighborhood_region_id', $region->id);
                                break;
                        }
                    }]);
                }
            } else {
                // Fallback to global count if region not found
                $categoriesQuery->withCount(['directoryEntries' => function ($q) {
                    $q->where('status', 'published');
                }]);
            }
        } else {
            // No region filter, show global counts
            $categoriesQuery->withCount(['directoryEntries' => function ($q) {
                $q->where('status', 'published');
            }]);
        }
        
        $categories = $categoriesQuery->get();

        return response()->json([
            'entries' => $entries,
            'categories' => $categories
        ]);
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
            'links' => 'nullable|array',
            'links.*.text' => 'required|string|max:100',
            'links.*.url' => 'required|url|max:500',
            'social_links' => 'nullable|array',
            'logo_url' => 'nullable|string|max:255',
            'cover_image_url' => 'nullable|string|max:255',
            'gallery_images' => 'nullable|array',
            
            // Location data (required for all types)
            'location' => 'required|array',
            'location.address_line1' => 'nullable|required_if:type,business_b2b,business_b2c,religious_org,point_of_interest,area_of_interest|string|max:255',
            'location.city' => 'required|string|max:255',
            'location.state' => 'required|string|max:2',
            'location.zip_code' => 'nullable|required_if:type,business_b2b,business_b2c,religious_org,point_of_interest,area_of_interest|string|max:10',
            'location.neighborhood' => 'nullable|string|max:255',
            'location.latitude' => 'nullable|required_if:type,business_b2b,business_b2c,religious_org,point_of_interest,area_of_interest|numeric|between:-90,90',
            'location.longitude' => 'nullable|required_if:type,business_b2b,business_b2c,religious_org,point_of_interest,area_of_interest|numeric|between:-180,180',
        ]);

        DB::beginTransaction();
        try {
            // Auto-create regions for all place types (they all need city/state now)
            if ($request->filled('location')) {
                $regionIds = $this->getOrCreateRegions($request->location);
                $validated = array_merge($validated, $regionIds);
            }

            // Extract location data and save coordinates directly to place
            $locationData = $request->location;
            $placeData = [
                ...$validated,
                'created_by_user_id' => auth()->id(),
                'status' => 'draft',
                'published_at' => null,
                // Save coordinates directly to place model
                'latitude' => $locationData['latitude'] ?? null,
                'longitude' => $locationData['longitude'] ?? null,
                'address' => $locationData['address_line1'] ?? null,
                'city' => $locationData['city'] ?? null,
                'state' => $locationData['state'] ?? null,
                'zip_code' => $locationData['zip_code'] ?? null,
            ];
            
            // Remove the nested location data before creating
            unset($placeData['location']);
            
            // Create directory entry
            $entry = Place::create($placeData);

            // Create location (always required now)
            Location::create([
                'directory_entry_id' => $entry->id,
                ...$request->location,
            ]);

            DB::commit();

            // Load all necessary relationships for canonical URL generation
            $entry->load(['location', 'category', 'stateRegion', 'cityRegion', 'neighborhoodRegion']);
            
            return response()->json($entry, 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create entry'], 500);
        }
    }

    public function update(Request $request, Place $place)
    {
        // Check permissions using policy
        $this->authorize('update', $place);

        // Debug logging
        \Log::info('Update request data:', $request->all());

        try {
            $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:directory_entries,slug,' . $place->id,
            'description' => 'nullable|string',
            'type' => 'sometimes|required|in:business_b2b,business_b2c,religious_org,point_of_interest,area_of_interest,service,online',
            'category_id' => 'sometimes|required|exists:categories,id',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email:filter|max:255',
            'website_url' => 'nullable|url|max:255',
            'links' => 'nullable|array',
            'links.*.text' => 'required|string|max:100',
            'links.*.url' => 'required|url|max:500',
            'logo_url' => 'nullable|string|max:255',
            'cover_image_url' => 'nullable|string|max:255',
            'gallery_images' => 'nullable|array',
            'tags' => 'nullable|array',
            'social_links' => 'nullable|array',
            'status' => 'sometimes|required|in:draft,pending_review,published,archived',
            
            // Social Media fields
            'facebook_url' => 'nullable|url|max:255',
            'instagram_handle' => 'nullable|string|max:100|regex:/^@?[A-Za-z0-9_.]+$/',
            'twitter_handle' => 'nullable|string|max:100|regex:/^@?[A-Za-z0-9_]+$/',
            'youtube_channel' => 'nullable|url|max:255',
            'messenger_contact' => 'nullable|string|max:255',
            
            // Location data (city and state required for all types)
            'location' => 'nullable|array',
            'location.address_line1' => 'nullable|required_if:type,business_b2b,business_b2c,religious_org,point_of_interest,area_of_interest|string|max:255',
            'location.city' => 'required_with:location|string|max:255',
            'location.state' => 'required_with:location|string|max:2',
            'location.zip_code' => 'nullable|required_if:type,business_b2b,business_b2c,religious_org,point_of_interest,area_of_interest|string|max:10',
            'location.neighborhood' => 'nullable|string|max:255',
            'location.latitude' => 'nullable|required_if:type,business_b2b,business_b2c,religious_org,point_of_interest,area_of_interest|numeric|between:-90,90',
            'location.longitude' => 'nullable|required_if:type,business_b2b,business_b2c,religious_org,point_of_interest,area_of_interest|numeric|between:-180,180',
            ]);

            \Log::info('Validated data:', $validated);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation failed:', $e->errors());
            return response()->json(['errors' => $e->errors()], 422);
        }

        DB::beginTransaction();
        try {
            // Remove location data from main update to handle separately
            $entryData = collect($validated)->except(['location'])->toArray();
            \Log::info('Entry data being saved:', $entryData);
            \Log::info('Entry before update:', [
                'logo_url' => $place->logo_url,
                'cover_image_url' => $place->cover_image_url,
                'updated_at' => $place->updated_at
            ]);
            // Check if entry exists and is not read-only
            \Log::info('Entry exists:', ['exists' => $place->exists]);
            \Log::info('Entry attributes before update:', $place->getAttributes());
            
            // Try to update with error catching
            try {
                $updateResult = $place->update($entryData);
                \Log::info('Update result:', ['success' => $updateResult]);
                
                if (!$updateResult) {
                    // If update fails, try to get the underlying reason
                    \Log::error('Update failed - checking for errors');
                    \Log::info('Model dirty attributes:', $place->getDirty());
                    \Log::info('Model original attributes:', $place->getOriginal());
                }
                
                $fresh = $place->fresh();
                \Log::info('Entry after update:', [
                    'logo_url' => $fresh->logo_url,
                    'cover_image_url' => $fresh->cover_image_url,
                    'updated_at' => $fresh->updated_at
                ]);
            } catch (\Exception $e) {
                \Log::error('Update exception:', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                throw $e;
            }

            // Handle location update/creation (now required for all types)
            if ($request->has('location')) {
                $locationData = array_filter($request->location); // Remove null values
                
                if (!empty($locationData)) {
                    // Auto-create regions if needed (only for physical location types)
                    if (in_array($place->type, ['business_b2b', 'business_b2c', 'religious_org', 'point_of_interest', 'area_of_interest'])) {
                        $regionIds = $this->getOrCreateRegions($locationData);
                        if (!empty($regionIds)) {
                            $place->update($regionIds);
                        }
                    }

                    // Update coordinates directly on the place model
                    $place->update([
                        'latitude' => $locationData['latitude'] ?? $place->latitude,
                        'longitude' => $locationData['longitude'] ?? $place->longitude,
                        'address' => $locationData['address_line1'] ?? $place->address,
                        'city' => $locationData['city'] ?? $place->city,
                        'state' => $locationData['state'] ?? $place->state,
                        'zip_code' => $locationData['zip_code'] ?? $place->zip_code,
                    ]);

                    if ($place->location) {
                        $place->location->update($locationData);
                    } else {
                        $place->location()->create([
                            'directory_entry_id' => $place->id,
                            ...$locationData
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json($place->load('location'));

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to update entry: ' . $e->getMessage()], 500);
        }
    }

    public function nearbyEntries(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:1|max:100',
            'type' => 'nullable|in:business_b2b,business_b2c,religious_org,point_of_interest,area_of_interest,service,online',
        ]);

        $entries = Place::published()
            ->with(['category', 'location'])
            ->whereHas('location', function ($query) use ($request) {
                $query->withinRadius(
                    $request->lat,
                    $request->lng,
                    $request->radius ?? 25
                );
            })
            ->when($request->filled('type'), function ($query) use ($request) {
                $query->ofType($request->type);
            })
            ->get();

        // Add distance to each entry
        $entries->each(function ($entry) {
            if ($entry->location) {
                $entry->distance = $entry->location->distance;
            }
        });

        // Sort by distance
        $sorted = $entries->sortBy('distance');

        return response()->json([
            'entries' => $sorted->values(),
            'total' => $sorted->count(),
        ]);
    }

    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240', // 10MB max
            'type' => 'required|in:logo,cover,gallery',
        ]);

        try {
            $file = $request->file('image');
            $type = $request->input('type');
            
            // Generate unique filename
            $filename = Str::random(40) . '.' . $file->getClientOriginalExtension();
            
            // Define image constraints based on type
            $constraints = $this->getImageConstraints($type);
            
            // Process image
            $manager = new ImageManager(new Driver());
            $image = $manager->read($file);
            
            // Apply constraints
            if ($constraints['max_width'] || $constraints['max_height']) {
                $image->scaleDown($constraints['max_width'], $constraints['max_height']);
            }
            
            // Save to storage
            $path = "directory-entries/{$type}s/{$filename}";
            Storage::disk('public')->put($path, $image->toJpeg());
            
            return response()->json([
                'success' => true,
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
                'filename' => $filename,
                'type' => $type,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload image: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteImage(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        try {
            $path = $request->input('path');
            
            // Security check - ensure path is within allowed directories
            if (!Str::startsWith($path, 'directory-entries/')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid file path'
                ], 400);
            }
            
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Image deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete image: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getImageConstraints($type)
    {
        $constraints = [
            'logo' => [
                'max_width' => 400,
                'max_height' => 400,
                'aspect_ratio' => '1:1', // Square preferred
                'file_size' => 2048, // 2MB
            ],
            'cover' => [
                'max_width' => 1920,
                'max_height' => 1080,
                'aspect_ratio' => '16:9', // Widescreen
                'file_size' => 5120, // 5MB
            ],
            'gallery' => [
                'max_width' => 1200,
                'max_height' => 800,
                'aspect_ratio' => null, // Any aspect ratio
                'file_size' => 3072, // 3MB
            ],
        ];

        return $constraints[$type] ?? $constraints['gallery'];
    }

    /**
     * Get or create regions based on location data
     */
    private function getOrCreateRegions($locationData)
    {
        $regionIds = [];
        
        // Create state region if doesn't exist
        if (!empty($locationData['state'])) {
            // Normalize state to full name (e.g., CA -> California)
            $stateName = StateHelper::normalize($locationData['state']);
            
            $stateRegion = \App\Models\Region::firstOrCreate(
                [
                    'slug' => Str::slug($stateName), 
                    'type' => 'state'
                ],
                [
                    'name' => $stateName, 
                    'level' => 1,
                    'parent_id' => null,
                    'abbreviation' => StateHelper::isAbbreviation($locationData['state']) 
                        ? strtoupper($locationData['state']) 
                        : StateHelper::getAbbreviation($stateName)
                ]
            );
            $regionIds['state_region_id'] = $stateRegion->id;
            
            // Create city region if doesn't exist
            if (!empty($locationData['city'])) {
                // For cities, first try to find by parent and name/type
                $cityRegion = \App\Models\Region::where('parent_id', $stateRegion->id)
                    ->where('type', 'city')
                    ->where(function($q) use ($locationData) {
                        $q->where('name', $locationData['city'])
                          ->orWhere('slug', Str::slug($locationData['city']));
                    })
                    ->first();
                    
                if (!$cityRegion) {
                    // Create with a unique slug that includes state
                    $citySlug = Str::slug($locationData['city']);
                    $stateSlug = $stateRegion->slug;
                    $uniqueSlug = $citySlug;
                    
                    // If slug already exists, append state to make it unique
                    if (\App\Models\Region::where('slug', $citySlug)->exists()) {
                        $uniqueSlug = $citySlug . '-' . $stateSlug;
                    }
                    
                    $cityRegion = \App\Models\Region::create([
                        'slug' => $uniqueSlug,
                        'type' => 'city',
                        'parent_id' => $stateRegion->id,
                        'name' => $locationData['city'], 
                        'level' => 2
                    ]);
                }
                
                $regionIds['city_region_id'] = $cityRegion->id;
                
                // Create neighborhood region if doesn't exist
                if (!empty($locationData['neighborhood'])) {
                    // Similar approach for neighborhoods
                    $neighborhoodRegion = \App\Models\Region::where('parent_id', $cityRegion->id)
                        ->where('type', 'neighborhood')
                        ->where(function($q) use ($locationData) {
                            $q->where('name', $locationData['neighborhood'])
                              ->orWhere('slug', Str::slug($locationData['neighborhood']));
                        })
                        ->first();
                        
                    if (!$neighborhoodRegion) {
                        $neighborhoodSlug = Str::slug($locationData['neighborhood']);
                        $uniqueSlug = $neighborhoodSlug;
                        
                        // If slug already exists, append city slug to make it unique
                        if (\App\Models\Region::where('slug', $neighborhoodSlug)->exists()) {
                            $uniqueSlug = $neighborhoodSlug . '-' . $cityRegion->slug;
                        }
                        
                        $neighborhoodRegion = \App\Models\Region::create([
                            'slug' => $uniqueSlug,
                            'type' => 'neighborhood',
                            'parent_id' => $cityRegion->id,
                            'name' => $locationData['neighborhood'], 
                            'level' => 3
                        ]);
                    }
                    
                    $regionIds['neighborhood_region_id'] = $neighborhoodRegion->id;
                }
            }
        }
        
        return $regionIds;
    }

    /**
     * Get public index of entries (no auth required)
     */
    public function publicIndex(Request $request)
    {
        $query = Place::with(['category', 'location', 'stateRegion', 'cityRegion', 'neighborhoodRegion'])
            ->published()
            ->featured();

        $entries = $query->paginate(12);

        return response()->json([
            'entries' => $entries,
            'categories' => \App\Models\Category::whereNull('parent_id')->with('children')->get()
        ]);
    }

    /**
     * Get entries by category slug
     */
    public function byCategory(Request $request, $slug)
    {
        $category = \App\Models\Category::where('slug', $slug)->firstOrFail();
        
        $query = Place::with(['category', 'location'])
            ->published()
            ->where('category_id', $category->id);

        if ($request->filled('sort')) {
            switch ($request->sort) {
                case 'name':
                    $query->orderBy('title');
                    break;
                case 'featured':
                    $query->orderBy('is_featured', 'desc')->orderBy('created_at', 'desc');
                    break;
                default:
                    $query->latest();
            }
        } else {
            $query->latest();
        }

        $entries = $query->paginate(20);

        return response()->json([
            'category' => $category,
            'entries' => $entries
        ]);
    }

    /**
     * Show entry by slug
     */
    public function show($slug)
    {
        $entry = Place::with(['category.parent', 'location', 'stateRegion', 'cityRegion', 'neighborhoodRegion', 'ownerUser:id,firstname,lastname'])
            ->where('slug', $slug)
            ->published()
            ->firstOrFail();

        // Get related entries
        $relatedEntries = Place::where('category_id', $entry->category_id)
            ->where('id', '!=', $entry->id)
            ->published()
            ->limit(6)
            ->get();

        // Add following and saved status if user is authenticated
        if (auth()->check()) {
            $entry->is_following = auth()->user()->isFollowing($entry);
            $entry->is_saved = $entry->isSavedBy(auth()->user());
        }

        return response()->json([
            'entry' => $entry,
            'relatedEntries' => $relatedEntries,
            'parentCategory' => $entry->category->parent ?? null,
            'childCategory' => $entry->category
        ]);
    }

    /**
     * Show entry by ID
     */
    public function showById($id)
    {
        $entry = Place::with(['category.parent', 'location', 'stateRegion', 'cityRegion', 'neighborhoodRegion', 'ownerUser:id,firstname,lastname'])
            ->published()
            ->findOrFail($id);

        // Get related entries
        $relatedEntries = Place::where('category_id', $entry->category_id)
            ->where('id', '!=', $entry->id)
            ->published()
            ->limit(6)
            ->get();

        // Add following and saved status if user is authenticated
        if (auth()->check()) {
            $entry->is_following = auth()->user()->isFollowing($entry);
            $entry->is_saved = $entry->isSavedBy(auth()->user());
        }

        return response()->json([
            'entry' => $entry,
            'relatedEntries' => $relatedEntries,
            'parentCategory' => $entry->category->parent ?? null,
            'childCategory' => $entry->category
        ]);
    }

    /**
     * Show entry by canonical URL pattern
     */
    public function showByCanonicalUrl($state, $city, $category, $entrySlug)
    {
        // Extract ID from entry slug (format: slug-123)
        if (!preg_match('/^(.+)-(\d+)$/', $entrySlug, $matches)) {
            abort(404);
        }
        
        $id = $matches[2];
        
        $entry = Place::with(['category.parent', 'location', 'stateRegion', 'cityRegion', 'ownerUser:id,firstname,lastname'])
            ->published()
            ->findOrFail($id);
            
        // For API requests, skip URL verification to avoid redirect issues
        // Frontend will handle canonical URLs properly
        
        // Get related entries
        $relatedEntries = Place::where('category_id', $entry->category_id)
            ->where('id', '!=', $entry->id)
            ->published()
            ->limit(6)
            ->get();

        // Add following and saved status if user is authenticated
        if (auth()->check()) {
            $entry->is_following = auth()->user()->isFollowing($entry);
            $entry->is_saved = $entry->isSavedBy(auth()->user());
        }

        return response()->json([
            'entry' => $entry,
            'relatedEntries' => $relatedEntries,
            'parentCategory' => $entry->category->parent ?? null,
            'childCategory' => $entry->category
        ]);
    }

    /**
     * Browse entries by state
     */
    public function browseByState($state)
    {
        $region = \App\Models\Region::where('slug', $state)
            ->where('type', 'state')
            ->firstOrFail();
            
        // Get cities in this state
        $cities = \App\Models\Region::where('parent_id', $region->id)
            ->where('type', 'city')
            ->withCount(['directoryEntries as cached_place_count' => function($query) {
                $query->published();
            }])
            ->orderBy('name')
            ->get();
            
        // Get categories with counts for this state
        $categories = \App\Models\Category::whereNull('parent_id')
            ->withCount(['directoryEntries as entries_count' => function($query) use ($region) {
                $query->published()->where('state_region_id', $region->id);
            }])
            ->having('entries_count', '>', 0)
            ->orderBy('name')
            ->get();
            
        // Get recent entries
        $recentEntries = Place::with(['category', 'cityRegion', 'stateRegion'])
            ->published()
            ->where('state_region_id', $region->id)
            ->latest()
            ->limit(12)
            ->get();

        return response()->json([
            'region' => $region,
            'cities' => $cities,
            'categories' => $categories,
            'recent_entries' => $recentEntries
        ]);
    }

    /**
     * Browse entries by city
     */
    public function browseByCity($state, $city)
    {
        $stateRegion = \App\Models\Region::where('slug', $state)
            ->where('type', 'state')
            ->firstOrFail();
            
        $cityRegion = \App\Models\Region::where('slug', $city)
            ->where('type', 'city')
            ->where('parent_id', $stateRegion->id)
            ->firstOrFail();
            
        // Get neighborhoods in this city
        $neighborhoods = \App\Models\Region::where('parent_id', $cityRegion->id)
            ->where('type', 'neighborhood')
            ->withCount(['directoryEntries as cached_place_count' => function($query) {
                $query->published();
            }])
            ->orderBy('name')
            ->get();
            
        // Get categories with counts for this city
        $categories = \App\Models\Category::whereNull('parent_id')
            ->withCount(['directoryEntries as entries_count' => function($query) use ($cityRegion) {
                $query->published()->where('city_region_id', $cityRegion->id);
            }])
            ->having('entries_count', '>', 0)
            ->orderBy('name')
            ->get();
            
        // Get all entries in this city
        $entries = Place::with(['category', 'location', 'neighborhoodRegion'])
            ->published()
            ->where('city_region_id', $cityRegion->id)
            ->get()
            ->map(function($entry) {
                $entry->canonical_url = $entry->getCanonicalUrl();
                return $entry;
            });

        return response()->json([
            'state' => $stateRegion,
            'city' => $cityRegion,
            'neighborhoods' => $neighborhoods,
            'categories' => $categories,
            'entries' => $entries
        ]);
    }

    /**
     * Browse entries by category in city
     */
    public function browseByCategory($state, $city, $category)
    {
        $stateRegion = \App\Models\Region::where('slug', $state)
            ->where('type', 'state')
            ->firstOrFail();
            
        $cityRegion = \App\Models\Region::where('slug', $city)
            ->where('type', 'city')
            ->where('parent_id', $stateRegion->id)
            ->firstOrFail();
            
        $categoryModel = \App\Models\Category::where('slug', $category)
            ->firstOrFail();
            
        // Get entries in this category and city
        $entries = Place::with(['category', 'location', 'neighborhoodRegion'])
            ->published()
            ->where('city_region_id', $cityRegion->id)
            ->where('category_id', $categoryModel->id)
            ->get()
            ->map(function($entry) {
                $entry->canonical_url = $entry->getCanonicalUrl();
                return $entry;
            });

        return response()->json([
            'state' => $stateRegion,
            'city' => $cityRegion,
            'category' => $categoryModel,
            'entries' => $entries
        ]);
    }
    
    public function submitForReview(Request $request, Place $place)
    {
        // Check permissions using policy
        $this->authorize('update', $place);
        
        // Only allow submission if in draft or rejected status
        if (!in_array($place->status, ['draft', 'rejected'])) {
            return response()->json(['error' => 'This place cannot be submitted for review in its current status'], 400);
        }
        
        // Update status
        $place->status = 'pending_review';
        $place->save();
        
        return response()->json($place->load(['location', 'category', 'stateRegion', 'cityRegion']));
    }
    
    /**
     * Get places associated with the current user
     * Includes created places and claimed places
     */
    public function myPlaces(Request $request)
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $request->validate([
            'type' => 'nullable|in:created,claimed,all',
            'status' => 'nullable|in:published,draft,pending_review,all',
            'q' => 'nullable|string|max:255',
        ]);
        
        $type = $request->get('type', 'all');
        $status = $request->get('status', 'all');
        
        // Start with base query
        $query = Place::with([
            'category:id,name,slug',
            'region:id,name,slug,type',
            'stateRegion:id,name,slug',
            'cityRegion:id,name,slug',
            'claims' => function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->whereIn('status', ['pending', 'approved']) // Only load active claims
                  ->latest();
            }
        ]);
        
        // Filter by ownership type
        if ($type === 'created') {
            $query->where('created_by_user_id', $user->id);
        } elseif ($type === 'claimed') {
            $query->whereHas('claims', function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->whereIn('status', ['pending', 'approved']); // Exclude expired and rejected
            });
        } else {
            // Show both created and claimed (excluding expired/rejected)
            $query->where(function($q) use ($user) {
                $q->where('created_by_user_id', $user->id)
                  ->orWhereHas('claims', function($subQ) use ($user) {
                      $subQ->where('user_id', $user->id)
                           ->whereIn('status', ['pending', 'approved']); // Exclude expired and rejected
                  });
            });
        }
        
        // Filter by status
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        
        // Search
        if ($request->filled('q')) {
            $searchTerm = $request->get('q');
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'ilike', "%{$searchTerm}%")
                  ->orWhere('description', 'ilike', "%{$searchTerm}%");
            });
        }
        
        // Add claim status for each place
        $places = $query->orderBy('updated_at', 'desc')
                       ->paginate(20)
                       ->through(function ($place) use ($user) {
                           // Determine ownership type
                           if ($place->created_by_user_id === $user->id) {
                               $place->ownership_type = 'created';
                           } else {
                               $place->ownership_type = 'claimed';
                           }
                           
                           // Add active claim info if exists
                           $activeClaim = $place->claims->first();
                           if ($activeClaim) {
                               $place->claim_status = $activeClaim->status;
                               $place->claim_tier = $activeClaim->tier;
                               $place->claimed_at = $activeClaim->approved_at;
                           }
                           
                           // Remove claims relation from response
                           unset($place->claims);
                           
                           return $place;
                       });
        
        return response()->json($places);
    }
    
    /**
     * Show a place for claiming purposes (includes unpublished places)
     */
    public function showForClaiming($slug)
    {
        // First try to find by slug
        $place = Place::where('slug', $slug)
            ->with([
                'category:id,name,slug',
                'region:id,name,slug,type',
                'stateRegion:id,name,slug',
                'cityRegion:id,name,slug',
                'claims' => function($q) {
                    $q->latest();
                }
            ])
            ->first();
            
        // If not found by slug and slug is numeric, try by ID
        if (!$place && is_numeric($slug)) {
            $place = Place::where('id', $slug)
                ->with([
                    'category:id,name,slug',
                    'region:id,name,slug,type',
                    'stateRegion:id,name,slug',
                    'cityRegion:id,name,slug',
                    'claims' => function($q) {
                        $q->latest();
                    }
                ])
                ->first();
        }
        
        if (!$place) {
            return response()->json(['error' => 'Place not found'], 404);
        }
        
        // Check if place is already claimed
        $activeClaim = $place->claims->where('status', '!=', 'rejected')->first();
        if ($activeClaim) {
            $place->is_claimed = true;
            $place->active_claim = $activeClaim;
        } else {
            $place->is_claimed = false;
        }
        
        return response()->json([
            'data' => $place
        ]);
    }

    /**
     * Get places for map view with clustering support
     */
    public function mapData(Request $request)
    {
        $request->validate([
            'bounds' => 'required|array',
            'bounds.north' => 'required|numeric|between:-90,90',
            'bounds.south' => 'required|numeric|between:-90,90',
            'bounds.east' => 'required|numeric|between:-180,180',
            'bounds.west' => 'required|numeric|between:-180,180',
            'zoom' => 'nullable|numeric|between:0,22',
            'category_id' => 'nullable|exists:categories,id',
            'type' => 'nullable|in:business_b2b,business_b2c,religious_org,point_of_interest,area_of_interest,service,online',
            'search' => 'nullable|string|max:255',
        ]);

        $query = Place::published()
            ->with([
                'category:id,name,slug',
                'region:id,name,slug,parent_id,type',
                'stateRegion:id,name,slug',
                'cityRegion:id,name,slug'
            ])
            ->hasValidCoordinates()
            ->withinBoundingBox(
                $request->bounds['north'],
                $request->bounds['south'],
                $request->bounds['east'],
                $request->bounds['west']
            );

        // Apply filters
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('type')) {
            $query->ofType($request->type);
        }

        // Search filter
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('title', 'ilike', "%{$searchTerm}%")
                  ->orWhere('description', 'ilike', "%{$searchTerm}%")
                  ->orWhere('address', 'ilike', "%{$searchTerm}%")
                  ->orWhere('city', 'ilike', "%{$searchTerm}%");
            });
        }

        // Limit results based on zoom level to prevent overwhelming the map
        $zoom = $request->get('zoom', 10);
        $limit = match(true) {
            $zoom < 8 => 100,
            $zoom < 10 => 500,
            $zoom < 12 => 1000,
            default => 2000
        };

        $places = $query->limit($limit)->get();

        // Format for Mapbox
        $features = $places->map(function ($place) {
            return [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [(float)$place->longitude, (float)$place->latitude]
                ],
                'properties' => [
                    'id' => $place->id,
                    'title' => $place->title,
                    'slug' => $place->slug,
                    'canonical_url' => $place->canonical_url,
                    'category' => $place->category ? [
                        'id' => $place->category->id,
                        'name' => $place->category->name,
                        'slug' => $place->category->slug
                    ] : null,
                    'address' => $place->address,
                    'city' => $place->city,
                    'state' => $place->state,
                    'logo_url' => $place->logo_url,
                    'cover_image_url' => $place->cover_image_url,
                    'is_featured' => $place->is_featured
                ]
            ];
        });

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
            'total' => $features->count()
        ]);
    }

    /**
     * Search places with geocoding support
     */
    public function searchWithLocation(Request $request)
    {
        $request->validate([
            'q' => 'required|string|max:255',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:1|max:100',
        ]);

        $query = Place::published()
            ->with(['category', 'location', 'stateRegion', 'cityRegion']);

        // Search by name/description
        $query->where(function ($q) use ($request) {
            $q->where('title', 'ilike', "%{$request->q}%")
              ->orWhere('description', 'ilike', "%{$request->q}%");
        });

        // If location provided, order by distance
        if ($request->filled(['lat', 'lng'])) {
            $query->select('directory_entries.*')
                ->selectDistanceTo($request->lat, $request->lng);
            
            if ($request->filled('radius')) {
                $query->withinRadius($request->lat, $request->lng, $request->radius);
            }
            
            $query->orderByDistance($request->lat, $request->lng);
        }

        $places = $query->limit(20)->get();

        return response()->json([
            'places' => $places,
            'total' => $places->count()
        ]);
    }
}