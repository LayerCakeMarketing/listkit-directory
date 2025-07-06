<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UserList extends Model
{
    use HasFactory;

    protected $table = 'lists';

    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'slug',
        'description',
        'featured_image',
        'featured_image_cloudflare_id',
        'gallery_images',
        'visibility',
        'is_draft',
        'published_at',
        'scheduled_for',
        'view_count',
        'is_featured',
        'settings'
    ];

    protected $casts = [
        'is_draft' => 'boolean',
        'published_at' => 'datetime',
        'scheduled_for' => 'datetime',
        'view_count' => 'integer',
        'is_featured' => 'boolean',
        'settings' => 'array',
        'gallery_images' => 'array',
    ];
    
    protected $appends = ['featured_image_url', 'gallery_images_with_urls'];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($list) {
            if (empty($list->slug)) {
                $list->slug = Str::slug($list->name);
                
                // Handle duplicate slugs
                $count = static::where('slug', 'like', $list->slug . '%')
                              ->where('user_id', $list->user_id)
                              ->count();
                if ($count > 0) {
                    $list->slug = $list->slug . '-' . ($count + 1);
                }
            }
        });
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(ListItem::class, 'list_id')->orderBy('order_index');
    }

    public function directoryEntries()
    {
        return $this->belongsToMany(DirectoryEntry::class, 'list_items')
                    ->withPivot('order_index', 'affiliate_url', 'notes')
                    ->orderBy('pivot_order_index')
                    ->withTimestamps();
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'list_id');
    }

    public function category()
    {
        return $this->belongsTo(ListCategory::class, 'category_id');
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function shares()
    {
        return $this->hasMany(UserListShare::class, 'list_id');
    }

    public function sharedWith()
    {
        return $this->belongsToMany(User::class, 'user_list_shares', 'list_id', 'user_id')
                    ->withPivot('permission', 'expires_at', 'shared_by')
                    ->withTimestamps();
    }

    // public function media()
    // {
    //     return $this->hasMany(ListMedia::class, 'list_id')->orderBy('order_index');
    // }

    // Visibility scopes
    public function scopePublic($query)
    {
        return $query->where('visibility', 'public');
    }

    public function scopeUnlisted($query)
    {
        return $query->where('visibility', 'unlisted');
    }

    public function scopePrivate($query)
    {
        return $query->where('visibility', 'private');
    }

    public function scopeViewableByGuests($query)
    {
        return $query->whereIn('visibility', ['public', 'unlisted']);
    }

    public function scopeSearchable($query)
    {
        return $query->where('visibility', 'public')
                     ->published();
    }

    // Publishing scopes
    public function scopePublished($query)
    {
        return $query->where('is_draft', false)
                     ->where(function ($q) {
                         $q->whereNull('published_at')
                           ->orWhere('published_at', '<=', now());
                     });
    }

    public function scopeDraft($query)
    {
        return $query->where('is_draft', true);
    }

    public function scopeScheduled($query)
    {
        return $query->where('is_draft', false)
                     ->where('published_at', '>', now());
    }

    // Visibility check for specific user
    public function scopeVisibleTo($query, $user)
    {
        return $query->where(function ($q) use ($user) {
            // Public lists that are published
            $q->where(function ($sub) {
                $sub->where('visibility', 'public')
                    ->published();
            });
            
            // User's own lists (any visibility)
            if ($user) {
                $q->orWhere('user_id', $user->id);
                
                // Lists shared with the user
                $q->orWhereHas('shares', function ($share) use ($user) {
                    $share->where('user_id', $user->id)
                          ->active();
                });
            }
        });
    }

    // Helper methods
    public function isPublic()
    {
        return $this->visibility === 'public';
    }

    public function isUnlisted()
    {
        return $this->visibility === 'unlisted';
    }

    public function isPrivate()
    {
        return $this->visibility === 'private';
    }

    public function isViewableByGuests()
    {
        return in_array($this->visibility, ['public', 'unlisted']);
    }

    // Additional scopes

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Helper methods
    public function isOwnedBy($user)
    {
        if (!$user) return false;
        return $this->user_id === $user->id;
    }

    public function canEdit($user = null)
    {
        $user = $user ?? auth()->user();
        if (!$user) return false;
        
        // Owner can always edit
        if ($this->isOwnedBy($user)) return true;
        
        // Admin and manager can edit any list
        if (in_array($user->role, ['admin', 'manager'])) return true;
        
        // Check if user has edit permission through sharing
        $share = $this->shares()
                     ->where('user_id', $user->id)
                     ->active()
                     ->first();
        
        return $share && $share->canEdit();
    }

    public function canView($user = null)
    {
        $user = $user ?? auth()->user();
        
        // Public lists can be viewed by anyone (if published)
        if ($this->isPublic() && $this->isPublished()) {
            return true;
        }
        
        // Unlisted lists can be viewed by anyone with the link (if published)
        if ($this->isUnlisted() && $this->isPublished()) {
            return true;
        }
        
        // Must have a user for private lists
        if (!$user) return false;
        
        // Owner can always view
        if ($this->isOwnedBy($user)) return true;
        
        // Admin and manager can view any list
        if (in_array($user->role, ['admin', 'manager'])) return true;
        
        // Check if user has view permission through sharing
        return $this->shares()
                    ->where('user_id', $user->id)
                    ->active()
                    ->exists();
    }

    public function isPublished()
    {
        return !$this->is_draft && 
               (!$this->published_at || $this->published_at->isPast());
    }

    public function isDraft()
    {
        return $this->is_draft;
    }

    public function isScheduled()
    {
        return !$this->is_draft && 
               $this->published_at && 
               $this->published_at->isFuture();
    }

    public function shareWith(User $user, $permission = 'view', $expiresAt = null)
    {
        return $this->shares()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'shared_by' => auth()->id(),
                'permission' => $permission,
                'expires_at' => $expiresAt,
            ]
        );
    }

    public function unshareWith(User $user)
    {
        return $this->shares()
                    ->where('user_id', $user->id)
                    ->delete();
    }

    public function isSharedWith(User $user)
    {
        return $this->shares()
                    ->where('user_id', $user->id)
                    ->active()
                    ->exists();
    }

    public function incrementViewCount()
    {
        $this->increment('view_count');
    }

    // Tag helper methods
    public function attachTags($tags)
    {
        $tagIds = [];
        
        foreach ($tags as $tag) {
            if (is_string($tag)) {
                $tagModel = Tag::findOrCreateByName($tag);
                $tagIds[] = $tagModel->id;
            } elseif (is_array($tag) && isset($tag['name'])) {
                $tagModel = Tag::findOrCreateByName($tag['name']);
                $tagIds[] = $tagModel->id;
            } elseif (is_numeric($tag)) {
                $tagIds[] = $tag;
            }
        }
        
        $this->tags()->sync($tagIds);
    }

    public function syncTags($tags)
    {
        $this->attachTags($tags);
    }

    // Accessors
    public function getFeaturedImageUrlAttribute()
    {
        if ($this->featured_image_cloudflare_id) {
            $imageService = app(\App\Services\CloudflareImageService::class);
            return $imageService->getImageUrl($this->featured_image_cloudflare_id);
        }
        return $this->featured_image; // Fallback to URL field
    }

    public function getGalleryImagesWithUrlsAttribute()
    {
        if (!$this->gallery_images) {
            return [];
        }

        $imageService = app(\App\Services\CloudflareImageService::class);
        
        return collect($this->gallery_images)->map(function ($image) use ($imageService) {
            if (isset($image['id'])) {
                // Add the gallery-16x9 variant URL
                $image['gallery_url'] = $imageService->getGalleryImageUrl($image['id']);
                // Keep the standard public URL as fallback
                $image['url'] = $imageService->getImageUrl($image['id']);
            }
            return $image;
        })->toArray();
    }
}