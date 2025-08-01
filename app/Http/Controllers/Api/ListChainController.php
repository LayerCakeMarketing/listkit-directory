<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ListChain;
use App\Models\ListChainItem;
use App\Models\UserList;
use App\Models\User;
use App\Http\Requests\StoreChannelChainRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ListChainController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        \Log::info('Chain index request', [
            'user_id' => $user ? $user->id : null,
            'owner_param' => $request->get('owner'),
            'per_page' => $request->get('per_page', 12)
        ]);
        
        $query = ListChain::with(['owner', 'lists' => function($query) {
                $query->select('lists.id', 'lists.name', 'lists.slug', 'lists.visibility', 'lists.featured_image')
                    ->withCount('items');
            }]);
            
        // When owner=me, only show user's own chains
        if ($request->get('owner') === 'me' && $user) {
            $query->where('owner_type', 'App\\Models\\User')
                  ->where('owner_id', $user->id);
        } else {
            // Otherwise use visible scope
            $query->visible($user);
        }
        
        $chains = $query
            ->when($request->get('search'), function($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->withCount('lists')
            ->orderBy($request->get('sort', 'created_at'), $request->get('order', 'desc'))
            ->paginate($request->get('per_page', 12));
            
        \Log::info('Chains found', [
            'total' => $chains->total(),
            'count' => $chains->count()
        ]);

        return response()->json($chains);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreChannelChainRequest $request)
    {
        $user = $request->user();
        
        if (!$user) {
            \Log::error('Chain creation: No authenticated user');
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        
        \Log::info('Chain creation attempt by user: ' . $user->id);
        
        // Get validated data from the form request
        $validated = $request->validated();

        // Lists permission check is now handled in the form request validation

        DB::beginTransaction();
        try {
            // Determine owner - authorization is handled in form request
            $ownerType = $validated['owner_type'] ?? 'App\Models\User';
            $ownerId = $validated['owner_id'] ?? $user->id;
            
            // Create the chain using the new method that handles unique slugs
            $chain = ListChain::createWithUniqueSlug([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'cover_image' => $validated['cover_image'] ?? null,
                'cover_cloudflare_id' => $validated['cover_cloudflare_id'] ?? null,
                'owner_type' => $ownerType,
                'owner_id' => $ownerId,
                'visibility' => $validated['visibility'],
                'status' => $validated['status'],
                'metadata' => $validated['metadata'] ?? null,
                'published_at' => $validated['status'] === 'published' ? now() : null
            ]);

            // Add lists to the chain
            foreach ($validated['lists'] as $index => $listData) {
                ListChainItem::create([
                    'list_chain_id' => $chain->id,
                    'list_id' => $listData['list_id'],
                    'order_index' => $index,
                    'label' => $listData['label'] ?? null,
                    'description' => $listData['description'] ?? null
                ]);
            }

            // Update lists count
            $chain->updateListsCount();

            DB::commit();

            return response()->json([
                'message' => 'Chain created successfully',
                'chain' => $chain->load(['owner', 'lists'])
            ], 201);
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollback();
            \Log::error('Chain creation database error: ' . $e->getMessage(), [
                'code' => $e->getCode(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
                'data' => $validated
            ]);
            
            // Check for specific constraint violations
            if (strpos($e->getMessage(), 'list_chains_slug_unique') !== false) {
                return response()->json([
                    'message' => 'A chain with a similar name already exists. Please try a different name.',
                    'error' => 'duplicate_slug'
                ], 422);
            }
            
            return response()->json([
                'message' => 'Failed to create chain',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Chain creation failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'data' => $validated
            ]);
            return response()->json([
                'message' => 'Failed to create chain',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        
        $chain = ListChain::with(['owner', 'lists' => function($query) {
                $query->select('lists.*')
                    ->withCount('items');
            }])
            ->visible($user)
            ->findOrFail($id);

        // Increment view count
        $chain->incrementViews();

        return response()->json($chain);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $chain = ListChain::findOrFail($id);

        if (!$chain->canEdit($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'cover_image' => 'nullable|string',
            'cover_cloudflare_id' => 'nullable|string',
            'visibility' => 'sometimes|required|in:public,private,unlisted',
            'status' => 'sometimes|required|in:draft,published,archived',
            'lists' => 'sometimes|array|min:2',
            'lists.*.list_id' => 'required|exists:lists,id',
            'lists.*.label' => 'nullable|string|max:100',
            'lists.*.description' => 'nullable|string|max:500',
            'metadata' => 'nullable|array'
        ]);

        DB::beginTransaction();
        try {
            // Update chain details
            $updateData = array_filter($validated, function($key) {
                return !in_array($key, ['lists']);
            }, ARRAY_FILTER_USE_KEY);

            if ($validated['status'] === 'published' && !$chain->published_at) {
                $updateData['published_at'] = now();
            }

            $chain->update($updateData);

            // Update lists if provided
            if (isset($validated['lists'])) {
                // Verify permissions for new lists
                $listIds = collect($validated['lists'])->pluck('list_id');
                $lists = UserList::whereIn('id', $listIds)->get();
                
                foreach ($lists as $list) {
                    if ($list->visibility !== 'public' && !$list->canEdit($user)) {
                        DB::rollback();
                        return response()->json([
                            'message' => "You don't have permission to add list '{$list->name}' to a chain"
                        ], 403);
                    }
                }

                // Remove old items
                $chain->items()->delete();

                // Add new items
                foreach ($validated['lists'] as $index => $listData) {
                    ListChainItem::create([
                        'list_chain_id' => $chain->id,
                        'list_id' => $listData['list_id'],
                        'order_index' => $index,
                        'label' => $listData['label'] ?? null,
                        'description' => $listData['description'] ?? null
                    ]);
                }

                $chain->updateListsCount();
            }

            DB::commit();

            return response()->json([
                'message' => 'Chain updated successfully',
                'chain' => $chain->fresh()->load(['owner', 'lists'])
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Failed to update chain',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $chain = ListChain::findOrFail($id);

        if (!$chain->canEdit($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $chain->delete();

        return response()->json(['message' => 'Chain deleted successfully']);
    }

    /**
     * Reorder lists in a chain
     */
    public function reorder(Request $request, $id)
    {
        $user = $request->user();
        $chain = ListChain::findOrFail($id);

        if (!$chain->canEdit($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'lists' => 'required|array',
            'lists.*.id' => 'required|exists:list_chain_items,id',
            'lists.*.order_index' => 'required|integer|min:0'
        ]);

        DB::beginTransaction();
        try {
            foreach ($validated['lists'] as $item) {
                ListChainItem::where('id', $item['id'])
                    ->where('list_chain_id', $chain->id)
                    ->update(['order_index' => $item['order_index']]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Chain reordered successfully',
                'chain' => $chain->fresh()->load('lists')
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Failed to reorder chain',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
