<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'username', 'custom_url', 'role', 'bio',
        'avatar', 'cover_image', 'social_links', 'preferences', 'phone',
        'permissions', 'is_public', 'display_title', 'profile_description',
        'location', 'website', 'birth_date', 'profile_settings',
        'show_activity', 'show_followers', 'show_following', 'page_title',
        'profile_color', 'custom_css', 'show_join_date', 'show_location', 'show_website',
        'avatar_cloudflare_id', 'cover_cloudflare_id', 'page_logo_cloudflare_id', 'page_logo_option'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'social_links' => 'array',
        'preferences' => 'array',
        'permissions' => 'array',
        'profile_settings' => 'array',
        'theme_settings' => 'array',
        'is_public' => 'boolean',
        'show_activity' => 'boolean',
        'show_followers' => 'boolean',
        'show_following' => 'boolean',
        'birth_date' => 'date',
        'last_active_at' => 'datetime',
    ];


    // Role-based permissions
    public function hasRole($role)
    {
        return $this->role === $role;
    }

    public function hasAnyRole($roles)
    {
        return in_array($this->role, $roles);
    }

    public function canManageContent()
    {
        return in_array($this->role, ['admin', 'manager', 'editor']);
    }

    public function canPublishContent()
    {
        return in_array($this->role, ['admin', 'manager']);
    }

    public function canModerateComments()
    {
        return in_array($this->role, ['admin', 'manager', 'editor']);
    }

    public function canManageUsers()
    {
        return in_array($this->role, ['admin', 'manager']);
    }

    public function canClaimBusinesses()
    {
        return in_array($this->role, ['admin', 'manager', 'business_owner', 'user']);
    }

    // Relationships
    public function createdEntries()
    {
        return $this->hasMany(DirectoryEntry::class, 'created_by_user_id');
    }

    public function ownedEntries()
    {
        return $this->hasMany(DirectoryEntry::class, 'owner_user_id');
    }

    public function lists()
    {
        return $this->hasMany(UserList::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function claims()
    {
        return $this->hasMany(Claim::class);
    }

    // Following relationships
    public function followers()
    {
        return $this->belongsToMany(User::class, 'user_follows', 'following_id', 'follower_id')
                    ->withTimestamps();
    }

    public function following()
    {
        return $this->belongsToMany(User::class, 'user_follows', 'follower_id', 'following_id')
                    ->withTimestamps();
    }

    public function followedEntries()
    {
        return $this->belongsToMany(DirectoryEntry::class, 'directory_entry_follows')
                    ->withTimestamps();
    }

    public function pinnedLists()
    {
        return $this->belongsToMany(UserList::class, 'pinned_lists', 'user_id', 'list_id')
                    ->withTimestamps()
                    ->withPivot('sort_order')
                    ->orderBy('pinned_lists.sort_order');
    }

    public function activities()
    {
        return $this->hasMany(UserActivity::class)->orderBy('created_at', 'desc');
    }

    public function publicActivities()
    {
        return $this->activities()->where('is_public', true);
    }

    // Helper methods
    public function getProfileUrlAttribute()
    {
        return route('user.profile', ['username' => $this->username]);
    }

    public function getUrlSlug()
    {
        return $this->custom_url ?: $this->username ?: $this->id;
    }

    public function getPublicListsUrl()
    {
        $slug = $this->getUrlSlug();
        return $slug ? "/{$slug}/lists" : "/user/{$this->id}/lists";
    }

    public function getListUrl($listSlug)
    {
        $userSlug = $this->getUrlSlug();
        return $userSlug ? "/{$userSlug}/{$listSlug}" : "/user/{$this->id}/{$listSlug}";
    }

    public static function findByUrlSlug($slug)
    {
        $query = static::where('custom_url', $slug)
                      ->orWhere('username', $slug);
        
        // Only check ID if the slug is numeric
        if (is_numeric($slug)) {
            $query->orWhere('id', $slug);
        }
        
        return $query->first();
    }

   // User Last Active
    public function updateLastActive()
    {
        $this->last_active_at = now();
        $this->saveQuietly(); 
    }

    // Profile enhancement methods
    public function getDisplayName()
    {
        return $this->display_title ?: $this->name;
    }

    public function incrementProfileViews()
    {
        $this->increment('profile_views');
    }

    public function isFollowedBy(User $user)
    {
        return $this->followers()->where('follower_id', $user->id)->exists();
    }

    public function isFollowing(User $user)
    {
        return $this->following()->where('following_id', $user->id)->exists();
    }

    public function follow(User $user)
    {
        if (!$this->isFollowing($user) && $this->id !== $user->id) {
            $this->following()->attach($user->id);
            $this->recordActivity('followed_user', $user);
            return true;
        }
        return false;
    }

    public function unfollow(User $user)
    {
        if ($this->isFollowing($user)) {
            $this->following()->detach($user->id);
            return true;
        }
        return false;
    }

    public function followEntry(DirectoryEntry $entry)
    {
        if (!$this->followedEntries()->where('directory_entry_id', $entry->id)->exists()) {
            $this->followedEntries()->attach($entry->id);
            $this->recordActivity('followed_entry', $entry);
            return true;
        }
        return false;
    }

    public function unfollowEntry(DirectoryEntry $entry)
    {
        return $this->followedEntries()->detach($entry->id) > 0;
    }

    public function isFollowingEntry(DirectoryEntry $entry)
    {
        return $this->followedEntries()->where('directory_entry_id', $entry->id)->exists();
    }

    public function pinList(UserList $list, $sortOrder = null)
    {
        if ($list->user_id !== $this->id) {
            return false;
        }

        $sortOrder = $sortOrder ?: ($this->pinnedLists()->max('sort_order') + 1);
        
        if (!$this->pinnedLists()->where('list_id', $list->id)->exists()) {
            $this->pinnedLists()->attach($list->id, ['sort_order' => $sortOrder]);
            return true;
        }
        return false;
    }

    public function unpinList(UserList $list)
    {
        return $this->pinnedLists()->detach($list->id) > 0;
    }

    public function recordActivity($type, $subject, $metadata = null, $isPublic = true)
    {
        return $this->activities()->create([
            'activity_type' => $type,
            'subject_type' => get_class($subject),
            'subject_id' => $subject->id,
            'metadata' => $metadata,
            'is_public' => $isPublic,
        ]);
    }

    public function getRecentLists($limit = 5)
    {
        return $this->lists()
                   ->searchable()
                   ->latest()
                   ->limit($limit)
                   ->get();
    }

    public function getFeaturedLists()
    {
        return $this->lists()
                   ->where('is_featured', true)
                   ->searchable()
                   ->latest()
                   ->get();
    }

    public function getProfileStats()
    {
        return [
            'lists_count' => $this->lists()->searchable()->count(),
            'followers_count' => $this->followers()->count(),
            'following_count' => $this->following()->count(),
            'entries_count' => $this->createdEntries()->where('status', 'published')->count(),
            'profile_views' => $this->profile_views,
        ];
    }

    // Avatar and image methods
    public function getGravatarUrl($size = 200)
    {
        $hash = md5(strtolower(trim($this->email)));
        return "https://www.gravatar.com/avatar/{$hash}?s={$size}&d=404";
    }

    public function getAvatarUrl($size = 200)
    {
        // If user has Cloudflare avatar, use it
        if ($this->avatar_cloudflare_id) {
            $imageService = app(\App\Services\CloudflareImageService::class);
            // Use 'public' variant which always works
            return $imageService->getImageUrl($this->avatar_cloudflare_id, ['variant' => 'public']);
        }
        
        // If user has uploaded avatar locally, use it
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }

        // Try Gravatar
        $gravatarUrl = $this->getGravatarUrl($size);
        
        // Check if Gravatar exists (we'll do this client-side for better performance)
        // For now, return Gravatar URL, fallback will be handled in frontend
        return $gravatarUrl;
    }

    public function getDefaultAvatarUrl()
    {
        return asset('images/listerino_profile.svg');
    }

    public function getCoverImageUrl()
    {
        // If user has Cloudflare cover image, use it
        if ($this->cover_cloudflare_id) {
            $imageService = app(\App\Services\CloudflareImageService::class);
            // Use 'public' variant which always works
            return $imageService->getImageUrl($this->cover_cloudflare_id, ['variant' => 'public']);
        }
        
        // If user has uploaded cover image locally, use it
        if ($this->cover_image) {
            return asset('storage/' . $this->cover_image);
        }
        return null;
    }

    public function getPageLogoUrl()
    {
        $option = $this->page_logo_option ?? 'initials';
        
        switch ($option) {
            case 'custom':
                if ($this->page_logo_cloudflare_id) {
                    $imageService = app(\App\Services\CloudflareImageService::class);
                    return $imageService->getImageUrl($this->page_logo_cloudflare_id, ['variant' => 'public']);
                }
                break;
            case 'profile':
                return $this->getAvatarUrl();
            case 'initials':
            default:
                // Return null for initials - will be generated client-side
                return null;
        }
        
        // Fallback to initials (null) if no custom logo found
        return null;
    }

    public function hasCustomAvatar()
    {
        return !empty($this->avatar) || !empty($this->avatar_cloudflare_id);
    }

    public function hasCustomCoverImage()
    {
        return !empty($this->cover_image) || !empty($this->cover_cloudflare_id);
    }

    public function hasCustomPageLogo()
    {
        return !empty($this->page_logo_cloudflare_id);
    }

    // Attribute accessors for API responses
    public function getAvatarUrlAttribute()
    {
        return $this->getAvatarUrl();
    }

    public function getCoverImageUrlAttribute()
    {
        return $this->getCoverImageUrl();
    }

    public function getPageLogoUrlAttribute()
    {
        return $this->getPageLogoUrl();
    }
}