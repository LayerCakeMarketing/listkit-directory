<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DirectoryEntry;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DirectoryEntryController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:255',
            'type' => 'nullable|in:physical_location,online_business,service,event,resource',
            'category_id' => 'nullable|exists:categories,id',
            'status' => 'nullable|in:draft,pending_review,published,archived',
            'sort_by' => 'nullable|in:title,created_at,updated_at,status',
            'sort_order' => 'nullable|in:asc,desc',
        ]);

        $query = DirectoryEntry::with(['category', 'region', 'location', 'createdBy', 'owner']);

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

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

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
            'status' => 'nullable|in:draft,pending_review,published',
            
            // Location data (if physical location)
            'location' => 'nullable|required_if:type,physical_location|array',
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
            $entry = DirectoryEntry::create([
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
            if ($request->filled('location') && in_array($entry->type, ['physical_location', 'event'])) {
                Location::create([
                    'directory_entry_id' => $entry->id,
                    ...$request->location,
                ]);
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

    public function update(Request $request, DirectoryEntry $entry)
    {
        // Check permissions
        if (!auth()->user()->canManageContent() && $entry->created_by_user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|required|in:physical_location,online_business,service,event,resource',
            'category_id' => 'sometimes|required|exists:categories,id',
            'status' => 'sometimes|required|in:draft,pending_review,published,archived',
            // ... other fields
        ]);

        DB::beginTransaction();
        try {
            if (isset($validated['title']) && $validated['title'] !== $entry->title) {
                $validated['slug'] = Str::slug($validated['title']);
            }

            $entry->update($validated);

            // Update location if provided
            if ($request->has('location') && $entry->hasPhysicalLocation()) {
                if ($entry->location) {
                    $entry->location->update($request->location);
                } else {
                    Location::create([
                        'directory_entry_id' => $entry->id,
                        ...$request->location,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Directory entry updated successfully',
                'entry' => $entry->fresh()->load(['location', 'category'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to update entry: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(DirectoryEntry $entry)
    {
        if (!auth()->user()->canManageContent()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $entry->delete();

        return response()->json(['message' => 'Directory entry deleted successfully']);
    }

    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'entry_ids' => 'required|array',
            'entry_ids.*' => 'exists:directory_entries,id',
            'action' => 'required|in:publish,archive,delete',
        ]);

        if (!auth()->user()->canManageContent()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $entries = DirectoryEntry::whereIn('id', $validated['entry_ids']);

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
        }

        return response()->json(['message' => 'Bulk action completed successfully']);
    }

    public function stats()
    {
        $stats = [
            'total_entries' => DirectoryEntry::count(),
            'by_type' => DirectoryEntry::selectRaw('type, count(*) as count')
                                      ->groupBy('type')
                                      ->pluck('count', 'type'),
            'by_status' => DirectoryEntry::selectRaw('status, count(*) as count')
                                        ->groupBy('status')
                                        ->pluck('count', 'status'),
            'published' => DirectoryEntry::where('status', 'published')->count(),
            'pending_review' => DirectoryEntry::where('status', 'pending_review')->count(),
            'featured' => DirectoryEntry::where('is_featured', true)->count(),
            'verified' => DirectoryEntry::where('is_verified', true)->count(),
        ];

        return response()->json($stats);
    }
}