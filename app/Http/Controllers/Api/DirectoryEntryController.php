<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DirectoryEntry;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class DirectoryEntryController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'type' => 'nullable|in:physical_location,online_business,service,event,resource',
            'category_id' => 'nullable|exists:categories,id',
            'region_id' => 'nullable|exists:regions,id',
            'status' => 'nullable|in:published,draft,pending_review',
            'q' => 'nullable|string|max:255',
        ]);

        $query = DirectoryEntry::with(['category', 'region', 'location']);

        // Filter by type
        if ($request->filled('type')) {
            $query->ofType($request->type);
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by region
        if ($request->filled('region_id')) {
            $query->where('region_id', $request->region_id);
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

        return response()->json($entries);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:physical_location,online_business,service,event,resource',
            'category_id' => 'required|exists:categories,id',
            'region_id' => 'nullable|exists:regions,id',
            'tags' => 'nullable|array',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website_url' => 'nullable|url|max:255',
            'social_links' => 'nullable|array',
            
            // Location data (if physical location)
            'location' => 'nullable|required_if:type,physical_location|array',
            'location.address_line1' => 'required_with:location|string|max:255',
            'location.city' => 'required_with:location|string|max:255',
            'location.state' => 'required_with:location|string|max:2',
            'location.zip_code' => 'required_with:location|string|max:10',
            'location.latitude' => 'required_with:location|numeric|between:-90,90',
            'location.longitude' => 'required_with:location|numeric|between:-180,180',
        ]);

        DB::beginTransaction();
        try {
            // Create directory entry
            $entry = DirectoryEntry::create([
                ...$validated,
                'created_by_user_id' => auth()->id(),
                'status' => auth()->user()?->canPublishContent() ? 'published' : 'pending_review',
                'published_at' => auth()->user()?->canPublishContent() ? now() : null,
            ]);

            // Create location if provided
            if ($request->filled('location') && in_array($entry->type, ['physical_location', 'event'])) {
                Location::create([
                    'directory_entry_id' => $entry->id,
                    ...$request->location,
                ]);
            }

            DB::commit();

            return response()->json($entry->load('location'), 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create entry'], 500);
        }
    }

    public function update(Request $request, DirectoryEntry $directoryEntry)
    {
        // Check permissions
        if (!$directoryEntry->canBeEdited()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Debug logging
        \Log::info('Update request data:', $request->all());

        try {
            $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|required|in:physical_location,online_business,service,event,resource',
            'category_id' => 'sometimes|required|exists:categories,id',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website_url' => 'nullable|url|max:255',
            'logo_url' => 'nullable|string|max:255',
            'cover_image_url' => 'nullable|string|max:255',
            'gallery_images' => 'nullable|array',
            'tags' => 'nullable|array',
            'social_links' => 'nullable|array',
            'status' => 'sometimes|required|in:draft,pending_review,published,archived',
            
            // Location data (if physical location)
            'location' => 'nullable|array',
            'location.address_line1' => 'nullable|string|max:255',
            'location.city' => 'nullable|string|max:255',
            'location.state' => 'nullable|string|max:2',
            'location.zip_code' => 'nullable|string|max:10',
            'location.latitude' => 'nullable|numeric|between:-90,90',
            'location.longitude' => 'nullable|numeric|between:-180,180',
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
                'logo_url' => $directoryEntry->logo_url,
                'cover_image_url' => $directoryEntry->cover_image_url,
                'updated_at' => $directoryEntry->updated_at
            ]);
            // Check if entry exists and is not read-only
            \Log::info('Entry exists:', ['exists' => $directoryEntry->exists]);
            \Log::info('Entry attributes before update:', $directoryEntry->getAttributes());
            
            // Try to update with error catching
            try {
                $updateResult = $directoryEntry->update($entryData);
                \Log::info('Update result:', ['success' => $updateResult]);
                
                if (!$updateResult) {
                    // If update fails, try to get the underlying reason
                    \Log::error('Update failed - checking for errors');
                    \Log::info('Model dirty attributes:', $directoryEntry->getDirty());
                    \Log::info('Model original attributes:', $directoryEntry->getOriginal());
                }
                
                $fresh = $directoryEntry->fresh();
                \Log::info('Entry after update:', [
                    'logo_url' => $fresh->logo_url,
                    'cover_image_url' => $fresh->cover_image_url,
                    'updated_at' => $fresh->updated_at
                ]);
            } catch (\Exception $e) {
                \Log::error('Update exception:', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                throw $e;
            }

            // Handle location update/creation
            if ($request->has('location') && in_array($directoryEntry->type, ['physical_location', 'event'])) {
                $locationData = array_filter($request->location); // Remove null values
                
                if (!empty($locationData)) {
                    if ($directoryEntry->location) {
                        $directoryEntry->location->update($locationData);
                    } else {
                        $directoryEntry->location()->create([
                            'directory_entry_id' => $directoryEntry->id,
                            ...$locationData
                        ]);
                    }
                }
            } elseif ($directoryEntry->location && !in_array($directoryEntry->type, ['physical_location', 'event'])) {
                // Remove location if type changed to non-physical
                $directoryEntry->location->delete();
            }

            DB::commit();

            return response()->json($directoryEntry->load('location'));

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
            'type' => 'nullable|in:physical_location,online_business,service,event,resource',
        ]);

        $entries = DirectoryEntry::published()
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
}