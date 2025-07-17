<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\User;
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
            'description' => 'nullable|string|max:1000',
            'is_public' => 'boolean',
            'avatar_image' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120', // 5MB
            'banner_image' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120', // 5MB
        ]);

        $validated['user_id'] = auth()->id();
        $validated['slug'] = Channel::generateUniqueSlug($validated['name']);

        DB::beginTransaction();
        try {
            $channel = Channel::create($validated);

            // Handle avatar upload
            if ($request->hasFile('avatar_image')) {
                $path = $request->file('avatar_image')->store('channels/avatars', 'public');
                $channel->update(['avatar_image' => $path]);
            }

            // Handle banner upload
            if ($request->hasFile('banner_image')) {
                $path = $request->file('banner_image')->store('channels/banners', 'public');
                $channel->update(['banner_image' => $path]);
            }

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
        $channel = Channel::where('slug', $slugOrId)
            ->orWhere('id', $slugOrId)
            ->with(['user:id,name,username,custom_url,avatar,avatar_cloudflare_id'])
            ->withCount(['lists', 'followers'])
            ->firstOrFail();

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
                'max:255',
                Rule::unique('channels')->ignore($channel->id),
                function ($attribute, $value, $fail) {
                    // Check against usernames
                    if (User::where('username', $value)->orWhere('custom_url', $value)->exists()) {
                        $fail('This slug is already taken by a user.');
                    }
                },
            ],
            'description' => 'nullable|string|max:1000',
            'is_public' => 'boolean',
            'avatar_image' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120', // 5MB
            'banner_image' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120', // 5MB
        ]);

        DB::beginTransaction();
        try {
            // Handle slug change
            if (isset($validated['slug']) && $validated['slug'] !== $channel->slug) {
                $validated['slug'] = Str::slug($validated['slug']);
            }

            // Handle avatar upload
            if ($request->hasFile('avatar_image')) {
                // Delete old avatar if exists
                if ($channel->avatar_image && !filter_var($channel->avatar_image, FILTER_VALIDATE_URL)) {
                    \Storage::disk('public')->delete($channel->avatar_image);
                }
                $validated['avatar_image'] = $request->file('avatar_image')->store('channels/avatars', 'public');
            }

            // Handle banner upload
            if ($request->hasFile('banner_image')) {
                // Delete old banner if exists
                if ($channel->banner_image && !filter_var($channel->banner_image, FILTER_VALIDATE_URL)) {
                    \Storage::disk('public')->delete($channel->banner_image);
                }
                $validated['banner_image'] = $request->file('banner_image')->store('channels/banners', 'public');
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
            ->with(['user:id,name,username,custom_url', 'category:id,name,slug'])
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
     * Check slug availability.
     */
    public function checkSlug(Request $request)
    {
        $request->validate([
            'slug' => 'required|string|max:255',
            'exclude_id' => 'nullable|exists:channels,id',
        ]);

        $slug = Str::slug($request->input('slug'));
        $excludeId = $request->input('exclude_id');

        // Check against channels
        $channelQuery = Channel::where('slug', $slug);
        if ($excludeId) {
            $channelQuery->where('id', '!=', $excludeId);
        }
        $channelExists = $channelQuery->exists();

        // Check against users
        $userExists = User::where('username', $slug)
            ->orWhere('custom_url', $slug)
            ->exists();

        $available = !$channelExists && !$userExists;

        return response()->json([
            'available' => $available,
            'slug' => $slug,
            'message' => $available ? 'Slug is available' : 'Slug is already taken',
        ]);
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