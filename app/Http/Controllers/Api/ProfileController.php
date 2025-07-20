<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserProfileCacheService;
use App\Rules\UniqueUrlSlug;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use App\Models\Post;
use App\Models\UserList;
use App\Models\Place;

class ProfileController extends Controller
{
    /**
     * Get the authenticated user's profile data
     */
    public function show(Request $request)
    {
        $user = $request->user();
        
        // Try to load counts safely
        try {
            $user->loadCount(['followers', 'following', 'lists']);
            $followers_count = $user->followers_count ?? 0;
            $following_count = $user->following_count ?? 0;
            $lists_count = $user->lists_count ?? 0;
        } catch (\Exception $e) {
            \Log::error('Error loading counts: ' . $e->getMessage());
            $followers_count = 0;
            $following_count = 0;
            $lists_count = 0;
        }
        
        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'email' => $user->email,
            'username' => $user->username,
            'custom_url' => $user->custom_url,
            'has_custom_url' => $user->hasCustomUrl(),
            'gender' => $user->gender,
            'birthdate' => $user->birthdate ? $user->birthdate->format('Y-m-d') : null,
            'bio' => $user->bio,
            'avatar_url' => $user->avatar_url,
            'cover_image_url' => $user->cover_image_url,
            'location' => $user->location,
            'website' => $user->website,
            'profile_color' => $user->profile_color,
            'page_title' => $user->page_title,
            'display_title' => $user->display_title,
            'profile_description' => $user->profile_description,
            'phone' => $user->phone,
            'show_activity' => $user->show_activity,
            'show_followers' => $user->show_followers,
            'show_following' => $user->show_following,
            'show_join_date' => $user->show_join_date,
            'show_location' => $user->show_location,
            'show_website' => $user->show_website,
            'page_logo_option' => $user->page_logo_option,
            'page_logo_cloudflare_id' => $user->page_logo_cloudflare_id,
            'page_logo_url' => $user->page_logo_url,
            'followers_count' => $followers_count,
            'following_count' => $following_count,
            'lists_count' => $lists_count,
            'created_at' => $user->created_at,
        ];
        
        // Only include timestamp fields if they exist
        if (Schema::hasColumn('users', 'avatar_updated_at')) {
            $userData['avatar_updated_at'] = $user->avatar_updated_at;
        }
        if (Schema::hasColumn('users', 'cover_updated_at')) {
            $userData['cover_updated_at'] = $user->cover_updated_at;
        }

        return response()->json([
            'user' => $userData
        ]);
    }

    /**
     * Update the authenticated user's profile
     */
    public function update(Request $request, UserProfileCacheService $cacheService)
    {
        $user = $request->user();
        
        $rules = [
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'username' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('users')->ignore($user->id)],
            'gender' => ['nullable', 'in:male,female,prefer_not_to_say'],
            'birthdate' => ['nullable', 'date'],
            'bio' => ['nullable', 'string', 'max:500'],
            'location' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'url', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'page_title' => ['nullable', 'string', 'max:100'],
            'display_title' => ['nullable', 'string', 'max:100'],
            'profile_description' => ['nullable', 'string', 'max:1000'],
            'profile_color' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'show_activity' => ['boolean'],
            'show_followers' => ['boolean'],
            'show_following' => ['boolean'],
            'show_join_date' => ['boolean'],
            'show_location' => ['boolean'],
            'show_website' => ['boolean'],
            'page_logo_option' => ['required', 'in:profile,custom,none'],
        ];
        
        // Only allow custom_url to be set if it hasn't been set before
        if (!$user->hasCustomUrl()) {
            $rules['custom_url'] = ['nullable', 'string', 'max:50', 'alpha_dash', Rule::unique('users')->ignore($user->id), new UniqueUrlSlug('user', $user->id)];
        }
        
        $validated = $request->validate($rules);
        
        // Update name field for backward compatibility
        if (isset($validated['firstname']) && isset($validated['lastname'])) {
            $validated['name'] = $validated['firstname'] . ' ' . $validated['lastname'];
        }

        $user->update($validated);
        
        // Clear cache for this user's profile
        $cacheService->clearProfileCache($user);
        
        // Reload user to get computed properties
        $user = $user->fresh();
        $user->loadCount(['followers', 'following', 'lists']);

        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'email' => $user->email,
            'username' => $user->username,
            'custom_url' => $user->custom_url,
            'has_custom_url' => $user->hasCustomUrl(),
            'gender' => $user->gender,
            'birthdate' => $user->birthdate ? $user->birthdate->format('Y-m-d') : null,
            'bio' => $user->bio,
            'avatar_url' => $user->avatar_url,
            'cover_image_url' => $user->cover_image_url,
            'location' => $user->location,
            'website' => $user->website,
            'profile_color' => $user->profile_color,
            'page_title' => $user->page_title,
            'display_title' => $user->display_title,
            'profile_description' => $user->profile_description,
            'phone' => $user->phone,
            'show_activity' => $user->show_activity,
            'show_followers' => $user->show_followers,
            'show_following' => $user->show_following,
            'show_join_date' => $user->show_join_date,
            'show_location' => $user->show_location,
            'show_website' => $user->show_website,
            'page_logo_option' => $user->page_logo_option,
            'followers_count' => $user->followers_count,
            'following_count' => $user->following_count,
            'lists_count' => $user->lists_count,
        ];
        
        // Only include timestamp fields if they exist
        if (Schema::hasColumn('users', 'avatar_updated_at')) {
            $userData['avatar_updated_at'] = $user->avatar_updated_at;
        }
        if (Schema::hasColumn('users', 'cover_updated_at')) {
            $userData['cover_updated_at'] = $user->cover_updated_at;
        }

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $userData
        ]);
    }

    /**
     * Update profile images (avatar, cover, logo)
     */
    public function updateImage(Request $request, UserProfileCacheService $cacheService)
    {
        $request->validate([
            'type' => ['required', 'in:avatar,cover,logo'],
            'cloudflare_id' => ['required', 'string'],
            'url' => ['required', 'url']
        ]);

        $user = $request->user();
        $cloudflareService = app(\App\Services\CloudflareImageService::class);
        
        // Store old image ID for deletion
        $oldImageId = null;
        
        switch ($request->type) {
            case 'avatar':
                $oldImageId = $user->avatar_cloudflare_id;
                $user->avatar_cloudflare_id = $request->cloudflare_id;
                $user->avatar = null; // Clear old local path
                // Only set timestamp if column exists
                if (Schema::hasColumn('users', 'avatar_updated_at')) {
                    $user->avatar_updated_at = now();
                }
                break;
            case 'cover':
                $oldImageId = $user->cover_cloudflare_id;
                $user->cover_cloudflare_id = $request->cloudflare_id;
                $user->cover_image = null; // Clear old local path
                // Only set timestamp if column exists
                if (Schema::hasColumn('users', 'cover_updated_at')) {
                    $user->cover_updated_at = now();
                }
                break;
            case 'logo':
                $oldImageId = $user->page_logo_cloudflare_id;
                $user->page_logo_cloudflare_id = $request->cloudflare_id;
                $user->page_logo_url = $request->url;
                break;
        }
        
        $user->save();
        
        // Clear cache for this user's profile
        $cacheService->clearProfileCache($user);
        
        // Delete old image from Cloudflare if it exists and is different
        if ($oldImageId && $oldImageId !== $request->cloudflare_id) {
            try {
                $cloudflareService->deleteImage($oldImageId);
            } catch (\Exception $e) {
                \Log::error('Failed to delete old image from Cloudflare: ' . $e->getMessage());
            }
        }

        // Reload user to get computed properties (avatar_url, cover_image_url)
        $user = $user->fresh();
        $user->loadCount(['followers', 'following', 'lists']);

        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'email' => $user->email,
            'username' => $user->username,
            'custom_url' => $user->custom_url,
            'has_custom_url' => $user->hasCustomUrl(),
            'gender' => $user->gender,
            'birthdate' => $user->birthdate ? $user->birthdate->format('Y-m-d') : null,
            'bio' => $user->bio,
            'avatar_url' => $user->avatar_url,
            'cover_image_url' => $user->cover_image_url,
            'location' => $user->location,
            'website' => $user->website,
            'profile_color' => $user->profile_color,
            'page_title' => $user->page_title,
            'display_title' => $user->display_title,
            'profile_description' => $user->profile_description,
            'phone' => $user->phone,
            'show_activity' => $user->show_activity,
            'show_followers' => $user->show_followers,
            'show_following' => $user->show_following,
            'show_join_date' => $user->show_join_date,
            'show_location' => $user->show_location,
            'show_website' => $user->show_website,
            'page_logo_option' => $user->page_logo_option,
            'followers_count' => $user->followers_count,
            'following_count' => $user->following_count,
            'lists_count' => $user->lists_count,
        ];
        
        // Only include timestamp fields if they exist
        if (Schema::hasColumn('users', 'avatar_updated_at')) {
            $userData['avatar_updated_at'] = $user->avatar_updated_at;
        }
        if (Schema::hasColumn('users', 'cover_updated_at')) {
            $userData['cover_updated_at'] = $user->cover_updated_at;
        }

        return response()->json([
            'message' => ucfirst($request->type) . ' updated successfully',
            'user' => $userData
        ]);
    }

    /**
     * Check if a custom URL is available
     */
    public function checkCustomUrl(Request $request)
    {
        $request->validate([
            'custom_url' => ['required', 'string', 'max:50', 'alpha_dash', new UniqueUrlSlug('user')]
        ]);

        $exists = User::where('custom_url', $request->custom_url)
            ->where('id', '!=', $request->user()->id)
            ->exists();

        return response()->json([
            'available' => !$exists,
            'message' => $exists ? 'This URL is already taken' : 'This URL is available'
        ]);
    }

    /**
     * Get user's page by custom URL (with @ prefix in route)
     */
    public function showByCustomUrl($username, UserProfileCacheService $cacheService)
    {
        // The route parameter is named 'username' but contains custom_url or username
        // Use cache service for better performance
        $profileData = $cacheService->getProfile($username);
        
        return response()->json($profileData);
    }

    /**
     * Update user's password
     */
    public function updatePassword(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)
                ->mixedCase()
                ->numbers()
                ->uncompromised()],
        ]);
        
        // Verify current password
        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'The provided password does not match your current password.',
                'errors' => [
                    'current_password' => ['Current password is incorrect']
                ]
            ], 401);
        }
        
        // Update password
        $user->update([
            'password' => Hash::make($validated['password'])
        ]);
        
        // Log password change event (without the password)
        \Log::info('Password changed for user', [
            'user_id' => $user->id,
            'timestamp' => now(),
            'ip_address' => $request->ip()
        ]);
        
        return response()->json([
            'message' => 'Password updated successfully'
        ]);
    }

    /**
     * Get user's activity feed (posts, lists, places)
     */
    public function getUserActivity($username, Request $request)
    {
        $user = User::where('username', $username)
            ->orWhere('custom_url', $username)
            ->firstOrFail();

        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 10);
        
        // Collect all activities in an array
        $activities = collect();
        
        // Get posts
        $posts = Post::where('user_id', $user->id)
            ->visible()
            ->with('user')
            ->latest()
            ->get()
            ->map(function ($post) {
                $post->feed_type = 'post';
                $post->feed_timestamp = $post->created_at;
                return $post;
            });
        
        // Get public lists
        $lists = UserList::where('user_id', $user->id)
            ->searchable()
            ->with(['user', 'category', 'tags'])
            ->withCount('items')
            ->latest()
            ->get()
            ->map(function ($list) {
                $list->feed_type = 'list';
                $list->feed_timestamp = $list->created_at;
                return $list;
            });
        
        // Get created places (if user creates places)
        $places = Place::where('created_by_user_id', $user->id)
            ->where('status', 'published')
            ->with(['category', 'location'])
            ->latest()
            ->get()
            ->map(function ($place) {
                $place->feed_type = 'place';
                $place->feed_timestamp = $place->created_at;
                return $place;
            });
        
        // Merge all activities and sort by timestamp
        $activities = $activities->concat($posts)
            ->concat($lists)
            ->concat($places)
            ->sortByDesc('feed_timestamp')
            ->values();
        
        // Manual pagination
        $total = $activities->count();
        $items = $activities->forPage($page, $perPage);
        
        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => $request->url()]
        );
        
        return response()->json($paginated);
    }
}