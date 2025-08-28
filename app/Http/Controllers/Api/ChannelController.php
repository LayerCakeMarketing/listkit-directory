<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\User;
use App\Rules\ValidChannelSlug;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ChannelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Channel::with(['user:id,name,username,custom_url,avatar,avatar_cloudflare_id'])
            ->withCount(['lists', 'followers']);

        // Filter by search term
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Filter by owner
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // Filter by visibility
        if (!auth()->check() || !$request->boolean('include_private')) {
            $query->public();
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        
        switch ($sortBy) {
            case 'followers':
                $query->orderBy('followers_count', $sortOrder);
                break;
            case 'lists':
                $query->orderBy('lists_count', $sortOrder);
                break;
            case 'name':
                $query->orderBy('name', $sortOrder);
                break;
            default:
                $query->orderBy('created_at', $sortOrder);
        }

        return $query->paginate($request->input('per_page', 12));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['nullable', 'string', new ValidChannelSlug()],
            'description' => 'nullable|string|max:1000',
            'is_public' => 'boolean',
            'avatar_cloudflare_id' => 'nullable|string',
            'avatar_url' => 'nullable|url',
            'banner_cloudflare_id' => 'nullable|string',
            'banner_url' => 'nullable|url',
        ]);

        $validated['user_id'] = auth()->id();
        
        // Generate slug if not provided or sanitize if provided
        if (!empty($validated['slug'])) {
            $validated['slug'] = Channel::sanitizeSlug($validated['slug']);
        } else {
            $validated['slug'] = Channel::generateUniqueSlug($validated['name']);
        }

        DB::beginTransaction();
        try {
            // Map the URL fields to the image fields for backward compatibility
            if (isset($validated['avatar_url'])) {
                $validated['avatar_image'] = $validated['avatar_url'];
                unset($validated['avatar_url']);
            }
            if (isset($validated['banner_url'])) {
                $validated['banner_image'] = $validated['banner_url'];
                unset($validated['banner_url']);
            }

            $channel = Channel::create($validated);

            DB::commit();

            return response()->json([
                'message' => 'Channel created successfully',
                'channel' => $channel->load('user:id,name,username,custom_url,avatar,avatar_cloudflare_id'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create channel',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($slugOrId)
    {
        $query = Channel::with(['user:id,name,username,custom_url,avatar,avatar_cloudflare_id'])
            ->withCount(['lists', 'followers']);

        // Sanitize slug by removing @ if present
        $slugOrId = Channel::sanitizeSlug($slugOrId);

        // If it's numeric, search by ID, otherwise by slug
        if (is_numeric($slugOrId)) {
            $channel = $query->where('id', $slugOrId)->firstOrFail();
        } else {
            $channel = $query->where('slug', $slugOrId)->firstOrFail();
        }

        // Check if user can view this channel
        if (!$channel->canBeViewedBy(auth()->user())) {
            abort(403, 'You do not have permission to view this channel');
        }

        // Add lists if requested
        if (request()->boolean('include_lists')) {
            $channel->load(['lists' => function ($query) {
                $query->with(['user:id,name,username,custom_url', 'category:id,name,slug'])
                    ->searchable()
                    ->latest()
                    ->limit(10);
            }]);
        }

        // Add is_following status
        $channel->is_following = false;
        if (auth()->check()) {
            $channel->is_following = auth()->user()->isFollowingChannel($channel);
        }

        return response()->json($channel);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Channel $channel)
    {
        // Check permission
        if ($channel->user_id !== auth()->id()) {
            abort(403, 'You do not have permission to edit this channel');
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => [
                'sometimes',
                'required',
                'string',
                new ValidChannelSlug($channel->id, true),
            ],
            'description' => 'nullable|string|max:1000',
            'is_public' => 'boolean',
            'avatar_cloudflare_id' => 'nullable|string',
            'banner_cloudflare_id' => 'nullable|string',
            // Keep legacy support for file uploads
            'avatar_image' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120', // 5MB
            'banner_image' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120', // 5MB
        ]);

        DB::beginTransaction();
        try {
            // Handle slug change - only allow if not yet customized
            if (isset($validated['slug']) && $validated['slug'] !== $channel->slug) {
                // Check if slug has already been customized
                if ($channel->slug_customized) {
                    return response()->json([
                        'message' => 'Channel URL has already been customized and cannot be changed again.',
                        'errors' => ['slug' => ['This channel URL has been permanently set and cannot be changed.']]
                    ], 422);
                }
                
                $validated['slug'] = Channel::sanitizeSlug($validated['slug']);
                // Mark slug as customized since it's being changed from the auto-generated one
                $validated['slug_customized'] = true;
                $validated['slug_customized_at'] = now();
            }

            // Handle Cloudflare avatar upload
            if (isset($validated['avatar_cloudflare_id'])) {
                // Set the avatar_cloudflare_id field
                // The avatar_image field should remain null when using Cloudflare
                if ($validated['avatar_cloudflare_id']) {
                    $validated['avatar_image'] = null; // Clear old local image
                }
            }

            // Handle Cloudflare banner upload
            if (isset($validated['banner_cloudflare_id'])) {
                // Set the banner_cloudflare_id field
                // The banner_image field should remain null when using Cloudflare
                if ($validated['banner_cloudflare_id']) {
                    $validated['banner_image'] = null; // Clear old local image
                }
            }

            // Legacy: Handle avatar file upload
            if ($request->hasFile('avatar_image')) {
                // Delete old avatar if exists
                if ($channel->avatar_image && !filter_var($channel->avatar_image, FILTER_VALIDATE_URL)) {
                    \Storage::disk('public')->delete($channel->avatar_image);
                }
                $validated['avatar_image'] = $request->file('avatar_image')->store('channels/avatars', 'public');
                $validated['avatar_cloudflare_id'] = null; // Clear cloudflare ID when using local file
            }

            // Legacy: Handle banner file upload
            if ($request->hasFile('banner_image')) {
                // Delete old banner if exists
                if ($channel->banner_image && !filter_var($channel->banner_image, FILTER_VALIDATE_URL)) {
                    \Storage::disk('public')->delete($channel->banner_image);
                }
                $validated['banner_image'] = $request->file('banner_image')->store('channels/banners', 'public');
                $validated['banner_cloudflare_id'] = null; // Clear cloudflare ID when using local file
            }

            $channel->update($validated);
            DB::commit();

            return response()->json([
                'message' => 'Channel updated successfully',
                'channel' => $channel->fresh(['user:id,name,username,custom_url,avatar,avatar_cloudflare_id']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update channel',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Channel $channel)
    {
        // Check permission
        if ($channel->user_id !== auth()->id()) {
            abort(403, 'You do not have permission to delete this channel');
        }

        DB::beginTransaction();
        try {
            // Delete images if they exist
            if ($channel->avatar_image && !filter_var($channel->avatar_image, FILTER_VALIDATE_URL)) {
                \Storage::disk('public')->delete($channel->avatar_image);
            }
            if ($channel->banner_image && !filter_var($channel->banner_image, FILTER_VALIDATE_URL)) {
                \Storage::disk('public')->delete($channel->banner_image);
            }

            // Remove channel_id from lists (don't delete the lists)
            $channel->lists()->update(['channel_id' => null]);

            // Delete the channel
            $channel->delete();

            DB::commit();

            return response()->json([
                'message' => 'Channel deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to delete channel',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if a slug is available.
     */
    public function checkSlug(Request $request)
    {
        $request->validate([
            'slug' => 'required|string',
            'exclude_id' => 'nullable|integer'
        ]);
        
        $slug = Channel::sanitizeSlug($request->input('slug'));
        $excludeId = $request->input('exclude_id');
        
        // If checking for an existing channel, verify it hasn't been customized already
        if ($excludeId) {
            $channel = Channel::find($excludeId);
            if ($channel && $channel->slug_customized && $channel->slug !== $slug) {
                return response()->json([
                    'available' => false,
                    'message' => 'This channel URL has been permanently set and cannot be changed.',
                    'slug_customized' => true
                ]);
            }
        }
        
        $isAvailable = Channel::isSlugAvailable($slug, $excludeId);
        
        return response()->json([
            'available' => $isAvailable,
            'slug' => $slug,
            'message' => $isAvailable ? 'This URL is available' : 'This URL is not available'
        ]);
    }
    
    /**
     * Follow a channel.
     */
    public function follow(Channel $channel)
    {
        $user = auth()->user();

        // Check if user is trying to follow their own channel
        if ($channel->user_id === $user->id) {
            return response()->json([
                'message' => 'You cannot follow your own channel',
            ], 400);
        }

        // Check if already following
        if ($channel->followers()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'message' => 'You are already following this channel',
            ], 400);
        }

        $channel->followers()->attach($user->id);
        
        // Create notification
        \App\Models\Notification::createFollowNotification($user, $channel);

        return response()->json([
            'message' => 'Successfully followed the channel',
            'followers_count' => $channel->followers()->count(),
        ]);
    }

    /**
     * Unfollow a channel.
     */
    public function unfollow(Channel $channel)
    {
        $user = auth()->user();

        $detached = $channel->followers()->detach($user->id);

        if ($detached === 0) {
            return response()->json([
                'message' => 'You are not following this channel',
            ], 400);
        }

        return response()->json([
            'message' => 'Successfully unfollowed the channel',
            'followers_count' => $channel->followers()->count(),
        ]);
    }

    /**
     * Get followers of a channel.
     */
    public function followers(Channel $channel, Request $request)
    {
        $followers = $channel->followers()
            ->select('users.id', 'users.name', 'users.username', 'users.custom_url', 'users.avatar', 'users.avatar_cloudflare_id')
            ->paginate($request->input('per_page', 20));

        return response()->json($followers);
    }

    /**
     * Get lists of a channel.
     */
    public function lists(Channel $channel, Request $request)
    {
        $query = $channel->lists()
            ->with(['user:id,name,username,custom_url', 'channel:id,name,slug,avatar_cloudflare_id', 'category:id,name,slug'])
            ->withCount('items');

        // Only show public lists to non-owners
        if (!auth()->check() || $channel->user_id !== auth()->id()) {
            $query->searchable();
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($request->input('per_page', 12));
    }

    /**
     * Get chains of a channel.
     */
    public function chains(Channel $channel, Request $request)
    {
        // Use policy to check authorization
        $this->authorize('viewChains', $channel);

        $query = $channel->chains()
            ->with(['owner', 'lists' => function($query) {
                $query->select('lists.id', 'lists.name', 'lists.slug', 'lists.visibility', 'lists.featured_image')
                    ->withCount('items');
            }])
            ->withCount('lists');

        // Only show public chains to non-owners
        if (!auth()->check() || $channel->user_id !== auth()->id()) {
            $query->where('visibility', 'public')
                  ->where('status', 'published');
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $chains = $query->paginate($request->input('per_page', 12));
        
        return \App\Http\Resources\ChannelChainResource::collection($chains);
    }

    /**
     * Show a specific list belonging to a channel.
     */
    public function showList(Channel $channel, $listSlug)
    {
        $list = $channel->lists()
            ->where('slug', $listSlug)
            ->with(['owner', 'user', 'channel', 'category', 'tags', 'items' => function($query) {
                $query->orderBy('order_index')
                      ->with(['place.location', 'place.category']);
            }])
            ->firstOrFail();

        // Check if user can view this list
        if (!$list->canView(auth()->user())) {
            abort(403, 'You do not have permission to view this list');
        }

        // Increment view count if not owner and list is viewable
        if (!$list->isOwnedBy(auth()->user()) && $list->isPublished()) {
            $list->incrementViewCount();
        }

        // Add saved status if user is authenticated
        if (auth()->check()) {
            $list->is_saved = $list->isSavedBy(auth()->user());
        }

        return response()->json($list);
    }


    /**
     * Get channels for the authenticated user.
     */
    public function myChannels(Request $request)
    {
        $channels = auth()->user()->channels()
            ->withCount(['lists', 'followers'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($channels);
    }

    /**
     * Get followed channels for the authenticated user.
     */
    public function followedChannels(Request $request)
    {
        $channels = auth()->user()->followedChannels()
            ->with(['user:id,name,username,custom_url,avatar,avatar_cloudflare_id'])
            ->withCount(['lists', 'followers'])
            ->paginate($request->input('per_page', 12));

        return response()->json($channels);
    }
}