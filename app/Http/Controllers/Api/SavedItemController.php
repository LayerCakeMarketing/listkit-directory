<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SavedItem;
use App\Models\Place;
use App\Models\UserList;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SavedItemController extends Controller
{
    /**
     * Display a listing of saved items.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->savedItems();

        // Filter by type if specified
        if ($request->has('type')) {
            $query->ofType($request->type);
        }

        // Filter by collection if specified
        if ($request->has('collection')) {
            $query->inCollection($request->collection);
        }

        // Eager load relationships based on type
        $query->with(['saveable' => function ($query) {
            if ($query->getModel() instanceof Place) {
                $query->with(['category', 'location']);
            } elseif ($query->getModel() instanceof UserList) {
                $query->with(['user', 'category'])->withCount('items');
            } elseif ($query->getModel() instanceof Region) {
                $query->with(['parent.parent']);
            }
        }, 'collection']);

        $savedItems = $query->paginate($request->get('per_page', 20));

        // Transform the response
        $savedItems->getCollection()->transform(function ($item) {
            $data = [
                'id' => $item->id,
                'type' => $item->type,
                'saveable_id' => $item->saveable_id, // Include the ID even if item is deleted
                'notes' => $item->notes,
                'saved_at' => $item->created_at,
                'collection' => $item->collection ? [
                    'id' => $item->collection->id,
                    'name' => $item->collection->name,
                    'color' => $item->collection->color,
                    'icon' => $item->collection->icon,
                ] : null,
                'item' => null,
            ];

            // Format the saveable item based on type
            if ($item->saveable) {
                switch ($item->type) {
                    case 'place':
                        $data['item'] = [
                            'id' => $item->saveable->id,
                            'title' => $item->saveable->title,
                            'description' => $item->saveable->description,
                            'category' => $item->saveable->category?->name,
                            'location' => $item->saveable->location?->city . ', ' . $item->saveable->location?->state,
                            'image_url' => $item->saveable->featured_image_url,
                            'url' => $item->saveable->canonical_url,
                        ];
                        break;
                    
                    case 'list':
                        $data['item'] = [
                            'id' => $item->saveable->id,
                            'name' => $item->saveable->name,
                            'description' => $item->saveable->description,
                            'items_count' => $item->saveable->items_count ?? 0,
                            'category' => $item->saveable->category?->name,
                            'user' => [
                                'name' => $item->saveable->user->name,
                                'username' => $item->saveable->user->username,
                            ],
                            'image_url' => $item->saveable->featured_image_url,
                            'url' => '/@' . ($item->saveable->user->custom_url ?? $item->saveable->user->username) . '/' . $item->saveable->slug,
                        ];
                        break;
                    
                    case 'region':
                        $region = $item->saveable;
                        $url = '/regions/' . $region->slug; // Default for state-level
                        
                        // Build hierarchical URL for city/neighborhood regions
                        if ($region->type === 'city' && $region->parent) {
                            $url = '/regions/' . $region->parent->slug . '/' . $region->slug;
                        } elseif ($region->type === 'neighborhood' && $region->parent && $region->parent->parent) {
                            $url = '/regions/' . $region->parent->parent->slug . '/' . $region->parent->slug . '/' . $region->slug;
                        }
                        
                        $data['item'] = [
                            'id' => $region->id,
                            'name' => $region->name,
                            'full_name' => $region->full_name,
                            'type' => $region->type,
                            'level' => $region->level,
                            'slug' => $region->slug,
                            'parent' => $region->parent?->name,
                            'parent_slug' => $region->parent?->slug,
                            'place_count' => $region->cached_place_count ?? 0,
                            'url' => $url,
                        ];
                        break;
                }
            }

            return $data;
        });

        return response()->json($savedItems);
    }

    /**
     * Store a newly created saved item.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:place,list,region',
            'id' => 'required|integer',
            'notes' => 'nullable|string|max:255',
            'collection_id' => 'nullable|exists:saved_collections,id',
        ]);

        // Map type to model class
        $modelMap = [
            'place' => Place::class,
            'list' => UserList::class,
            'region' => Region::class,
        ];

        $modelClass = $modelMap[$request->type];
        
        // Find the model
        $model = $modelClass::findOrFail($request->id);

        // Check if already saved
        $existing = $request->user()->savedItems()
            ->where('saveable_type', $modelClass)
            ->where('saveable_id', $request->id)
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Item already saved',
                'saved_item' => $existing,
            ], 200);
        }

        // If collection_id is provided, verify ownership
        if ($request->collection_id) {
            $collection = \App\Models\SavedCollection::find($request->collection_id);
            if (!$collection || $collection->user_id !== $request->user()->id) {
                return response()->json(['message' => 'Invalid collection'], 403);
            }
        }

        // Create saved item
        $savedItem = $request->user()->savedItems()->create([
            'saveable_type' => $modelClass,
            'saveable_id' => $request->id,
            'notes' => $request->notes,
            'collection_id' => $request->collection_id,
        ]);

        return response()->json([
            'message' => 'Item saved successfully',
            'saved_item' => $savedItem->load('saveable'),
        ], 201);
    }

    /**
     * Remove the specified saved item.
     */
    public function destroy(Request $request, $type, $id): JsonResponse
    {
        // Map type to model class
        $modelMap = [
            'place' => Place::class,
            'list' => UserList::class,
            'region' => Region::class,
        ];

        if (!isset($modelMap[$type])) {
            return response()->json(['message' => 'Invalid type'], 400);
        }

        $modelClass = $modelMap[$type];

        $deleted = $request->user()->savedItems()
            ->where('saveable_type', $modelClass)
            ->where('saveable_id', $id)
            ->delete();

        if ($deleted) {
            return response()->json(['message' => 'Item unsaved successfully']);
        }

        return response()->json(['message' => 'Item not found in saved items'], 404);
    }

    /**
     * Check if items are saved by the current user.
     */
    public function checkSaved(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.type' => 'required|in:place,list,region',
            'items.*.id' => 'required|integer',
        ]);

        $savedStatus = [];
        
        foreach ($request->items as $item) {
            $modelMap = [
                'place' => Place::class,
                'list' => UserList::class,
                'region' => Region::class,
            ];

            $modelClass = $modelMap[$item['type']];
            
            $isSaved = $request->user()->savedItems()
                ->where('saveable_type', $modelClass)
                ->where('saveable_id', $item['id'])
                ->exists();

            $savedStatus[] = [
                'type' => $item['type'],
                'id' => $item['id'],
                'is_saved' => $isSaved,
            ];
        }

        return response()->json(['items' => $savedStatus]);
    }

    /**
     * Get saved items for list creation.
     */
    public function forListCreation(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Get collections
        $collections = \App\Models\SavedCollection::where('user_id', $user->id)
            ->withCount('savedItems')
            ->get()
            ->map(function ($collection) {
                return [
                    'id' => $collection->id,
                    'name' => $collection->name,
                    'items_count' => $collection->saved_items_count,
                ];
            });
            
        // Get uncategorized count
        $uncategorizedCount = $user->savedItems()
            ->whereNull('collection_id')
            ->count();
        
        // Get all types of saved items (places, regions, lists)
        $savedItems = $user->savedItems()
            ->with(['saveable', 'collection'])
            ->get()
            ->map(function ($item) {
                if (!$item->saveable) {
                    return null;
                }
                
                $saveable = $item->saveable;
                $type = class_basename($saveable);
                
                // Base structure for all types
                $baseData = [
                    'id' => $saveable->id,
                    'type' => strtolower($type),
                    'saved_at' => $item->created_at,
                    'collection_id' => $item->collection_id,
                    'collection_name' => $item->collection?->name,
                ];
                
                // Add type-specific fields
                switch ($type) {
                    case 'Place':
                        $location = '';
                        if ($saveable->region) {
                            $location = $saveable->region->name;
                        }
                        
                        return array_merge($baseData, [
                            'title' => $saveable->title,
                            'description' => $saveable->description,
                            'category' => $saveable->category?->name,
                            'location' => $location,
                            'image_url' => $saveable->featured_image_url,
                        ]);
                        
                    case 'Region':
                        // Load parent relationship if not loaded
                        if (!$saveable->relationLoaded('parent') && $saveable->parent_id) {
                            $saveable->load('parent');
                        }
                        
                        return array_merge($baseData, [
                            'title' => $saveable->name,
                            'description' => $saveable->description,
                            'category' => 'Region',
                            'location' => $saveable->parent ? $saveable->parent->name : '',
                            'image_url' => $saveable->featured_image,
                            'region_type' => $saveable->type,
                        ]);
                        
                    case 'UserList':
                        return array_merge($baseData, [
                            'title' => $saveable->name,
                            'description' => $saveable->description,
                            'category' => $saveable->category?->name ?? 'List',
                            'location' => '',
                            'image_url' => $saveable->featured_image_url,
                            'author' => $saveable->user?->name,
                            'item_count' => $saveable->items_count ?? 0,
                        ]);
                        
                    default:
                        return null;
                }
            })
            ->filter() // Remove null values
            ->values(); // Reset array keys

        return response()->json([
            'items' => $savedItems,
            'collections' => $collections,
            'uncategorized_count' => $uncategorizedCount,
            'total_count' => $savedItems->count()
        ]);
    }
}