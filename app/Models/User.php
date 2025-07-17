<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Place;
use App\Traits\Followable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, Followable;

    protected $fillable = [
        'name', 'email', 'password', 'username', 'custom_url', 'role', 'bio',
        'avatar', 'cover_image', 'social_links', 'preferences', 'phone',
        'permissions', 'is_public', 'display_title', 'profile_description',
        'location', 'website', 'birth_date', 'profile_settings',
        'show_activity', 'show_followers', 'show_following', 'page_title',
        'profile_color', 'custom_css', 'show_join_date', 'show_location', 'show_website',
        'avatar_cloudflare_id', 'cover_cloudflare_id', 'page_logo_cloudflare_id', 'page_logo_option',
        'avatar_updated_at', 'cover_updated_at'
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
        'avatar_updated_at' => 'datetime',
        'cover_updated_at' => 'datetime',
    ];
    
    protected $appends = ['avatar_url', 'gravatar_url', 'default_avatar_url', 'has_custom_avatar'];


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
    
    // Accessors
    public function getAvatarUrlAttribute()
    {
        if ($this->avatar_cloudflare_id) {
            $deliveryUrl = config('services.cloudflare.delivery_url', 'https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A');
            return rtrim($deliveryUrl, '/') . '/' . $this->avatar_cloudflare_id . '/public';
        }
        return $this->avatar;
    }

    // Relationships
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function tackedPost()
    {
        return $this->hasOne(Post::class)->where('is_tacked', true);
    }

    public function createdPlaces()
    {
        return $this->hasMany(Place::class, 'created_by_user_id');
    }
    
    // Backward compatibility
    public function createdEntries()
    {
        return $this->createdPlaces();
    }

    public function ownedPlaces()
    {
        return $this->hasMany(Place::class, 'owner_user_id');
    }
    
    // Backward compatibility
    public function ownedEntries()
    {
        return $this->ownedPlaces();
    }

    public function lists()
    {
        return $this->hasMany(UserList::class);
    }

    public function channels()
    {
        return $this->hasMany(Channel::class);
    }

    public function followedChannels()
    {
        return $this->belongsToMany(Channel::class, 'channel_followers')
                    ->withTimestamps();
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function claims()
    {
        return $this->hasMany(Claim::class);
    }
    
    // Polymorphic following relationships
    public function following()
    {
        return $this->hasMany(Follow::class, 'follower_id');
    }
    
    public function followingUsers()
    {
        return $this->morphedByMany(
            User::class, 
            'followable', 
            'follows', 
            'follower_id', 
            'followable_id'
        )->wherePivot('followable_type', User::class);
    }
    
    public function followingPlaces()
    {
        return $this->morphedByMany(
            Place::class, 
            'followable', 
            'follows', 
            'follower_id', 
            'followable_id'
        )->wherePivot('followable_type', Place::class);
    }
    
    public function isFollowing($followable): bool
    {
        return $this->following()
            ->where('followable_id', $followable->id)
            ->where('followable_type', get_class($followable))
            ->exists();
    }
    
    public function followUser(User $user): void
    {
        if ($user->id !== $this->id && !$this->isFollowing($user)) {
            $this->following()->create([
                'followable_id' => $user->id,
                'followable_type' => User::class,
            ]);
        }
    }
    
    public function unfollowUser(User $user): void
    {
        $this->following()
            ->where('followable_id', $user->id)
            ->where('followable_type', User::class)
            ->delete();
    }
    
    public function followPlace(Place $place): void
    {
        if (!$this->isFollowing($place)) {
            $this->following()->create([
                'followable_id' => $place->id,
                'followable_type' => Place::class,
            ]);
        }
    }
    
    public function unfollowPlace(Place $place): void
    {
        $this->following()
            ->where('followable_id', $place->id)
            ->where('followable_type', Place::class)
            ->delete();
    }
    

    // Following relationships
    public function followers()
    {
        return $this->belongsToMany(User::class, 'user_follows', 'following_id', 'follower_id')
                    ->withTimestamps();
    }

    public function followingOld()
    {
        return $this->belongsToMany(User::class, 'user_follows', 'follower_id', 'following_id')
                    ->withTimestamps();
    }

    public function followedPlaces()
    {
        return $this->belongsToMany(Place::class, 'directory_entry_follows')
                    ->withTimestamps();
    }
    
    // Backward compatibility
    public function followedEntries()
    {
        return $this->followedPlaces();
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

    public function savedItems()
    {
        return $this->hasMany(SavedItem::class)->latest();
    }

    public function publicActivities()
    {
        return $this->activities()->where('is_public', true);
    }

    // Helper methods
    public function getProfileUrlAttribute()
    {
        return '/@' . $this->getUrlSlug();
    }

    public function getUrlSlug()
    {
        return $this->custom_url ?: $this->username ?: $this->id;
    }

    public function getPublicListsUrl()
    {
        return '/@' . $this->getUrlSlug() . '/lists';
    }

    public function getListUrl($listSlug)
    {
        return '/@' . $this->getUrlSlug() . '/' . $listSlug;
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


    public function isFollowingPlace(Place $place)
    {
        return $this->followedPlaces()->where('directory_entry_id', $place->id)->exists();
    }
    
    // Backward compatibility
    public function isFollowingEntry($entry)
    {
        return $this->isFollowingPlace($entry);
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
            'entries_count' => $this->createdPlaces()->where('status', 'published')->count(),
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
            $deliveryUrl = config('services.cloudflare.delivery_url', 'https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A');
            return rtrim($deliveryUrl, '/') . '/' . $this->avatar_cloudflare_id . '/public';
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

    // Accessor for gravatar_url attribute
    public function getGravatarUrlAttribute()
    {
        return $this->getGravatarUrl();
    }

    // Accessor for default_avatar_url attribute
    public function getDefaultAvatarUrlAttribute()
    {
        return $this->getDefaultAvatarUrl();
    }

    // Accessor for has_custom_avatar attribute
    public function getHasCustomAvatarAttribute()
    {
        return $this->hasCustomAvatar();
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
    public function getCoverImageUrlAttribute()
    {
        return $this->getCoverImageUrl();
    }

    public function getPageLogoUrlAttribute()
    {
        return $this->getPageLogoUrl();
    }
}