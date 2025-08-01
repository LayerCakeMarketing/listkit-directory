<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Traits\HasTags;
use App\Traits\Followable;
use App\Traits\Saveable;
use App\Traits\Likeable;
use App\Traits\Commentable;

class Place extends Model
{
    use HasFactory, HasTags, Followable, Saveable, Likeable, Commentable;
    
    protected $table = 'directory_entries';

    protected $fillable = [
        'title', 'slug', 'description', 'type', 'category_id', 'region_id',
        'tags', 'owner_user_id', 'created_by_user_id', 'updated_by_user_id',
        'phone', 'email', 'website_url', 'links', 'social_links', 'featured_image',
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
        'state_region_id', 'city_region_id', 'neighborhood_region_id', 'regions_updated_at',
        
        // Approval/Rejection fields
        'rejection_reason', 'rejected_at', 'rejected_by',
        'approval_notes', 'approved_by',
        
        // Ownership and subscription fields
        'subscription_tier', 'subscription_expires_at', 'stripe_customer_id', 'stripe_subscription_id',
        'claimed_at', 'ownership_transferred_at', 'ownership_transferred_by'
    ];

    protected $casts = [
        'tags' => 'array',
        'links' => 'array',
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
        'rejected_at' => 'datetime',
        
        // Ownership fields
        'claimed_at' => 'datetime',
        'subscription_expires_at' => 'datetime',
        'ownership_transferred_at' => 'datetime',
    ];

    protected $appends = ['canonical_url', 'name'];

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
        
        // Update region place counts after save
        static::saved(function ($entry) {
            $entry->updateRegionPlaceCounts();
        });
        
        // Update region place counts after delete
        static::deleted(function ($entry) {
            $entry->updateRegionPlaceCounts();
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

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
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
        return $this->hasMany(Claim::class, 'place_id');
    }

    public function ownerUser()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    /**
     * Get all managers for this place (polymorphic)
     */
    public function managers()
    {
        return $this->hasMany(PlaceManager::class);
    }

    /**
     * Get active managers for this place
     */
    public function activeManagers()
    {
        return $this->managers()->active()->accepted();
    }

    /**
     * Check if a user can manage this place
     */
    public function canBeManaged(User $user): bool
    {
        // Check if user is the creator
        if ($this->created_by_user_id === $user->id) {
            return true;
        }

        // Check if user is the owner
        if ($this->owner_user_id === $user->id) {
            return true;
        }

        // Check if user is an active manager
        return $this->activeManagers()
            ->where('manageable_type', User::class)
            ->where('manageable_id', $user->id)
            ->exists();
    }

    /**
     * Add a manager to this place
     */
    public function addManager($manageable, string $role = 'manager', array $permissions = []): PlaceManager
    {
        return $this->managers()->create([
            'manageable_type' => get_class($manageable),
            'manageable_id' => $manageable->id,
            'role' => $role,
            'permissions' => $permissions,
            'invited_at' => now(),
            'invited_by' => auth()->id(),
        ]);
    }

    /**
     * Remove a manager from this place
     */
    public function removeManager($manageable): bool
    {
        return $this->managers()
            ->where('manageable_type', get_class($manageable))
            ->where('manageable_id', $manageable->id)
            ->delete() > 0;
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
        
        // Geographic hierarchy - using full state name
        if ($this->stateRegion) {
            // Use full state name slug instead of abbreviation
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
     * Accessor for canonical_url attribute
     */
    public function getCanonicalUrlAttribute()
    {
        return $this->getCanonicalUrl();
    }
    
    /**
     * Get name attribute (alias for title)
     */
    public function getNameAttribute()
    {
        return $this->title;
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

    /**
     * Update place counts for associated regions
     */
    public function updateRegionPlaceCounts()
    {
        // Update state region count
        if ($this->state_region_id) {
            $stateCount = self::where('state_region_id', $this->state_region_id)
                ->where('status', 'published')
                ->count();
            
            \App\Models\Region::where('id', $this->state_region_id)
                ->update(['cached_place_count' => $stateCount]);
        }
        
        // Update city region count
        if ($this->city_region_id) {
            $cityCount = self::where('city_region_id', $this->city_region_id)
                ->where('status', 'published')
                ->count();
            
            \App\Models\Region::where('id', $this->city_region_id)
                ->update(['cached_place_count' => $cityCount]);
        }
        
        // Update neighborhood region count
        if ($this->neighborhood_region_id) {
            $neighborhoodCount = self::where('neighborhood_region_id', $this->neighborhood_region_id)
                ->where('status', 'published')
                ->count();
            
            \App\Models\Region::where('id', $this->neighborhood_region_id)
                ->update(['cached_place_count' => $neighborhoodCount]);
        }
    }

    public function canBeEdited()
    {
        $user = auth()->user();
        if (!$user) return false;
        
        // Admin and Manager can edit anything
        if (in_array($user->role, ['admin', 'manager'])) return true;
        
        // Editor can edit published entries
        if ($user->role === 'editor' && $this->status === 'published') return true;
        
        // Regular users cannot edit places that are pending review
        if ($this->status === 'pending_review' && !in_array($user->role, ['admin', 'manager'])) {
            return false;
        }
        
        // Check if user can manage this place (covers creator, owner, and managers)
        return $this->canBeManaged($user);
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

    // Ownership and subscription methods
    
    /**
     * Check if the place has an active claim request
     */
    public function hasActiveClaim(): bool
    {
        return $this->claims()->pending()->exists();
    }

    /**
     * Get the active claim if any
     */
    public function activeClaim()
    {
        return $this->claims()->pending()->first();
    }

    /**
     * Check if a user can claim this place
     */
    public function canBeClaimedBy(User $user): bool
    {
        // Can't claim if already owned
        if ($this->owner_user_id) {
            return false;
        }

        // Can't claim if user already has a pending claim
        if ($this->claims()->where('user_id', $user->id)->pending()->exists()) {
            return false;
        }

        // Can't claim if another claim is pending
        if ($this->hasActiveClaim()) {
            return false;
        }

        return true;
    }

    /**
     * Get the owner who transferred ownership
     */
    public function ownershipTransferredBy()
    {
        return $this->belongsTo(User::class, 'ownership_transferred_by');
    }

    /**
     * Check if subscription is active
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscription_tier !== 'free' && 
               $this->subscription_expires_at && 
               $this->subscription_expires_at->isFuture();
    }

    /**
     * Get subscription tier label
     */
    public function getSubscriptionTierLabelAttribute(): string
    {
        return match($this->subscription_tier) {
            'tier1' => 'Professional',
            'tier2' => 'Premium',
            default => 'Free'
        };
    }

    /**
     * Check if user has access to tier features
     */
    public function hasTierAccess(string $feature): bool
    {
        $tierFeatures = [
            'free' => ['basic_edits', 'respond_reviews', 'update_hours'],
            'tier1' => ['basic_edits', 'respond_reviews', 'update_hours', 'analytics', 'featured_badge', 'priority_support'],
            'tier2' => ['basic_edits', 'respond_reviews', 'update_hours', 'analytics', 'featured_badge', 'priority_support', 'multi_location', 'team_access', 'custom_branding']
        ];

        $currentTier = $this->hasActiveSubscription() ? $this->subscription_tier : 'free';
        
        return in_array($feature, $tierFeatures[$currentTier] ?? []);
    }

    /**
     * Scope for claimed places
     */
    public function scopeClaimed($query)
    {
        return $query->where('is_claimed', true)->whereNotNull('owner_user_id');
    }

    /**
     * Scope for unclaimed places
     */
    public function scopeUnclaimed($query)
    {
        return $query->where(function ($q) {
            $q->where('is_claimed', false)
              ->orWhereNull('owner_user_id');
        });
    }

    /**
     * Scope for places with active subscriptions
     */
    public function scopeWithActiveSubscription($query)
    {
        return $query->where('subscription_tier', '!=', 'free')
                    ->where('subscription_expires_at', '>', now());
    }
}