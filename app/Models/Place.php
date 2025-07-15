<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Place extends Model
{
    use HasFactory;
    
    protected $table = 'directory_entries';

    protected $fillable = [
        'title', 'slug', 'description', 'type', 'category_id', 'region_id',
        'tags', 'owner_user_id', 'created_by_user_id', 'updated_by_user_id',
        'phone', 'email', 'website_url', 'social_links', 'featured_image',
        'logo_url', 'cover_image_url', 'gallery_images', 'status', 'is_featured', 
        'is_verified', 'is_claimed', 'meta_title', 'meta_description', 'structured_data', 
        'published_at', 'view_count', 'list_count',
        
        // Social Media
        'facebook_url', 'instagram_handle', 'twitter_handle', 'youtube_channel', 'messenger_contact',
        
        // Business Metadata
        'price_range', 'takes_reservations', 'accepts_credit_cards', 'wifi_available', 
        'pet_friendly', 'parking_options', 'wheelchair_accessible', 'outdoor_seating', 'kid_friendly',
        
        // Media
        'video_urls', 'pdf_files',
        
        // Operational Info
        'hours_of_operation', 'special_hours', 'temporarily_closed', 'open_24_7',
        
        // Location metadata
        'cross_streets', 'neighborhood',
        
        // Hierarchical regions
        'state_region_id', 'city_region_id', 'neighborhood_region_id', 'regions_updated_at'
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
        
        // New JSON fields
        'video_urls' => 'array',
        'pdf_files' => 'array',
        'hours_of_operation' => 'array',
        'special_hours' => 'array',
        
        // Boolean fields
        'takes_reservations' => 'boolean',
        'accepts_credit_cards' => 'boolean',
        'wifi_available' => 'boolean',
        'pet_friendly' => 'boolean',
        'wheelchair_accessible' => 'boolean',
        'outdoor_seating' => 'boolean',
        'kid_friendly' => 'boolean',
        'temporarily_closed' => 'boolean',
        'open_24_7' => 'boolean',
        'regions_updated_at' => 'datetime',
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
            \Log::info('Model updating event triggered', [
                'entry_id' => $entry->id,
                'auth_check' => auth()->check(),
                'auth_id' => auth()->id()
            ]);
            
            // Track who made the update - only if user is authenticated
            if (auth()->check() && auth()->id()) {
                $entry->updated_by_user_id = auth()->id();
                \Log::info('Set updated_by_user_id to:', ['user_id' => auth()->id()]);
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

    // Hierarchical region relationships
    public function stateRegion()
    {
        return $this->belongsTo(Region::class, 'state_region_id');
    }

    public function cityRegion()
    {
        return $this->belongsTo(Region::class, 'city_region_id');
    }

    public function neighborhoodRegion()
    {
        return $this->belongsTo(Region::class, 'neighborhood_region_id');
    }

    // Many-to-many relationship with regions (for featured places)
    public function regions()
    {
        return $this->belongsToMany(Region::class, 'place_regions')
            ->withPivot('is_featured', 'featured_order', 'featured_at')
            ->withTimestamps();
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
        return $this->hasOne(Location::class, 'directory_entry_id');
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

    // Region-based scopes
    public function scopeInRegion($query, $regionId)
    {
        return $query->where(function ($q) use ($regionId) {
            $q->where('region_id', $regionId)
              ->orWhere('state_region_id', $regionId)
              ->orWhere('city_region_id', $regionId)
              ->orWhere('neighborhood_region_id', $regionId);
        });
    }

    public function scopeInState($query, $stateRegionId)
    {
        return $query->where('state_region_id', $stateRegionId);
    }

    public function scopeInCity($query, $cityRegionId)
    {
        return $query->where('city_region_id', $cityRegionId);
    }

    public function scopeInNeighborhood($query, $neighborhoodRegionId)
    {
        return $query->where('neighborhood_region_id', $neighborhoodRegionId);
    }

    public function scopeWithRegions($query)
    {
        return $query->with(['stateRegion', 'cityRegion', 'neighborhoodRegion']);
    }

    public function scopeInRegionHierarchy($query, Region $region)
    {
        // Get all entries within a region and its descendants
        $regionIds = collect([$region->id])->merge($region->descendants()->pluck('id'));
        
        return $query->where(function ($q) use ($regionIds) {
            $q->whereIn('state_region_id', $regionIds)
              ->orWhereIn('city_region_id', $regionIds)
              ->orWhereIn('neighborhood_region_id', $regionIds);
        });
    }

    // Helper methods
    public function hasPhysicalLocation()
    {
        return in_array($this->type, ['business_b2b', 'business_b2c', 'religious_org', 'point_of_interest', 'area_of_interest']) && $this->location;
    }

    /**
     * Get the canonical URL for this entry
     * Format: /places/{state-slug}/{city-slug}/{category-slug}/{business-slug}-{id}
     */
    public function getCanonicalUrl()
    {
        $parts = ['places'];
        
        // Geographic hierarchy
        if ($this->stateRegion) {
            $parts[] = $this->stateRegion->slug;
        }
        if ($this->cityRegion) {
            $parts[] = $this->cityRegion->slug;
        }
        
        // Category
        if ($this->category) {
            $parts[] = $this->category->slug;
        }
        
        // Entry with ID
        $parts[] = $this->slug . '-' . $this->id;
        
        return '/' . implode('/', $parts);
    }

    /**
     * Get the short URL for this entry
     * Format: /p/{id}
     */
    public function getShortUrl()
    {
        return '/p/' . $this->id;
    }

    public function isOnlineOnly()
    {
        return in_array($this->type, ['online', 'service']);
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
            return "/place/{$parentSlug}/{$childSlug}/{$this->slug}";
        }
        
        // If it's a root category or no category, use the legacy route as fallback
        return "/place/entry/{$this->slug}";
    }

    // Get region-based URL (new method)
    public function getRegionUrl()
    {
        $path = [];
        
        // Load regions if not already loaded
        if (!$this->relationLoaded('stateRegion')) {
            $this->load(['stateRegion', 'cityRegion', 'neighborhoodRegion']);
        }
        
        if ($this->stateRegion) {
            $path[] = $this->stateRegion->slug;
        }
        
        if ($this->cityRegion) {
            $path[] = $this->cityRegion->slug;
        }
        
        if ($this->neighborhoodRegion) {
            $path[] = $this->neighborhoodRegion->slug;
        }
        
        $path[] = $this->slug;
        
        return '/' . implode('/', $path);
    }

    // Add URL as an accessor attribute
    public function getUrlAttribute()
    {
        return $this->getUrl();
    }

    // Get the full region hierarchy for this entry
    public function getRegionHierarchy()
    {
        return collect([
            'state' => $this->stateRegion,
            'city' => $this->cityRegion,
            'neighborhood' => $this->neighborhoodRegion
        ])->filter();
    }

    // Check if entry needs region assignment
    public function needsRegionAssignment()
    {
        return !$this->regions_updated_at || 
               $this->regions_updated_at->lt(now()->subDays(7)) ||
               (!$this->state_region_id && !$this->city_region_id && $this->hasPhysicalLocation());
    }
}