<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserList;
use App\Models\ListItem;
use App\Models\Place;
use App\Models\Tag;
use App\Models\ListCategory;
use App\Services\ProfanityFilterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserListController extends Controller
{
    public function index(Request $request)
    {
        $query = UserList::with(['user', 'items'])
                        ->withCount('items')
                        ->where('user_id', auth()->id());

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by visibility
        if ($request->filled('visibility')) {
            $query->where('visibility', $request->visibility);
        }

        $lists = $query->orderBy('created_at', 'desc')->paginate(12);

        return response()->json($lists);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'visibility' => 'required|in:public,unlisted,private',
            'is_draft' => 'boolean',
            'scheduled_for' => 'nullable|date|after:now',
            'featured_image' => 'nullable|string', // Base64 or URL
            'featured_image_cloudflare_id' => 'nullable|string',
            'gallery_images' => 'nullable|array',
            'gallery_images.*.id' => 'nullable|string',
            'gallery_images.*.url' => 'nullable|string',
            'gallery_images.*.filename' => 'nullable|string',
            'category_id' => 'required|exists:list_categories,id',
            'tags' => 'nullable|array',
            'tags.*.name' => 'string|max:255',
            'tags.*.is_new' => 'boolean',
        ]);

        DB::transaction(function() use ($validated, &$list) {
            $list = UserList::create([
                'user_id' => auth()->id(),
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']),
                'description' => $validated['description'] ?? null,
                'visibility' => $validated['visibility'],
                'is_draft' => $validated['is_draft'] ?? false,
                'published_at' => (!($validated['is_draft'] ?? false) && empty($validated['scheduled_for'])) ? now() : null,
                'scheduled_for' => $validated['scheduled_for'] ?? null,
                'featured_image' => $validated['featured_image'] ?? null,
                'featured_image_cloudflare_id' => $validated['featured_image_cloudflare_id'] ?? null,
                'gallery_images' => $validated['gallery_images'] ?? null,
                'category_id' => $validated['category_id'],
            ]);

            // Handle tags
            if (!empty($validated['tags'])) {
                $tagIds = [];
                
                foreach ($validated['tags'] as $tagData) {
                    if (isset($tagData['is_new']) && $tagData['is_new']) {
                        // Validate profanity for new tags
                        if (!ProfanityFilterService::validateTag($tagData['name'])) {
                            throw new \Exception('Tag "' . $tagData['name'] . '" contains inappropriate content');
                        }
                        
                        // Check if tag already exists by name/slug
                        $slug = Str::slug($tagData['name']);
                        $existingTag = Tag::where('slug', $slug)->first();
                        
                        if ($existingTag) {
                            $tagIds[] = $existingTag->id;
                        } else {
                            // Create new tag
                            $tag = Tag::create([
                                'name' => $tagData['name'],
                                'slug' => $slug,
                                'color' => '#6B7280',
                                'is_active' => true,
                            ]);
                            $tagIds[] = $tag->id;
                        }
                    } else {
                        // Use existing tag - handle both object and array formats
                        $tagId = isset($tagData['id']) ? $tagData['id'] : $tagData;
                        if (is_numeric($tagId)) {
                            $tagIds[] = $tagId;
                        }
                    }
                }
                
                $list->tags()->sync($tagIds);
            }
        });

        $list->load('user', 'category', 'tags');
        
        return response()->json([
            'message' => 'List created successfully',
            'list' => $list
        ], 201);
    }

    public function show($id)
    {
        $list = UserList::with(['user', 'category', 'tags', 'items' => function($query) {
            $query->orderBy('order_index')
                  ->with(['place.location', 'place.category']);
        }])->findOrFail($id);

        // Check if user can view this list
        if (!$list->canView(auth()->user())) {
            abort(403, 'You do not have permission to view this list');
        }

        // Increment view count if not owner and list is viewable
        if (!$list->isOwnedBy(auth()->user()) && $list->isPublished()) {
            $list->incrementViewCount();
        }

        return response()->json($list);
    }

    public function update(Request $request, $id)
    {
        $list = UserList::findOrFail($id);

        if (!$list->canEdit()) {
            abort(403, 'Unauthorized to edit this list');
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'visibility' => 'sometimes|in:public,unlisted,private',
            'is_draft' => 'sometimes|boolean',
            'scheduled_for' => 'nullable|date|after:now',
            'featured_image' => 'nullable|string',
            'featured_image_cloudflare_id' => 'nullable|string',
            'gallery_images' => 'nullable|array',
            'gallery_images.*.id' => 'nullable|string',
            'gallery_images.*.url' => 'nullable|string',
            'gallery_images.*.filename' => 'nullable|string',
            'category_id' => 'sometimes|required|exists:list_categories,id',
            'tags' => 'nullable|array',
            'tags.*.name' => 'string|max:255',
            'tags.*.is_new' => 'boolean',
        ]);

        DB::transaction(function() use ($validated, $list) {
            if (isset($validated['name']) && $validated['name'] !== $list->name) {
                $validated['slug'] = Str::slug($validated['name']);
            }

            // Handle publishing logic
            if (isset($validated['is_draft']) && !$validated['is_draft'] && $list->is_draft) {
                // Publishing from draft
                if (empty($validated['scheduled_for'])) {
                    $validated['published_at'] = now();
                }
            }

            // Handle tags separately
            $tags = $validated['tags'] ?? null;
            unset($validated['tags']);

            $list->update($validated);

            // Update tags if provided
            if ($tags !== null) {
                $tagIds = [];
                
                foreach ($tags as $tagData) {
                    if (isset($tagData['is_new']) && $tagData['is_new']) {
                        // Validate profanity for new tags
                        if (!ProfanityFilterService::validateTag($tagData['name'])) {
                            throw new \Exception('Tag "' . $tagData['name'] . '" contains inappropriate content');
                        }
                        
                        // Check if tag already exists by name/slug
                        $slug = Str::slug($tagData['name']);
                        $existingTag = Tag::where('slug', $slug)->first();
                        
                        if ($existingTag) {
                            $tagIds[] = $existingTag->id;
                        } else {
                            // Create new tag
                            $tag = Tag::create([
                                'name' => $tagData['name'],
                                'slug' => $slug,
                                'color' => '#6B7280',
                                'is_active' => true,
                            ]);
                            $tagIds[] = $tag->id;
                        }
                    } else {
                        // Use existing tag - handle both object and array formats
                        $tagId = isset($tagData['id']) ? $tagData['id'] : $tagData;
                        if (is_numeric($tagId)) {
                            $tagIds[] = $tagId;
                        }
                    }
                }
                
                $list->tags()->sync($tagIds);
            }
        });

        $list->load('category', 'tags');

        return response()->json([
            'message' => 'List updated successfully',
            'list' => $list
        ]);
    }

    public function destroy($id)
    {
        $list = UserList::findOrFail($id);

        if (!$list->canEdit()) {
            abort(403, 'Unauthorized to delete this list');
        }

        $list->delete();

        return response()->json(['message' => 'List deleted successfully']);
    }

    // List Items Management
    public function addItem(Request $request, $listId)
    {
        $list = UserList::findOrFail($listId);

        if (!$list->canEdit()) {
            abort(403, 'Unauthorized to edit this list');
        }

        $validated = $request->validate([
            'type' => 'required|in:directory_entry,text,location,event',
            'directory_entry_id' => 'required_if:type,directory_entry|exists:directory_entries,id',
            'title' => 'required_if:type,text,location,event|string|max:255',
            'content' => 'nullable|string',
            'data' => 'nullable|array',
            'image' => 'nullable|string',
            'affiliate_url' => 'nullable|url',
            'notes' => 'nullable|string',
        ]);

        // Get the next order index
        $maxOrder = $list->items()->max('order_index') ?? -1;
        $validated['order_index'] = $maxOrder + 1;
        $validated['list_id'] = $listId;

        // Handle location data
        if ($validated['type'] === 'location' && isset($validated['data'])) {
            // Validate location data
            $request->validate([
                'data.latitude' => 'required|numeric|between:-90,90',
                'data.longitude' => 'required|numeric|between:-180,180',
                'data.address' => 'nullable|string',
                'data.name' => 'nullable|string',
            ]);
        }

        // Handle event data
        if ($validated['type'] === 'event' && isset($validated['data'])) {
            $request->validate([
                'data.start_date' => 'required|date',
                'data.end_date' => 'nullable|date|after:data.start_date',
                'data.location' => 'nullable|string',
                'data.url' => 'nullable|url',
            ]);
        }

        $item = ListItem::create($validated);

        return response()->json([
            'message' => 'Item added successfully',
            'item' => $item->load('place')
        ], 201);
    }

    public function updateItem(Request $request, $listId, $itemId)
    {
        $list = UserList::findOrFail($listId);

        if (!$list->canEdit()) {
            abort(403, 'Unauthorized to edit this list');
        }

        $item = $list->items()->findOrFail($itemId);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'nullable|string',
            'data' => 'nullable|array',
            'image' => 'nullable|string',
            'item_image' => 'nullable|string',
            'affiliate_url' => 'nullable|url',
            'notes' => 'nullable|string',
        ]);

        // Handle Cloudflare image ID
        if (isset($validated['item_image'])) {
            $validated['item_image_cloudflare_id'] = $validated['item_image'];
            unset($validated['item_image']);
        }

        $item->update($validated);

        return response()->json([
            'message' => 'Item updated successfully',
            'item' => $item
        ]);
    }

    public function removeItem($listId, $itemId)
    {
        $list = UserList::findOrFail($listId);

        if (!$list->canEdit()) {
            abort(403, 'Unauthorized to edit this list');
        }

        $item = $list->items()->findOrFail($itemId);
        $item->delete();

        // Reorder remaining items
        $list->items()
             ->where('order_index', '>', $item->order_index)
             ->decrement('order_index');

        return response()->json(['message' => 'Item removed successfully']);
    }

    public function reorderItems(Request $request, $listId)
    {
        $listModel = UserList::findOrFail($listId);

        if (!$listModel->canEdit()) {
            abort(403, 'Unauthorized to edit this list');
        }

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:list_items,id',
            'items.*.order_index' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($validated, $listId) {
            foreach ($validated['items'] as $itemData) {
                ListItem::where('id', $itemData['id'])
                        ->where('list_id', $listId)
                        ->update(['order_index' => $itemData['order_index']]);
            }
        });

        return response()->json(['message' => 'Items reordered successfully']);
    }

    // Search directory entries to add to list
    public function searchEntries(Request $request)
    {
        $query = Place::with(['category', 'location'])
                              ->published();

        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $entries = $query->limit(20)->get();

        return response()->json($entries);
    }
    // Add these methods to your UserListController

/**
 * Get user's personal lists
 */
public function myLists(Request $request)
{
    $user = auth()->user();
    
    $query = $user->lists()->with(['items', 'category', 'tags']);
    
    // Apply filters
    if ($request->has('search') && $request->search) {
        $query->where('name', 'like', '%' . $request->search . '%');
    }
    
    if ($request->has('visibility') && $request->visibility) {
        $query->where('visibility', $request->visibility);
    }
    
    if ($request->has('is_draft')) {
        $query->where('is_draft', $request->boolean('is_draft'));
    }
    
    // Apply sorting
    $sortBy = $request->get('sort_by', 'updated_at');
    $sortOrder = $request->get('sort_order', 'desc');
    $query->orderBy($sortBy, $sortOrder);
    
    $lists = $query->paginate(12);
    
    return response()->json($lists);
}

/**
 * Get all public lists
 */
public function publicLists(Request $request)
{
    $query = \App\Models\UserList::searchable()
        ->with(['user', 'items', 'category'])
        ->withCount('items');
    
    // Apply filters
    if ($request->has('search') && $request->search) {
        $query->where('name', 'like', '%' . $request->search . '%')
              ->orWhere('description', 'like', '%' . $request->search . '%');
    }
    
    if ($request->has('user') && $request->user) {
        $query->where('user_id', $request->user);
    }
    
    // Filter by category
    if ($request->has('category_id') && $request->category_id) {
        $query->where('category_id', $request->category_id);
    }
    
    // Apply sorting
    $sortBy = $request->get('sort_by', 'updated_at');
    $sortOrder = $request->get('sort_order', 'desc');
    
    switch ($sortBy) {
        case 'popularity':
            $query->orderBy('views_count', $sortOrder);
            break;
        case 'items_count':
            $query->orderBy('items_count', $sortOrder);
            break;
        case 'title':
            $query->orderBy('name', $sortOrder);
            break;
        case 'created_at':
            $query->orderBy('created_at', $sortOrder);
            break;
        default:
            $query->orderBy('updated_at', $sortOrder);
    }
    
    $perPage = $request->get('per_page', 12);
    $lists = $query->paginate($perPage);
    
    return response()->json($lists);
}

/**
 * Get list categories for filtering
 */
public function categories()
{
    // Return empty array for now since lists don't have categories
    return response()->json([]);
}

// Add these methods to your UserListController

/**
 * Get popular list creators
 */
public function popularCreators()
{
    $users = \App\Models\User::whereHas('lists', function($query) {
        $query->searchable();
    })->withCount(['lists' => function($query) {
        $query->searchable();
    }])->orderBy('lists_count', 'desc')
    ->limit(20)
    ->get(['id', 'name', 'lists_count']);
    
    return response()->json($users);
}

/**
 * Get public lists statistics
 */
public function publicListsStats()
{
    $stats = [
        'total_lists' => \App\Models\UserList::searchable()->count(),
        'total_users' => \App\Models\User::whereHas('lists', function($query) {
            $query->searchable();
        })->count(),
        'total_items' => \App\Models\ListItem::whereHas('list', function($query) {
            $query->searchable();
        })->count(),
        'total_categories' => \App\Models\ListCategory::where('is_active', true)->count()
    ];
    
    return response()->json($stats);
}

/**
 * Add list to user's favorites
 */
public function addToFavorites($listId)
{
    $user = auth()->user();
    $list = \App\Models\UserList::findOrFail($listId);
    
    // Check if already favorited
    $exists = \DB::table('user_list_favorites')
        ->where('user_id', $user->id)
        ->where('list_id', $listId)
        ->exists();
    
    if (!$exists) {
        \DB::table('user_list_favorites')->insert([
            'user_id' => $user->id,
            'list_id' => $listId,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
    
    return response()->json(['success' => true]);
}

/**
 * Remove list from user's favorites
 */
public function removeFromFavorites($listId)
{
    $user = auth()->user();
    
    \DB::table('user_list_favorites')
        ->where('user_id', $user->id)
        ->where('list_id', $listId)
        ->delete();
    
    return response()->json(['success' => true]);
}

/**
 * Get public lists for a specific user
 */
public function userPublicLists(\App\Models\User $user)
{
    $lists = $user->lists()
        ->searchable()  // Only public lists
        ->withCount('items')
        ->latest()
        ->paginate(12);
        
    return response()->json($lists);
}

/**
 * Get all active list categories
 */
public function getAllCategories()
{
    $categories = ListCategory::where('is_active', true)
        ->orderBy('sort_order')
        ->get(['id', 'name', 'slug', 'description']);
        
    return response()->json($categories);
}

/**
 * Search tags for autocomplete
 */
public function searchTags(Request $request)
{
    $query = $request->get('q', '');
    
    if (strlen($query) < 2) {
        return response()->json([]);
    }
    
    $tags = Tag::where('is_active', true)
        ->where(function($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
              ->orWhere('slug', 'like', "%{$query}%");
        })
        ->limit(20)
        ->get(['id', 'name', 'slug', 'color']);
        
    return response()->json($tags);
}

/**
 * Validate tag name for profanity
 */
public function validateTag(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255'
    ]);
    
    $isValid = ProfanityFilterService::validateTag($request->name);
    
    return response()->json([
        'valid' => $isValid,
        'message' => $isValid ? 'Tag is valid' : 'Tag contains inappropriate content'
    ]);
}

/**
 * Get lists for a specific user by username or custom URL
 */
public function userLists($username)
{
    // Find user by custom URL or username
    $user = \App\Models\User::where('custom_url', $username)
        ->orWhere('username', $username)
        ->first();
    
    if (!$user) {
        return response()->json([
            'message' => 'User not found'
        ], 404);
    }
    
    // Get user's public lists with optimized loading
    $lists = $user->lists()
        ->where('visibility', 'public')
        ->where('is_draft', false)
        ->with(['category:id,name,slug', 'tags:id,name,slug,color'])
        ->withCount('items')
        ->select('id', 'user_id', 'name', 'slug', 'description', 'featured_image', 'featured_image_cloudflare_id', 'is_pinned', 'pinned_at', 'view_count', 'created_at', 'updated_at')
        ->orderBy('is_pinned', 'desc')
        ->orderBy('pinned_at', 'desc')
        ->orderBy('created_at', 'desc')
        ->get();
    
    return response()->json([
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'custom_url' => $user->custom_url,
            'avatar_url' => $user->avatar_url,
            'page_title' => $user->page_title,
        ],
        'lists' => $lists,
        'total' => $lists->count()
    ]);
}

/**
 * Show a specific list by user and slug
 */
public function showBySlug($username, $slug)
{
    // Find user by custom URL or username
    $user = \App\Models\User::where('custom_url', $username)
        ->orWhere('username', $username)
        ->first();
    
    if (!$user) {
        return response()->json([
            'message' => 'User not found'
        ], 404);
    }
    
    // Find the list
    $list = $user->lists()
        ->where('slug', $slug)
        ->with(['user', 'category', 'tags', 'items' => function($query) {
            $query->orderBy('order_index')
                  ->with(['place.location', 'place.category']);
        }])
        ->first();
    
    if (!$list) {
        return response()->json([
            'message' => 'List not found'
        ], 404);
    }
    
    // Check if user can view this list
    if (!$list->canView(auth()->user())) {
        return response()->json([
            'message' => 'You do not have permission to view this list'
        ], 403);
    }
    
    // Increment view count if not owner and list is viewable
    if (!$list->isOwnedBy(auth()->user()) && $list->isPublished()) {
        $list->incrementViewCount();
    }
    
    return response()->json($list);
}



}