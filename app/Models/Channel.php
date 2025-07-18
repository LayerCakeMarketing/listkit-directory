<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Channel extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'slug',
        'name',
        'description',
        'avatar_image',
        'avatar_cloudflare_id',
        'banner_image',
        'banner_cloudflare_id',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    protected $appends = ['followers_count', 'lists_count', 'is_following', 'avatar_url', 'banner_url'];

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Get the user that owns the channel.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the lists for the channel.
     */
    public function lists()
    {
        return $this->morphMany(UserList::class, 'owner');
    }
    
    // Legacy relationship for backward compatibility
    public function legacyLists(): HasMany
    {
        return $this->hasMany(UserList::class);
    }

    /**
     * The users that follow this channel.
     */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'channel_followers')
            ->withTimestamps();
    }

    /**
     * Get the followers count attribute.
     */
    public function getFollowersCountAttribute(): int
    {
        if (!$this->exists) {
            return 0;
        }
        return $this->followers()->count();
    }

    /**
     * Get the lists count attribute.
     */
    public function getListsCountAttribute(): int
    {
        if (!$this->exists) {
            return 0;
        }
        return $this->lists()->count();
    }

    /**
     * Check if the authenticated user is following this channel.
     */
    public function getIsFollowingAttribute(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        return $this->followers()->where('user_id', auth()->id())->exists();
    }

    /**
     * Generate a unique slug from the channel name.
     */
    public static function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $count = 1;

        // Check against both channels and users
        while (
            static::where('slug', $slug)->exists() ||
            User::where('username', $slug)->exists() ||
            User::where('custom_url', $slug)->exists()
        ) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        return $slug;
    }

    /**
     * Get the URL for the channel's avatar image.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        // If we have a Cloudflare ID, use it
        if ($this->avatar_cloudflare_id) {
            return "https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/{$this->avatar_cloudflare_id}/public";
        }

        if (!$this->avatar_image) {
            return null;
        }

        // If it's a full URL, return as is
        if (filter_var($this->avatar_image, FILTER_VALIDATE_URL)) {
            return $this->avatar_image;
        }

        // Otherwise, assume it's a storage path
        return asset('storage/' . $this->avatar_image);
    }

    /**
     * Get the URL for the channel's banner image.
     */
    public function getBannerUrlAttribute(): ?string
    {
        // If we have a Cloudflare ID, use it
        if ($this->banner_cloudflare_id) {
            return "https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/{$this->banner_cloudflare_id}/public";
        }

        if (!$this->banner_image) {
            return null;
        }

        // If it's a full URL, return as is
        if (filter_var($this->banner_image, FILTER_VALIDATE_URL)) {
            return $this->banner_image;
        }

        // Otherwise, assume it's a storage path
        return asset('storage/' . $this->banner_image);
    }

    /**
     * Scope a query to only include public channels.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Check if a user can view this channel.
     */
    public function canBeViewedBy(?User $user): bool
    {
        if ($this->is_public) {
            return true;
        }

        if (!$user) {
            return false;
        }

        // Owner can always view
        if ($user->id === $this->user_id) {
            return true;
        }

        // Followers can view private channels
        return $this->followers()->where('user_id', $user->id)->exists();
    }
}