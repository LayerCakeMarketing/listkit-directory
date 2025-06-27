<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DirectoryEntry;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'sometimes|required|exists:categories,id',
            'tags' => 'nullable|array',
            'status' => 'sometimes|required|in:draft,pending_review,published,archived',
            // ... other fields
        ]);

        DB::beginTransaction();
        try {
            $directoryEntry->update($validated);

            // Update location if provided
            if ($request->has('location') && $directoryEntry->hasPhysicalLocation()) {
                $directoryEntry->location->update($request->location);
            }

            DB::commit();

            return response()->json($directoryEntry->load('location'));

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to update entry'], 500);
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
}