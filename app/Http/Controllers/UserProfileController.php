<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserList;
use App\Models\DirectoryEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class UserProfileController extends Controller
{
    public function show(Request $request, $username)
    {
        // Find user by custom URL, username, or ID
        $user = User::findByUrlSlug($username);
        
        if (!$user) {
            abort(404, 'User not found');
        }

        // Check if profile is public or user has permission to view
        $currentUser = Auth::user();
        if (!$user->is_public && (!$currentUser || $currentUser->id !== $user->id)) {
            abort(403, 'This profile is private');
        }

        // Increment profile views (not for own profile)
        if (!$currentUser || $currentUser->id !== $user->id) {
            $user->incrementProfileViews();
        }

        // Get user's data
        $profileData = $this->getProfileData($user, $currentUser);

        return Inertia::render('User/Profile', $profileData);
    }

    public function edit(Request $request)
    {
        $user = Auth::user();
        
        $userData = $user->only([
            'id', 'name', 'username', 'email', 'phone', 'page_title', 'display_title', 'bio', 
            'profile_description', 'location', 'website', 'birth_date',
            'avatar', 'cover_image', 'avatar_cloudflare_id', 'cover_cloudflare_id', 'page_logo_cloudflare_id', 'page_logo_option',
            'social_links', 'custom_url', 'custom_css', 'theme_settings', 'profile_color', 
            'show_activity', 'show_followers', 'show_following', 'show_join_date', 
            'show_location', 'show_website', 'is_public'
        ]);
        
        // Add computed image URLs for edit form
        $userData['avatar_url'] = $user->getAvatarUrl();
        $userData['gravatar_url'] = $user->getGravatarUrl();
        $userData['default_avatar_url'] = $user->getDefaultAvatarUrl();
        $userData['cover_image_url'] = $user->getCoverImageUrl();
        $userData['page_logo_url'] = $user->getPageLogoUrl();
        $userData['has_custom_avatar'] = $user->hasCustomAvatar();
        $userData['has_custom_cover'] = $user->hasCustomCoverImage();
        $userData['has_custom_page_logo'] = $user->hasCustomPageLogo();

        return Inertia::render('User/EditProfile', [
            'user' => $userData
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'page_title' => 'nullable|string|max:255',
            'display_title' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:500',
            'profile_description' => 'nullable|string|max:1000',
            'location' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'birth_date' => 'nullable|date|before:today',
            'custom_url' => 'nullable|string|max:50|unique:users,custom_url,' . $user->id . '|regex:/^[a-zA-Z0-9_-]+$/',
            'custom_css' => 'nullable|string|max:5000',
            'profile_color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'show_activity' => 'boolean',
            'show_followers' => 'boolean',
            'show_following' => 'boolean',
            'show_join_date' => 'boolean',
            'show_location' => 'boolean',
            'show_website' => 'boolean',
            'is_public' => 'boolean',
            'social_links' => 'nullable|array',
            'social_links.twitter' => 'nullable|string|max:255',
            'social_links.instagram' => 'nullable|string|max:255',
            'social_links.github' => 'nullable|string|max:255',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:14336',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:14336',
            'avatar_cloudflare_id' => 'nullable|string|max:255',
            'cover_cloudflare_id' => 'nullable|string|max:255',
            'page_logo_cloudflare_id' => 'nullable|string|max:255',
            'page_logo_option' => 'nullable|string|in:initials,profile,custom',
            'remove_avatar' => 'boolean',
            'remove_cover' => 'boolean',
            'remove_page_logo' => 'boolean',
        ]);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar) {
                \Storage::disk('public')->delete($user->avatar);
            }
            // Delete old Cloudflare avatar if exists
            if ($user->avatar_cloudflare_id) {
                $imageService = app(\App\Services\CloudflareImageService::class);
                $imageService->deleteImage($user->avatar_cloudflare_id);
            }
            
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar'] = $avatarPath;
            // Clear Cloudflare ID when uploading local file
            $validated['avatar_cloudflare_id'] = null;
        } elseif ($request->filled('avatar_cloudflare_id')) {
            // Using Cloudflare image - clear local avatar
            if ($user->avatar) {
                \Storage::disk('public')->delete($user->avatar);
            }
            // Delete old Cloudflare avatar if exists and different
            if ($user->avatar_cloudflare_id && $user->avatar_cloudflare_id !== $request->input('avatar_cloudflare_id')) {
                $imageService = app(\App\Services\CloudflareImageService::class);
                $imageService->deleteImage($user->avatar_cloudflare_id);
            }
            $validated['avatar'] = null;
            // avatar_cloudflare_id is already in validated array
        } elseif ($request->input('remove_avatar')) {
            // Remove existing avatar
            if ($user->avatar) {
                \Storage::disk('public')->delete($user->avatar);
            }
            // Delete Cloudflare avatar if exists
            if ($user->avatar_cloudflare_id) {
                $imageService = app(\App\Services\CloudflareImageService::class);
                $imageService->deleteImage($user->avatar_cloudflare_id);
            }
            $validated['avatar'] = null;
            $validated['avatar_cloudflare_id'] = null;
        }

        // Handle cover image upload
        if ($request->hasFile('cover_image')) {
            // Delete old cover image if exists
            if ($user->cover_image) {
                \Storage::disk('public')->delete($user->cover_image);
            }
            // Delete old Cloudflare cover if exists
            if ($user->cover_cloudflare_id) {
                $imageService = app(\App\Services\CloudflareImageService::class);
                $imageService->deleteImage($user->cover_cloudflare_id);
            }
            
            $coverPath = $request->file('cover_image')->store('covers', 'public');
            $validated['cover_image'] = $coverPath;
            // Clear Cloudflare ID when uploading local file
            $validated['cover_cloudflare_id'] = null;
        } elseif ($request->filled('cover_cloudflare_id')) {
            // Using Cloudflare image - clear local cover image
            if ($user->cover_image) {
                \Storage::disk('public')->delete($user->cover_image);
            }
            // Delete old Cloudflare cover if exists and different
            if ($user->cover_cloudflare_id && $user->cover_cloudflare_id !== $request->input('cover_cloudflare_id')) {
                $imageService = app(\App\Services\CloudflareImageService::class);
                $imageService->deleteImage($user->cover_cloudflare_id);
            }
            $validated['cover_image'] = null;
            // cover_cloudflare_id is already in validated array
        } elseif ($request->input('remove_cover')) {
            // Remove existing cover image
            if ($user->cover_image) {
                \Storage::disk('public')->delete($user->cover_image);
            }
            // Delete Cloudflare cover if exists
            if ($user->cover_cloudflare_id) {
                $imageService = app(\App\Services\CloudflareImageService::class);
                $imageService->deleteImage($user->cover_cloudflare_id);
            }
            $validated['cover_image'] = null;
            $validated['cover_cloudflare_id'] = null;
        }

        // Handle page logo upload
        if ($request->filled('page_logo_cloudflare_id')) {
            // Delete old page logo from Cloudflare if exists
            if ($user->page_logo_cloudflare_id && $user->page_logo_cloudflare_id !== $request->input('page_logo_cloudflare_id')) {
                $imageService = app(\App\Services\CloudflareImageService::class);
                $imageService->deleteImage($user->page_logo_cloudflare_id);
            }
            // page_logo_cloudflare_id is already in validated array
        } elseif ($request->input('remove_page_logo')) {
            // Remove existing page logo
            if ($user->page_logo_cloudflare_id) {
                $imageService = app(\App\Services\CloudflareImageService::class);
                $imageService->deleteImage($user->page_logo_cloudflare_id);
            }
            $validated['page_logo_cloudflare_id'] = null;
        }

        $user->update($validated);
        $user->recordActivity('updated_profile', $user);

        // For AJAX requests (like from UserSettingsModal), return JSON response
        if ($request->wantsJson() || $request->header('X-Inertia')) {
            return back()->with('success', 'Profile updated successfully!');
        }

        return redirect()->route('user.profile', ['username' => $user->getUrlSlug()])
                        ->with('success', 'Profile updated successfully!');
    }

    public function follow(Request $request, $username)
    {
        $currentUser = Auth::user();
        $targetUser = User::findByUrlSlug($username);

        if (!$targetUser) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $success = $currentUser->follow($targetUser);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Now following ' . $targetUser->getDisplayName() : 'Already following this user',
            'is_following' => true
        ]);
    }

    public function unfollow(Request $request, $username)
    {
        $currentUser = Auth::user();
        $targetUser = User::findByUrlSlug($username);

        if (!$targetUser) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $success = $currentUser->unfollow($targetUser);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Unfollowed ' . $targetUser->getDisplayName() : 'Not following this user',
            'is_following' => false
        ]);
    }

    public function pinList(Request $request, $listId)
    {
        $user = Auth::user();
        $list = UserList::findOrFail($listId);

        if ($list->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $success = $user->pinList($list);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'List pinned to profile' : 'List is already pinned'
        ]);
    }

    public function unpinList(Request $request, $listId)
    {
        $user = Auth::user();
        $list = UserList::findOrFail($listId);

        if ($list->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $success = $user->unpinList($list);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'List unpinned from profile' : 'List was not pinned'
        ]);
    }

    public function followers(Request $request, $username)
    {
        $user = User::findByUrlSlug($username);
        
        if (!$user || (!$user->show_followers && (!Auth::user() || Auth::user()->id !== $user->id))) {
            abort(404);
        }

        $followers = $user->followers()
                         ->select(['users.id', 'users.name', 'users.username', 'users.display_title', 'users.avatar', 'users.custom_url', 'users.email'])
                         ->paginate(20);
        
        // Add avatar URLs for each follower
        $followers->getCollection()->transform(function ($follower) {
            $follower->avatar_url = $follower->getAvatarUrl();
            $follower->gravatar_url = $follower->getGravatarUrl();
            $follower->default_avatar_url = $follower->getDefaultAvatarUrl();
            $follower->has_custom_avatar = $follower->hasCustomAvatar();
            return $follower;
        });

        return Inertia::render('User/Followers', [
            'user' => $user->only(['id', 'name', 'username', 'display_title', 'custom_url']),
            'followers' => $followers
        ]);
    }

    public function following(Request $request, $username)
    {
        $user = User::findByUrlSlug($username);
        
        if (!$user || (!$user->show_following && (!Auth::user() || Auth::user()->id !== $user->id))) {
            abort(404);
        }

        $following = $user->following()
                         ->select(['users.id', 'users.name', 'users.username', 'users.display_title', 'users.avatar', 'users.custom_url', 'users.email'])
                         ->paginate(20);
        
        // Add avatar URLs for each followed user
        $following->getCollection()->transform(function ($followedUser) {
            $followedUser->avatar_url = $followedUser->getAvatarUrl();
            $followedUser->gravatar_url = $followedUser->getGravatarUrl();
            $followedUser->default_avatar_url = $followedUser->getDefaultAvatarUrl();
            $followedUser->has_custom_avatar = $followedUser->hasCustomAvatar();
            return $followedUser;
        });

        return Inertia::render('User/Following', [
            'user' => $user->only(['id', 'name', 'username', 'display_title', 'custom_url']),
            'following' => $following
        ]);
    }

    private function getProfileData(User $user, ?User $currentUser)
    {
        // Basic user info
        $userData = $user->only([
            'id', 'name', 'username', 'page_title', 'display_title', 'bio', 'profile_description',
            'location', 'website', 'avatar', 'cover_image', 'social_links', 'custom_css',
            'theme_settings', 'profile_color', 'custom_url', 'created_at', 'last_active_at',
            'show_join_date', 'show_location', 'show_website', 'page_logo_option', 'page_logo_cloudflare_id'
        ]);

        // Add computed image URLs
        $userData['avatar_url'] = $user->getAvatarUrl();
        $userData['gravatar_url'] = $user->getGravatarUrl();
        $userData['default_avatar_url'] = $user->getDefaultAvatarUrl();
        $userData['cover_image_url'] = $user->getCoverImageUrl();
        $userData['page_logo_url'] = $user->getPageLogoUrl();
        $userData['has_custom_avatar'] = $user->hasCustomAvatar();
        $userData['has_custom_cover'] = $user->hasCustomCoverImage();
        $userData['has_custom_page_logo'] = $user->hasCustomPageLogo();

        // Profile stats
        $userData['stats'] = $user->getProfileStats();

        // Check permissions
        $userData['permissions'] = [
            'can_edit' => $currentUser && $currentUser->id === $user->id,
            'can_follow' => $currentUser && $currentUser->id !== $user->id,
            'is_following' => $currentUser ? $currentUser->isFollowing($user) : false,
            'show_activity' => $user->show_activity || ($currentUser && $currentUser->id === $user->id),
            'show_followers' => $user->show_followers || ($currentUser && $currentUser->id === $user->id),
            'show_following' => $user->show_following || ($currentUser && $currentUser->id === $user->id),
        ];

        // Pinned lists
        $userData['pinned_lists'] = $user->pinnedLists()
                                       ->with(['user:id,name,username,custom_url'])
                                       ->get();

        // Recent lists (non-pinned)
        $userData['recent_lists'] = $user->getRecentLists(6);

        // Featured lists
        $userData['featured_lists'] = $user->getFeaturedLists();

        // Following data (if visible)
        if ($userData['permissions']['show_following']) {
            $followingUsers = $user->following()
                                  ->select(['users.id', 'users.name', 'users.username', 'users.display_title', 'users.avatar', 'users.custom_url', 'users.email'])
                                  ->limit(6)
                                  ->get();
            
            // Add avatar URLs for each followed user
            $userData['following_users'] = $followingUsers->map(function ($followedUser) {
                return [
                    'id' => $followedUser->id,
                    'name' => $followedUser->name,
                    'username' => $followedUser->username,
                    'display_title' => $followedUser->display_title,
                    'custom_url' => $followedUser->custom_url,
                    'avatar_url' => $followedUser->getAvatarUrl(),
                    'gravatar_url' => $followedUser->getGravatarUrl(),
                    'default_avatar_url' => $followedUser->getDefaultAvatarUrl(),
                    'has_custom_avatar' => $followedUser->hasCustomAvatar(),
                ];
            });

            $userData['followed_entries'] = $user->followedEntries()
                                               ->with(['category:id,name,slug', 'region:id,name'])
                                               ->select(['directory_entries.id', 'directory_entries.title', 'directory_entries.slug', 'directory_entries.description', 'directory_entries.logo_url', 'directory_entries.category_id', 'directory_entries.region_id'])
                                               ->limit(6)
                                               ->get();
        }

        // Activity feed (if visible)
        if ($userData['permissions']['show_activity']) {
            $userData['recent_activities'] = $user->publicActivities()
                                                ->with(['subject'])
                                                ->limit(10)
                                                ->get()
                                                ->map(function ($activity) {
                                                    return [
                                                        'id' => $activity->id,
                                                        'type' => $activity->activity_type,
                                                        'description' => $activity->getActivityDescription(),
                                                        'subject' => $activity->subject,
                                                        'created_at' => $activity->created_at,
                                                        'metadata' => $activity->metadata,
                                                    ];
                                                });
        }

        return [
            'profile_user' => $userData,
            'current_user' => $currentUser ? $currentUser->only(['id', 'name', 'username']) : null,
        ];
    }
}