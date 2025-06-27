<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserList;
use App\Models\ListItem;
use App\Models\DirectoryEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserListController extends Controller
{
    public function index(Request $request)
    {
        $query = UserList::with(['user', 'items'])
                        ->withCount('items');

        // Filter by user if not admin
        if (!auth()->user()->hasAnyRole(['admin', 'manager'])) {
            $query->where('user_id', auth()->id());
        }

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
            $query->where('is_public', $request->visibility === 'public');
        }

        $lists = $query->orderBy('created_at', 'desc')->paginate(12);

        return response()->json($lists);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_public' => 'boolean',
            'featured_image' => 'nullable|string', // Base64 or URL
        ]);

        $list = UserList::create([
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_public' => $validated['is_public'] ?? true,
            'featured_image' => $validated['featured_image'] ?? null,
        ]);

        $list->load('user');
        
        return response()->json([
            'message' => 'List created successfully',
            'list' => $list
        ], 201);
    }

    public function show($id)
    {
        $list = UserList::with(['user', 'items' => function($query) {
            $query->orderBy('order_index');
        }])->findOrFail($id);

        // Check if user can view this list
        if (!$list->is_public && !$list->canEdit(auth()->user())) {
            abort(403, 'This list is private');
        }

        // Increment view count if not owner
        if (!$list->isOwnedBy(auth()->user())) {
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
            'is_public' => 'sometimes|boolean',
            'featured_image' => 'nullable|string',
        ]);

        if (isset($validated['name']) && $validated['name'] !== $list->name) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $list->update($validated);

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
            'item' => $item->load('directoryEntry')
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
            'affiliate_url' => 'nullable|url',
            'notes' => 'nullable|string',
        ]);

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
        $query = DirectoryEntry::with(['category', 'location'])
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
    
    $query = $user->lists()->with(['items']);
    
    // Apply filters
    if ($request->has('search') && $request->search) {
        $query->where('name', 'like', '%' . $request->search . '%');
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
    $query = \App\Models\UserList::where('is_public', true)
        ->with(['user', 'items'])
        ->withCount('items');
    
    // Apply filters
    if ($request->has('search') && $request->search) {
        $query->where('name', 'like', '%' . $request->search . '%')
              ->orWhere('description', 'like', '%' . $request->search . '%');
    }
    
    if ($request->has('user') && $request->user) {
        $query->where('user_id', $request->user);
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
    
    $lists = $query->paginate(12);
    
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
        $query->where('is_public', true);
    })->withCount(['lists' => function($query) {
        $query->where('is_public', true);
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
        'total_lists' => \App\Models\UserList::where('is_public', true)->count(),
        'total_users' => \App\Models\User::whereHas('lists', function($query) {
            $query->where('is_public', true);
        })->count(),
        'total_items' => \App\Models\ListItem::whereHas('list', function($query) {
            $query->where('is_public', true);
        })->count(),
        'total_categories' => 0 // Categories not implemented for lists yet
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



}