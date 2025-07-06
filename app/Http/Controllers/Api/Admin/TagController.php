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
        $query = Tag::withCount('taggables');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $isActive = $request->status === 'active';
            $query->where('is_active', $isActive);
        }

        // Filter by content type
        if ($request->filled('content_type')) {
            $contentType = $request->content_type;
            $query->whereHas('taggables', function ($q) use ($contentType) {
                $q->where('taggable_type', $contentType);
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        
        if ($sortBy === 'usage_count') {
            $query->orderBy('taggables_count', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $tags = $query->paginate(20);

        // Add usage by type for each tag
        $tags->getCollection()->transform(function ($tag) {
            $usageByType = DB::table('taggables')
                ->select('taggable_type', DB::raw('count(*) as count'))
                ->where('tag_id', $tag->id)
                ->groupBy('taggable_type')
                ->pluck('count', 'taggable_type');
            
            $tag->usage_by_type = $usageByType;
            return $tag;
        });

        return response()->json($tags);
    }

    public function stats()
    {
        $contentTypeStats = DB::table('taggables')
            ->select('taggable_type', DB::raw('count(distinct tag_id) as unique_tags'), DB::raw('count(*) as total_usages'))
            ->groupBy('taggable_type')
            ->get()
            ->keyBy('taggable_type');

        return response()->json([
            'total_tags' => Tag::count(),
            'active_tags' => Tag::where('is_active', true)->count(),
            'inactive_tags' => Tag::where('is_active', false)->count(),
            'total_usages' => DB::table('taggables')->count(),
            'content_types' => $contentTypeStats,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:tags,slug',
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'is_active' => 'boolean',
        ]);

        $tag = Tag::create($validated);

        return response()->json([
            'message' => 'Tag created successfully',
            'tag' => $tag,
        ], 201);
    }

    public function show($id)
    {
        $tag = Tag::withCount('taggables')->findOrFail($id);
        
        // Get usage by type
        $usageByType = DB::table('taggables')
            ->select('taggable_type', DB::raw('count(*) as count'))
            ->where('tag_id', $tag->id)
            ->groupBy('taggable_type')
            ->pluck('count', 'taggable_type');
        
        $tag->usage_by_type = $usageByType;
        
        return response()->json($tag);
    }

    public function update(Request $request, $id)
    {
        $tag = Tag::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:tags,slug,' . $id,
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'is_active' => 'boolean',
        ]);

        $tag->update($validated);

        return response()->json([
            'message' => 'Tag updated successfully',
            'tag' => $tag->fresh(),
        ]);
    }

    public function destroy($id)
    {
        $tag = Tag::findOrFail($id);
        
        // This will also delete related taggables due to cascade
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
            'action' => 'required|string|in:delete,activate,deactivate,merge',
            'is_active' => 'required_if:action,activate,deactivate|boolean',
            'target_tag_id' => 'required_if:action,merge|exists:tags,id',
        ]);

        switch ($validated['action']) {
            case 'delete':
                Tag::whereIn('id', $validated['tag_ids'])->delete();
                $message = 'Tags deleted successfully';
                break;

            case 'activate':
            case 'deactivate':
                Tag::whereIn('id', $validated['tag_ids'])
                    ->update(['is_active' => $validated['is_active']]);
                $message = 'Tags updated successfully';
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

        $tags = Tag::active()
                   ->where('name', 'like', "%{$query}%")
                   ->orderBy('name')
                   ->limit(20)
                   ->select('id', 'name', 'slug', 'color')
                   ->get();

        return response()->json($tags);
    }

    public function popular(Request $request)
    {
        $limit = $request->get('limit', 20);
        $contentType = $request->get('content_type');

        $query = Tag::active()->popular($limit);

        if ($contentType) {
            $query->whereHas('taggables', function ($q) use ($contentType) {
                $q->where('taggable_type', $contentType);
            });
        }

        $tags = $query->get();

        return response()->json($tags);
    }
}