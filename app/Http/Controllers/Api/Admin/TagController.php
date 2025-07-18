<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TagController extends Controller
{
    public function index(Request $request)
    {
        $query = Tag::with('creator');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by featured
        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        // Filter by system
        if ($request->boolean('system')) {
            $query->where('is_system', true);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        
        if ($sortBy === 'usage') {
            $query->orderBy('usage_count', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $tags = $query->paginate($request->get('per_page', 50));

        return response()->json($tags);
    }

    public function stats()
    {
        $stats = [
            'total' => Tag::count(),
            'by_type' => Tag::selectRaw('type, count(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type'),
            'featured' => Tag::where('is_featured', true)->count(),
            'system' => Tag::where('is_system', true)->count(),
            'most_used' => Tag::orderBy('usage_count', 'desc')
                ->limit(10)
                ->get(['id', 'name', 'usage_count', 'color']),
            'recently_created' => Tag::latest()
                ->limit(10)
                ->with('creator')
                ->get(['id', 'name', 'created_at', 'created_by']),
        ];

        return response()->json($stats);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|in:general,category,location,event,trending',
            'color' => 'nullable|string|max:7', // hex color
            'description' => 'nullable|string|max:500',
            'is_featured' => 'boolean',
            'is_system' => 'boolean',
        ]);

        // Check for duplicate
        $slug = \Illuminate\Support\Str::slug($validated['name']);
        if (Tag::where('slug', $slug)->exists()) {
            return response()->json([
                'message' => 'A tag with this name already exists.',
                'errors' => ['name' => ['Tag already exists']]
            ], 422);
        }

        $tag = Tag::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'type' => $validated['type'] ?? 'general',
            'color' => $validated['color'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_featured' => $validated['is_featured'] ?? false,
            'is_system' => $validated['is_system'] ?? false,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Tag created successfully',
            'tag' => $tag->load('creator')
        ], 201);
    }

    public function show($id)
    {
        $tag = Tag::with('creator')->findOrFail($id);
        
        return response()->json($tag);
    }

    public function update(Request $request, $id)
    {
        $tag = Tag::findOrFail($id);

        // Prevent editing system tags (except by super admin)
        if ($tag->is_system && !auth()->user()->hasRole('super_admin')) {
            return response()->json(['message' => 'Cannot edit system tags'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'type' => 'nullable|string|in:general,category,location,event,trending',
            'color' => 'nullable|string|max:7',
            'description' => 'nullable|string|max:500',
            'is_featured' => 'boolean',
            'is_system' => 'boolean',
        ]);

        // Check for duplicate if name is changing
        if (isset($validated['name']) && $validated['name'] !== $tag->name) {
            $slug = \Illuminate\Support\Str::slug($validated['name']);
            if (Tag::where('slug', $slug)->where('id', '!=', $tag->id)->exists()) {
                return response()->json([
                    'message' => 'A tag with this name already exists.',
                    'errors' => ['name' => ['Tag already exists']]
                ], 422);
            }
            $validated['slug'] = $slug;
        }

        $tag->update($validated);
        $tag->updateCounts();

        return response()->json([
            'message' => 'Tag updated successfully',
            'tag' => $tag->fresh()->load('creator')
        ]);
    }

    public function destroy($id)
    {
        $tag = Tag::findOrFail($id);
        
        // Prevent deleting system tags
        if ($tag->is_system) {
            return response()->json(['message' => 'Cannot delete system tags'], 403);
        }

        // Check if tag is in use
        if ($tag->usage_count > 0) {
            return response()->json([
                'message' => 'Cannot delete tag that is in use',
                'usage_count' => $tag->usage_count
            ], 422);
        }

        $tag->delete();

        return response()->json([
            'message' => 'Tag deleted successfully',
        ]);
    }

    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'tag_ids' => 'required|array',
            'tag_ids.*' => 'exists:tags,id',
            'action' => 'required|string|in:delete,toggle_featured,merge',
            'target_tag_id' => 'required_if:action,merge|exists:tags,id',
        ]);

        switch ($validated['action']) {
            case 'delete':
                // Only delete non-system tags with no usage
                $tagsToDelete = Tag::whereIn('id', $validated['tag_ids'])
                    ->where('is_system', false)
                    ->where('usage_count', 0)
                    ->pluck('id');
                
                if ($tagsToDelete->isEmpty()) {
                    return response()->json([
                        'message' => 'No tags could be deleted. Tags must not be system tags and must have zero usage.'
                    ], 422);
                }
                
                Tag::whereIn('id', $tagsToDelete)->delete();
                $message = count($tagsToDelete) . ' tags deleted successfully';
                break;

            case 'toggle_featured':
                Tag::whereIn('id', $validated['tag_ids'])
                    ->where('is_system', false)
                    ->each(function ($tag) {
                        $tag->update(['is_featured' => !$tag->is_featured]);
                    });
                $message = 'Tags featured status toggled successfully';
                break;

            case 'merge':
                $targetTag = Tag::findOrFail($validated['target_tag_id']);
                $tagsToMerge = Tag::whereIn('id', $validated['tag_ids'])
                                 ->where('id', '!=', $validated['target_tag_id'])
                                 ->get();

                foreach ($tagsToMerge as $tag) {
                    // Move all taggables to target tag
                    DB::table('taggables')
                        ->where('tag_id', $tag->id)
                        ->update(['tag_id' => $targetTag->id]);
                    
                    // Delete the old tag
                    $tag->delete();
                }
                
                $message = 'Tags merged successfully';
                break;

            default:
                return response()->json(['error' => 'Invalid action'], 400);
        }

        return response()->json(['message' => $message]);
    }

    public function search(Request $request)
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $tags = Tag::where('name', 'like', "%{$query}%")
                   ->orderBy('usage_count', 'desc')
                   ->limit(20)
                   ->select('id', 'name', 'slug', 'color', 'usage_count')
                   ->get();

        return response()->json($tags);
    }

}