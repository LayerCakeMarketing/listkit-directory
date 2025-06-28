<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DirectoryEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'description', 'type', 'category_id', 'region_id',
        'tags', 'owner_user_id', 'created_by_user_id', 'updated_by_user_id',
        'phone', 'email', 'website_url', 'social_links', 'featured_image',
        'gallery_images', 'status', 'is_featured', 'is_verified', 'is_claimed',
        'meta_title', 'meta_description', 'structured_data', 'published_at',
        'view_count', 'list_count'
    ];

    protected $casts = [
        'tags' => 'array',
        'social_links' => 'array',
        'gallery_images' => 'array',
        'structured_data' => 'array',
        'is_featured' => 'boolean',
        'is_verified' => 'boolean',
        'is_claimed' => 'boolean',
        'published_at' => 'datetime',
        'view_count' => 'integer',
        'list_count' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($entry) {
            if (empty($entry->slug)) {
                $entry->slug = Str::slug($entry->title);
                
                // Handle duplicate slugs
                $count = static::where('slug', 'like', $entry->slug . '%')->count();
                if ($count > 0) {
                    $entry->slug = $entry->slug . '-' . ($count + 1);
                }
            }
            
            // Set created_by if not set
            if (!$entry->created_by_user_id && auth()->check()) {
                $entry->created_by_user_id = auth()->id();
            }
        });
        
        static::updating(function ($entry) {
            // Track who made the update
            if (auth()->check()) {
                $entry->updated_by_user_id = auth()->id();
            }
        });
    }

    // Relationships
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function location()
    {
        return $this->hasOne(Location::class);
    }

    public function lists()
    {
        return $this->belongsToMany(UserList::class, 'list_items')
            ->withPivot('order_index', 'affiliate_url', 'notes')
            ->withTimestamps();
    }

    public function claims()
    {
        return $this->hasMany(Claim::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeWithLocation($query)
    {
        return $query->whereHas('location');
    }

    public function scopeOnlineOnly($query)
    {
        return $query->whereIn('type', ['online_business', 'resource'])
                    ->doesntHave('location');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    // Helper methods
    public function hasPhysicalLocation()
    {
        return in_array($this->type, ['physical_location', 'event']) && $this->location;
    }

    public function isOnlineOnly()
    {
        return in_array($this->type, ['online_business', 'resource']);
    }

    public function canBeEdited()
    {
        $user = auth()->user();
        if (!$user) return false;
        
        // Admin and Manager can edit anything
        if (in_array($user->role, ['admin', 'manager'])) return true;
        
        // Editor can edit published entries
        if ($user->role === 'editor' && $this->status === 'published') return true;
        
        // Business owner can edit their own entries
        if ($user->role === 'business_owner' && $this->owner_user_id === $user->id) return true;
        
        // Original creator can edit draft entries
        if ($this->created_by_user_id === $user->id && $this->status === 'draft') return true;
        
        return false;
    }

    public function publish()
    {
        $this->status = 'published';
        $this->published_at = now();
        $this->save();
    }

    public function incrementViewCount()
    {
        $this->increment('view_count');
    }

    public function incrementListCount()
    {
        $this->increment('list_count');
    }

    // Get the URL for this entry
    public function getUrl()
    {
        if (!$this->category) {
            $this->load('category');
        }
        
        // If it's a subcategory, get parent category
        if ($this->category && $this->category->parent_id) {
            $this->category->load('parent');
            $parentSlug = $this->category->parent->slug;
            $childSlug = $this->category->slug;
            return "/{$parentSlug}/{$childSlug}/{$this->slug}";
        }
        
        // If it's a root category, use directory route as fallback
        return "/directory/entry/{$this->slug}";
    }
}