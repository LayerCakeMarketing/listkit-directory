<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SavedCollection;
use App\Models\SavedItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SavedCollectionController extends Controller
{
    /**
     * Get all collections for the authenticated user
     */
    public function index(Request $request)
    {
        $collections = $request->user()
            ->savedCollections()
            ->withCount('savedItems')
            ->get();

        // Add uncategorized count
        $uncategorizedCount = $request->user()
            ->savedItems()
            ->whereNull('collection_id')
            ->count();

        return response()->json([
            'collections' => $collections,
            'uncategorized_count' => $uncategorizedCount
        ]);
    }

    /**
     * Create a new collection
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'color' => 'nullable|string|in:' . implode(',', array_keys(SavedCollection::availableColors())),
            'icon' => 'nullable|string|in:' . implode(',', array_keys(SavedCollection::availableIcons())),
        ]);

        $collection = $request->user()->savedCollections()->create($validated);

        return response()->json([
            'collection' => $collection->load('savedItems')
        ], 201);
    }

    /**
     * Get a single collection
     */
    public function show(Request $request, SavedCollection $collection)
    {
        if ($collection->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json([
            'collection' => $collection->load('savedItems.saveable')
        ]);
    }

    /**
     * Update a collection
     */
    public function update(Request $request, SavedCollection $collection)
    {
        if ($collection->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:500',
            'color' => 'nullable|string|in:' . implode(',', array_keys(SavedCollection::availableColors())),
            'icon' => 'nullable|string|in:' . implode(',', array_keys(SavedCollection::availableIcons())),
            'order_index' => 'sometimes|integer|min:0'
        ]);

        $collection->update($validated);

        return response()->json([
            'collection' => $collection->fresh()->loadCount('savedItems')
        ]);
    }

    /**
     * Delete a collection
     */
    public function destroy(Request $request, SavedCollection $collection)
    {
        if ($collection->user_id !== $request->user()->id) {
            abort(403);
        }

        // Move all items to uncategorized
        $collection->savedItems()->update(['collection_id' => null]);
        
        $collection->delete();

        return response()->json([
            'message' => 'Collection deleted successfully'
        ]);
    }

    /**
     * Add items to a collection
     */
    public function addItems(Request $request, SavedCollection $collection)
    {
        if ($collection->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'item_ids' => 'required|array',
            'item_ids.*' => 'integer|exists:saved_items,id'
        ]);

        // Verify ownership of all items
        $items = SavedItem::whereIn('id', $validated['item_ids'])
            ->where('user_id', $request->user()->id)
            ->get();

        if ($items->count() !== count($validated['item_ids'])) {
            return response()->json(['error' => 'Invalid items'], 403);
        }

        // Update collection for all items
        SavedItem::whereIn('id', $validated['item_ids'])
            ->update(['collection_id' => $collection->id]);

        return response()->json([
            'message' => 'Items added to collection',
            'updated_count' => $items->count()
        ]);
    }

    /**
     * Remove items from a collection
     */
    public function removeItems(Request $request, SavedCollection $collection)
    {
        if ($collection->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'item_ids' => 'required|array',
            'item_ids.*' => 'integer|exists:saved_items,id'
        ]);

        // Update collection to null for specified items
        $updated = SavedItem::whereIn('id', $validated['item_ids'])
            ->where('user_id', $request->user()->id)
            ->where('collection_id', $collection->id)
            ->update(['collection_id' => null]);

        return response()->json([
            'message' => 'Items removed from collection',
            'updated_count' => $updated
        ]);
    }

    /**
     * Move an item to a different collection
     */
    public function moveItem(Request $request, SavedItem $item)
    {
        if ($item->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'collection_id' => 'nullable|exists:saved_collections,id'
        ]);

        // If collection_id is provided, verify ownership
        if ($validated['collection_id']) {
            $collection = SavedCollection::find($validated['collection_id']);
            if ($collection->user_id !== $request->user()->id) {
                abort(403);
            }
        }

        $item->update(['collection_id' => $validated['collection_id']]);

        return response()->json([
            'message' => 'Item moved successfully',
            'item' => $item->fresh()->load('collection')
        ]);
    }

    /**
     * Reorder collections
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'collection_ids' => 'required|array',
            'collection_ids.*' => 'integer|exists:saved_collections,id'
        ]);

        $user = $request->user();
        
        // Verify ownership of all collections
        $collections = SavedCollection::whereIn('id', $validated['collection_ids'])
            ->where('user_id', $user->id)
            ->get();

        if ($collections->count() !== count($validated['collection_ids'])) {
            return response()->json(['error' => 'Invalid collections'], 403);
        }

        // Update order indexes
        DB::transaction(function () use ($validated, $user) {
            foreach ($validated['collection_ids'] as $index => $collectionId) {
                SavedCollection::where('id', $collectionId)
                    ->where('user_id', $user->id)
                    ->update(['order_index' => $index]);
            }
        });

        return response()->json([
            'message' => 'Collections reordered successfully'
        ]);
    }
}