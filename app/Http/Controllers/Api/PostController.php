<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    /**
     * Create a new post
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:500',
            'media' => 'nullable|array|max:4', // Support multiple media items, max 4 images
            'media.*.url' => 'required_with:media|string|url',
            'media.*.fileId' => 'required_with:media|string',
            'media.*.metadata' => 'nullable|array',
            'media_type' => 'nullable|in:images,video',
            'expires_in_days' => 'nullable|integer|min:1|max:365',
        ]);

        // Use default expiration if not specified
        if (!isset($validated['expires_in_days'])) {
            $validated['expires_in_days'] = Post::getDefaultExpirationDays();
        }

        $post = Post::create([
            'user_id' => Auth::id(),
            'content' => $validated['content'],
            'media' => $validated['media'] ?? null,
            'media_type' => $validated['media_type'] ?? null,
            'expires_in_days' => $validated['expires_in_days'],
        ]);

        // Load relationships for response
        $post->load('user');
        $post->feed_type = 'post'; // Add feed type for frontend

        return response()->json([
            'message' => 'Post created successfully',
            'post' => $post,
        ], 201);
    }

    /**
     * Get a single post
     */
    public function show($id)
    {
        $post = Post::with('user')
            ->visible()
            ->findOrFail($id);

        $post->increment('views_count');
        $post->feed_type = 'post';

        return response()->json($post);
    }

    /**
     * Update a post
     */
    public function update(Request $request, $id)
    {
        $post = Post::where('user_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'content' => 'required|string|max:500',
            'media' => 'nullable|array|max:4',
            'media.*.url' => 'required_with:media|string|url',
            'media.*.fileId' => 'required_with:media|string',
            'media.*.metadata' => 'nullable|array',
            'media_type' => 'nullable|in:images,video',
            'removed_media' => 'nullable|array',
            'removed_media.*.fileId' => 'required|string',
        ]);

        // Handle removed media - delete from ImageKit
        if (!empty($validated['removed_media'])) {
            $imageKitService = app(\App\Services\ImageKitService::class);
            foreach ($validated['removed_media'] as $removedItem) {
                if (!empty($removedItem['fileId'])) {
                    $imageKitService->deleteImage($removedItem['fileId']);
                }
            }
        }

        // Update post data
        $updateData = [
            'content' => $validated['content'],
        ];

        // Build the updated media array
        $currentMedia = $post->media ?? [];
        $updatedMedia = [];

        // Keep media that wasn't removed
        if (is_array($currentMedia)) {
            foreach ($currentMedia as $media) {
                $wasRemoved = false;
                if (!empty($validated['removed_media'])) {
                    foreach ($validated['removed_media'] as $removed) {
                        if ($media['fileId'] === $removed['fileId']) {
                            $wasRemoved = true;
                            break;
                        }
                    }
                }
                if (!$wasRemoved) {
                    $updatedMedia[] = $media;
                }
            }
        }

        // Add new media if provided
        if (!empty($validated['media'])) {
            $updatedMedia = array_merge($updatedMedia, $validated['media']);
        }

        // Update media fields
        $updateData['media'] = !empty($updatedMedia) ? $updatedMedia : null;
        
        // Determine media type
        if (!empty($updatedMedia)) {
            $updateData['media_type'] = $validated['media_type'] ?? 'images';
        } else {
            $updateData['media_type'] = null;
        }

        $post->update($updateData);

        $post->load('user');
        $post->feed_type = 'post';

        return response()->json([
            'message' => 'Post updated successfully',
            'post' => $post,
        ]);
    }

    /**
     * Delete a post
     */
    public function destroy($id)
    {
        $post = Post::where('user_id', Auth::id())->findOrFail($id);
        
        // Delete media from ImageKit before deleting post
        if ($post->media && is_array($post->media)) {
            $imageKitService = app(\App\Services\ImageKitService::class);
            foreach ($post->media as $mediaItem) {
                if (!empty($mediaItem['fileId'])) {
                    $imageKitService->deleteImage($mediaItem['fileId']);
                }
            }
        }
        
        $post->delete();

        return response()->json([
            'message' => 'Post deleted successfully',
        ]);
    }

    /**
     * Tack a post
     */
    public function tack($id)
    {
        $post = Post::where('user_id', Auth::id())->findOrFail($id);
        $post->tack();

        $post->load('user');
        $post->feed_type = 'post';

        return response()->json([
            'message' => 'Post tacked successfully',
            'post' => $post,
        ]);
    }

    /**
     * Untack a post
     */
    public function untack($id)
    {
        $post = Post::where('user_id', Auth::id())->findOrFail($id);
        $post->untack();

        $post->load('user');
        $post->feed_type = 'post';

        return response()->json([
            'message' => 'Post untacked successfully',
            'post' => $post,
        ]);
    }

    /**
     * Get user's posts
     */
    public function userPosts(Request $request, $username)
    {
        $user = \App\Models\User::where('username', $username)
            ->orWhere('custom_url', $username)
            ->firstOrFail();

        $query = Post::where('user_id', $user->id)
            ->with('user')
            ->visible();
            
        // Filter for tacked posts only if requested
        if ($request->boolean('tacked_only')) {
            $query->where('is_tacked', true);
        }
        
        $posts = $query->orderByDesc('is_tacked')
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 20));

        // Add feed type to each post
        $posts->each(function ($post) {
            $post->feed_type = 'post';
        });

        return response()->json($posts);
    }

    /**
     * Get posts for the home feed
     */
    public function feed(Request $request)
    {
        $user = Auth::user();
        
        // Get posts from users the current user follows and their own posts
        $followingIds = $user->following()->pluck('users.id')->toArray();
        $userIds = array_merge([$user->id], $followingIds);

        $posts = Post::whereIn('user_id', $userIds)
            ->with('user')
            ->visible()
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 20));

        // Add feed type to each post
        $posts->each(function ($post) {
            $post->feed_type = 'post';
        });

        return response()->json($posts);
    }

}