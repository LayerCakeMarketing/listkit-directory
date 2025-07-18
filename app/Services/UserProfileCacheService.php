<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserList;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class UserProfileCacheService
{
    /**
     * Cache duration in seconds (10 minutes)
     */
    private const CACHE_DURATION = 600;

    /**
     * Get cached profile data or fetch and cache it
     */
    public function getProfile($customUrl)
    {
        $cacheKey = $this->getProfileCacheKey($customUrl);
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($customUrl) {
            return $this->fetchProfileData($customUrl);
        });
    }

    /**
     * Fetch fresh profile data
     */
    private function fetchProfileData($customUrl)
    {
        $user = User::where('custom_url', $customUrl)
            ->orWhere('username', $customUrl)
            ->withCount(['followers', 'followingOld as following_count'])
            ->firstOrFail();
            
        // Manually calculate lists count due to complex relationship
        $user->lists_count = UserList::where(function($query) use ($user) {
                $query->where(function($q) use ($user) {
                    $q->where('owner_type', User::class)
                      ->where('owner_id', $user->id);
                })
                ->orWhere(function($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->whereNull('owner_type');
                });
            })
            ->where('visibility', 'public')
            ->count();

        // Get pinned lists
        $pinnedLists = $user->lists()
            ->where('visibility', 'public')
            ->where('is_pinned', true)
            ->with(['category', 'items' => function ($query) {
                $query->limit(5); // Limit items per list for performance
            }])
            ->withCount('items')
            ->orderBy('pinned_at', 'desc')
            ->get();

        // Get recent lists
        $recentLists = $user->lists()
            ->where('visibility', 'public')
            ->where('is_pinned', false)
            ->with(['category', 'items' => function ($query) {
                $query->limit(5); // Limit items per list for performance
            }])
            ->withCount('items')
            ->latest()
            ->limit(6)
            ->get();

        // Add is_following status if user is authenticated
        $isFollowing = false;
        if (Auth::check()) {
            $isFollowing = Auth::user()->isFollowing($user);
        }

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'custom_url' => $user->custom_url,
                'bio' => $user->bio,
                'avatar_url' => $user->avatar_url,
                'cover_image_url' => $user->cover_image_url,
                'location' => $user->location,
                'website' => $user->website,
                'page_title' => $user->page_title,
                'display_title' => $user->display_title,
                'profile_description' => $user->profile_description,
                'profile_color' => $user->profile_color,
                'show_activity' => $user->show_activity,
                'show_followers' => $user->show_followers,
                'show_following' => $user->show_following,
                'show_join_date' => $user->show_join_date,
                'show_location' => $user->show_location,
                'show_website' => $user->show_website,
                'page_logo_option' => $user->page_logo_option,
                'page_logo_url' => $user->page_logo_url,
                'followers_count' => $user->followers_count,
                'following_count' => $user->following_count,
                'lists_count' => $user->lists_count,
                'created_at' => $user->created_at,
                'is_following' => $isFollowing,
            ],
            'pinnedLists' => $pinnedLists,
            'recentLists' => $recentLists,
        ];
    }

    /**
     * Clear cache for a specific user profile
     */
    public function clearProfileCache($user)
    {
        // Clear by custom URL
        if ($user->custom_url) {
            Cache::forget($this->getProfileCacheKey($user->custom_url));
        }
        
        // Clear by username
        Cache::forget($this->getProfileCacheKey($user->username));
        
        // Clear user's lists cache
        Cache::tags(['user_lists_' . $user->id])->flush();
    }

    /**
     * Clear all profile caches (use sparingly)
     */
    public function clearAllProfileCaches()
    {
        Cache::tags(['user_profiles'])->flush();
    }

    /**
     * Get cache key for a profile
     */
    private function getProfileCacheKey($customUrl)
    {
        return 'user_profile_' . md5($customUrl);
    }

    /**
     * Warm up cache for a user (useful after profile updates)
     */
    public function warmCache($user)
    {
        if ($user->custom_url) {
            $this->getProfile($user->custom_url);
        }
        $this->getProfile($user->username);
    }
}