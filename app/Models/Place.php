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
        
        // Geospatial fields
        'latitude', 'longitude', 'address_line1', 'address_line2', 'city', 'state', 'zip_code', 'country',
        'location_updated_at', 'location_verified',
        
        // Hierarchical regions
        'state_region_id', 'city_region_id', 'neighborhood_region_id', 'park_region_id', 'regions_updated_at',
        
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
        
        // Geospatial fields
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'location_updated_at' => 'datetime',
        'location_verified' => 'boolean',
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

    public function parkRegion()
    {
        return $this->belongsTo(Region::class, 'park_region_id');
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

    /**
     * Scope to efficiently load relationships needed for URL generation
     * 
     * This scope loads all necessary relationships to avoid N+1 queries
     * when generating canonical URLs for multiple places.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithUrlRelations($query)
    {
        return $query->with([
            'stateRegion:id,name,slug',
            'cityRegion:id,name,slug', 
            'category:id,name,slug,parent_id',
            'category.parent:id,name,slug'
        ]);
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

    // Geospatial scopes for distance-based queries
    public function scopeWithinRadius($query, $latitude, $longitude, $radiusInMiles = 25)
    {
        $radiusInMeters = $radiusInMiles * 1609.34;
        
        return $query->whereNotNull('geom')
            ->whereRaw(
                'ST_DWithin(geom, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)',
                [$longitude, $latitude, $radiusInMeters]
            )
            ->addSelect(\DB::raw("
                ST_Distance(geom, ST_SetSRID(ST_MakePoint({$longitude}, {$latitude}), 4326)::geography) as distance_meters,
                ROUND(ST_Distance(geom, ST_SetSRID(ST_MakePoint({$longitude}, {$latitude}), 4326)::geography) * 0.000621371, 2) as distance_miles
            "));
    }

    public function scopeWithinBoundingBox($query, $northLat, $southLat, $eastLng, $westLng)
    {
        return $query->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereBetween('latitude', [$southLat, $northLat])
            ->whereBetween('longitude', [$westLng, $eastLng]);
    }

    public function scopeNearbyFast($query, $latitude, $longitude, $radiusInMiles = 25)
    {
        // Fast bounding box approximation for initial filtering
        $latRange = $radiusInMiles / 69; // 1 degree lat ≈ 69 miles
        $lngRange = $radiusInMiles / (69 * cos(deg2rad($latitude))); // Adjust for longitude
        
        return $query->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereBetween('latitude', [$latitude - $latRange, $latitude + $latRange])
            ->whereBetween('longitude', [$longitude - $lngRange, $longitude + $lngRange]);
    }

    public function scopeOrderByDistance($query, $latitude, $longitude)
    {
        return $query->whereNotNull('geom')
            ->orderByRaw(
                'ST_Distance(geom, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography)',
                [$longitude, $latitude]
            );
    }

    public function scopeWithCoordinates($query)
    {
        return $query->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereNotNull('geom');
    }

    public function scopeNeedsGeocoding($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('latitude')
              ->orWhereNull('longitude')
              ->orWhereNull('location_updated_at')
              ->orWhere('location_verified', false);
        })->whereNotNull('address_line1')
          ->orWhereNotNull('city');
    }

    // Helper methods
    public function hasPhysicalLocation()
    {
        return in_array($this->type, ['business_b2b', 'business_b2c', 'religious_org', 'point_of_interest', 'area_of_interest']) && $this->location;
    }

    /**
     * Get the canonical URL for this entry
     * Format: /places/{state-slug}/{city-slug}/{parent-category-slug}/{business-slug}-{id}
     * 
     * Uses parent categories for cleaner URLs and better SEO.
     * For subcategories, uses the parent category. For root categories, uses the category itself.
     * 
     * Performance Note: For bulk operations, use the withUrlRelations() scope to avoid N+1 queries:
     * Place::withUrlRelations()->get()->each(fn($place) => $place->getCanonicalUrl())
     */
    public function getCanonicalUrl()
    {
        $parts = ['places'];
        
        // Geographic hierarchy - using full state name
        if ($this->stateRegion) {
            $parts[] = $this->stateRegion->slug;
        }
        if ($this->cityRegion) {
            $parts[] = $this->cityRegion->slug;
        }
        
        // Category - use parent category for better URL structure
        if ($this->category) {
            $parentCategory = $this->getParentCategoryForUrl();
            if ($parentCategory) {
                $parts[] = $parentCategory->slug;
            }
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
     * Get the parent category for URL construction
     * 
     * Performance optimized method that:
     * - Returns parent category for subcategories
     * - Returns the category itself for root categories
     * - Handles cases where category is not loaded
     * - Returns null if no category is assigned
     * 
     * @return Category|null
     */
    public function getParentCategoryForUrl()
    {
        if (!$this->category) {
            // Load category if not already loaded
            if (!$this->relationLoaded('category') && $this->category_id) {
                $this->load('category.parent');
            } else {
                return null;
            }
        }
        
        // If category has a parent, use the parent for URL
        if ($this->category->parent_id && $this->category->relationLoaded('parent')) {
            return $this->category->parent;
        } elseif ($this->category->parent_id && !$this->category->relationLoaded('parent')) {
            // Load parent if needed
            $this->category->load('parent');
            return $this->category->parent;
        }
        
        // If no parent, use the category itself (it's a root category)
        return $this->category;
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

    // Get the URL for this entry (legacy method - use getCanonicalUrl() for new implementations)
    public function getUrl()
    {
        if (!$this->category) {
            $this->load('category');
        }
        
        // Use parent category logic for consistency with canonical URLs
        $parentCategory = $this->getParentCategoryForUrl();
        
        if ($parentCategory && $this->category && $this->category->parent_id) {
            // For subcategories, include both parent and child in URL
            $parentSlug = $parentCategory->slug;
            $childSlug = $this->category->slug;
            return "/place/{$parentSlug}/{$childSlug}/{$this->slug}";
        } elseif ($parentCategory) {
            // For root categories, use simplified URL
            return "/place/{$parentCategory->slug}/{$this->slug}";
        }
        
        // Fallback for places without categories
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

    // Geospatial helper methods
    
    /**
     * Get the full address string
     */
    public function getFullAddressAttribute()
    {
        $parts = array_filter([
            $this->address_line1,
            $this->address_line2,
            $this->city,
            $this->state,
            $this->zip_code
        ]);

        return implode(', ', $parts);
    }

    /**
     * Check if place has valid coordinates
     */
    public function hasValidCoordinates(): bool
    {
        return $this->latitude !== null 
            && $this->longitude !== null
            && $this->latitude >= -90 
            && $this->latitude <= 90
            && $this->longitude >= -180 
            && $this->longitude <= 180;
    }

    /**
     * Calculate distance to another place or coordinates
     */
    public function distanceTo($target, $unit = 'miles')
    {
        if (!$this->hasValidCoordinates()) {
            return null;
        }

        if ($target instanceof Place) {
            if (!$target->hasValidCoordinates()) {
                return null;
            }
            $targetLat = $target->latitude;
            $targetLng = $target->longitude;
        } elseif (is_array($target) && isset($target['lat'], $target['lng'])) {
            $targetLat = $target['lat'];
            $targetLng = $target['lng'];
        } else {
            return null;
        }

        // Use PostGIS for accurate calculation
        $distance = \DB::selectOne("
            SELECT ST_Distance(
                ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography,
                ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography
            ) as distance
        ", [$this->longitude, $this->latitude, $targetLng, $targetLat]);

        $distanceMeters = $distance->distance;
        
        return match($unit) {
            'miles' => $distanceMeters * 0.000621371,
            'kilometers', 'km' => $distanceMeters / 1000,
            'meters', 'm' => $distanceMeters,
            default => $distanceMeters * 0.000621371
        };
    }

    /**
     * Get nearby places within radius
     */
    public function getNearbyPlaces($radiusInMiles = 5, $limit = 10)
    {
        if (!$this->hasValidCoordinates()) {
            return collect();
        }

        return static::published()
            ->where('id', '!=', $this->id)
            ->withinRadius($this->latitude, $this->longitude, $radiusInMiles)
            ->orderByDistance($this->latitude, $this->longitude)
            ->limit($limit)
            ->get();
    }

    /**
     * Update coordinates and trigger geometry update
     */
    public function updateCoordinates($latitude, $longitude, $source = 'manual_correction')
    {
        $this->update([
            'latitude' => $latitude,
            'longitude' => $longitude,
            'location_source' => $source,
            'location_updated_at' => now(),
            'location_verified' => true
        ]);

        return $this;
    }

    /**
     * Get coordinates as array
     */
    public function getCoordinatesAttribute()
    {
        if (!$this->hasValidCoordinates()) {
            return null;
        }

        return [
            'lat' => (float) $this->latitude,
            'lng' => (float) $this->longitude
        ];
    }

    /**
     * Check if place needs geocoding
     */
    public function needsGeocoding(): bool
    {
        // Has address but no coordinates
        if (($this->address_line1 || $this->city) && !$this->hasValidCoordinates()) {
            return true;
        }

        // Location not updated recently and not verified
        if (!$this->location_verified && 
            (!$this->location_updated_at || $this->location_updated_at->lt(now()->subDays(30)))) {
            return true;
        }

        return false;
    }

    /**
     * Get geocoding address string
     */
    public function getGeocodingAddress(): ?string
    {
        if (!$this->address_line1 && !$this->city) {
            return null;
        }

        $parts = array_filter([
            $this->address_line1,
            $this->city,
            $this->state,
            $this->zip_code,
            $this->country === 'US' ? 'USA' : $this->country
        ]);

        return implode(', ', $parts);
    }

    /**
     * Scope query to select distance to a point
     */
    public function scopeSelectDistanceTo($query, $lat, $lng)
    {
        return $query->addSelect(\DB::raw(
            "ST_Distance(
                ST_SetSRID(ST_MakePoint(longitude, latitude), 4326)::geography,
                ST_SetSRID(ST_MakePoint({$lng}, {$lat}), 4326)::geography
            ) as distance_meters"
        ));
    }

    /**
     * Scope query to filter places with valid coordinates
     */
    public function scopeHasValidCoordinates($query)
    {
        return $query->whereNotNull('latitude')
                     ->whereNotNull('longitude')
                     ->where('latitude', '>=', -90)
                     ->where('latitude', '<=', 90)
                     ->where('longitude', '>=', -180)
                     ->where('longitude', '<=', 180);
    }

}