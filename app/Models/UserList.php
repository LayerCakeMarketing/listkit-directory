<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Place;
use App\Traits\HasTags;
use App\Traits\Saveable;
use App\Traits\Likeable;
use App\Traits\Commentable;
use App\Traits\Repostable;

class UserList extends Model
{
    use HasFactory, HasTags, Saveable, Likeable, Commentable, Repostable;

    protected $table = 'lists';

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    protected $fillable = [
        'user_id',
        'channel_id',
        'owner_id',
        'owner_type',
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
        'settings',
        'status',
        'status_reason',
        'status_changed_at',
        'status_changed_by',
        'type',
        'region_id',
        'place_ids',
        'is_region_specific',
        'is_category_specific',
        'order_index',
        'structure_version'
    ];

    protected $casts = [
        'is_draft' => 'boolean',
        'published_at' => 'datetime',
        'scheduled_for' => 'datetime',
        'view_count' => 'integer',
        'is_featured' => 'boolean',
        'settings' => 'array',
        'gallery_images' => 'array',
        'status_changed_at' => 'datetime',
        'place_ids' => 'array',
        'is_region_specific' => 'boolean',
        'is_category_specific' => 'boolean',
        'order_index' => 'integer',
    ];
    
    protected $appends = ['featured_image_url', 'gallery_images_with_urls', 'channel_data'];

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
    // Polymorphic owner relationship
    public function owner()
    {
        return $this->morphTo();
    }
    
    // Legacy relationships for backward compatibility
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }
    
    // Scopes
    public function scopeForChannel($query, $channelId)
    {
        return $query->where('owner_id', $channelId)
                     ->where('owner_type', Channel::class);
    }
    
    public function scopeForUser($query, $userId)
    {
        return $query->where('owner_id', $userId)
                     ->where('owner_type', User::class);
    }

    public function items()
    {
        return $this->hasMany(ListItem::class, 'list_id')->orderBy('order_index');
    }

    public function places()
    {
        return $this->belongsToMany(Place::class, 'list_items', 'list_id', 'directory_entry_id')
                    ->withPivot('order_index', 'affiliate_url', 'notes')
                    ->orderBy('pivot_order_index')
                    ->withTimestamps();
    }
    
    // Backward compatibility
    public function directoryEntries()
    {
        return $this->places();
    }

    // Comments relationship is handled by Commentable trait
    // which uses polymorphic commentable_type/commentable_id columns

    public function category()
    {
        return $this->belongsTo(ListCategory::class, 'category_id');
    }
    
    public function region()
    {
        return $this->belongsTo(Region::class);
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

    public function statusChangedBy()
    {
        return $this->belongsTo(User::class, 'status_changed_by');
    }

    // public function media()
    // {
    //     return $this->hasMany(ListMedia::class, 'list_id')->orderBy('order_index');
    // }

    // Chains this list belongs to
    public function chains()
    {
        return $this->belongsToMany(ListChain::class, 'list_chain_items', 'list_id', 'list_chain_id')
                    ->withPivot(['order_index', 'label', 'description'])
                    ->withTimestamps()
                    ->orderBy('list_chain_items.order_index');
    }

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
                     ->published()
                     ->notOnHold(); // Exclude lists with on_hold status
    }

    // Status scopes (gracefully handle missing status column)
    public function scopeActive($query)
    {
        // Check if status column exists before using it
        if (\Schema::hasColumn('lists', 'status')) {
            return $query->where(function($q) {
                $q->whereNull('status')
                  ->orWhere('status', 'active');
            });
        }
        // If status column doesn't exist, consider all records as active
        return $query;
    }

    public function scopeOnHold($query)
    {
        // Check if status column exists before using it
        if (\Schema::hasColumn('lists', 'status')) {
            return $query->where('status', 'on_hold');
        }
        // If status column doesn't exist, return empty result
        return $query->where('id', '<', 0); // Ensures no results
    }

    public function scopeNotOnHold($query)
    {
        // Check if status column exists before using it
        if (\Schema::hasColumn('lists', 'status')) {
            return $query->where(function($q) {
                $q->whereNull('status')
                  ->orWhere('status', '!=', 'on_hold');
            });
        }
        // If status column doesn't exist, consider all records as not on hold
        return $query;
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
        
        // Check polymorphic ownership
        if ($this->owner_type === User::class) {
            return $this->owner_id === $user->id;
        } elseif ($this->owner_type === Channel::class && $this->owner) {
            return $this->owner->user_id === $user->id;
        }
        
        // Fallback to legacy ownership check
        if ($this->user_id === $user->id) return true;
        
        // Legacy channel ownership
        if ($this->channel_id && $this->channel) {
            return $this->channel->user_id === $user->id;
        }
        
        return false;
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
        
        // Lists on hold cannot be viewed except by owner and admins
        if ($this->isOnHold()) {
            if (!$user) return false;
            return $this->isOwnedBy($user) || in_array($user->role, ['admin', 'manager']);
        }
        
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

    public function getChannelDataAttribute()
    {
        if ($this->owner_type === Channel::class && $this->relationLoaded('owner')) {
            return $this->owner;
        }
        return null;
    }

    // URL generation
    public function getPublicUrl()
    {
        if ($this->owner_type === Channel::class && $this->owner) {
            return '/@' . $this->owner->slug . '/' . $this->slug;
        }
        return '/u/' . $this->user->getUrlSlug() . '/' . $this->slug;
    }

    public function getUrlAttribute()
    {
        return $this->getPublicUrl();
    }

    // Status helpers
    public function isActive()
    {
        // Check if status column exists and has a value
        if (\Schema::hasColumn('lists', 'status') && isset($this->status)) {
            return !$this->status || $this->status === 'active';
        }
        // If no status column, consider all records as active
        return true;
    }

    public function isOnHold()
    {
        // Check if status column exists and has a value
        if (\Schema::hasColumn('lists', 'status') && isset($this->status)) {
            return $this->status === 'on_hold';
        }
        // If no status column, consider no records as on hold
        return false;
    }

    public function putOnHold($reason, $adminId = null)
    {
        // Only update status if the column exists
        $updateData = [];
        if (\Schema::hasColumn('lists', 'status')) {
            $updateData['status'] = 'on_hold';
        }
        if (\Schema::hasColumn('lists', 'status_reason')) {
            $updateData['status_reason'] = $reason;
        }
        if (\Schema::hasColumn('lists', 'status_changed_at')) {
            $updateData['status_changed_at'] = now();
        }
        if (\Schema::hasColumn('lists', 'status_changed_by')) {
            $updateData['status_changed_by'] = $adminId ?? auth()->id();
        }
        
        if (!empty($updateData)) {
            $this->update($updateData);
        }
    }

    public function reactivate($adminId = null)
    {
        // Only update status if the column exists
        $updateData = [];
        if (\Schema::hasColumn('lists', 'status')) {
            $updateData['status'] = 'active';
        }
        if (\Schema::hasColumn('lists', 'status_reason')) {
            $updateData['status_reason'] = null;
        }
        if (\Schema::hasColumn('lists', 'status_changed_at')) {
            $updateData['status_changed_at'] = now();
        }
        if (\Schema::hasColumn('lists', 'status_changed_by')) {
            $updateData['status_changed_by'] = $adminId ?? auth()->id();
        }
        
        if (!empty($updateData)) {
            $this->update($updateData);
        }
    }

    // Section support methods
    public function convertToSectionFormat()
    {
        if ($this->structure_version === '1.0' || !$this->structure_version) {
            $oldItems = $this->items ?? [];
            
            // Convert items to section format
            $sections = [
                [
                    'id' => 'default-section',
                    'type' => 'section',
                    'heading' => 'Items',
                    'items' => []
                ]
            ];
            
            // Move existing items into the default section
            foreach ($oldItems as $item) {
                $sections[0]['items'][] = $item->toArray();
            }
            
            // Update the list with section structure
            $this->structure_version = '2.0';
            $this->save();
            
            return $sections;
        }
        
        return null;
    }

    public function getSectionsAttribute()
    {
        if ($this->structure_version === '2.0') {
            return $this->items()
                ->where('is_section', true)
                ->orderBy('order_index')
                ->get()
                ->map(function ($section) {
                    // Ensure heading is set from title field
                    $section->heading = $section->title;
                    return $section;
                });
        }
        
        // For backward compatibility, wrap all items in a default section
        return collect([
            (object) [
                'id' => 'default-section',
                'type' => 'section',
                'heading' => 'Items',
                'items' => $this->items
            ]
        ]);
    }

    public function addItemToSection($sectionId, $itemData)
    {
        if ($this->structure_version !== '2.0') {
            $this->convertToSectionFormat();
        }
        
        // Implementation will be in the controller
        return true;
    }
}