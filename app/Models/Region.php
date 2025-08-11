<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Place;
use App\Traits\Saveable;

class Region extends Model
{
    use HasFactory, Saveable;

    protected $fillable = [
        'name',
        'full_name',
        'slug',
        'abbreviation',
        'type',
        'level',
        'parent_id',
        'metadata',
        'cached_place_count',
        'boundaries',
        'boundaries_simplified',
        'center_point',
        'cover_image',
        'cloudflare_image_id',
        'intro_text',
        'data_points',
        'is_featured',
        'meta_title',
        'meta_description',
        'custom_fields',
        'designation',
        // Park-specific fields
        'park_system',
        'park_designation',
        'area_acres',
        'established_date',
        'park_features',
        'park_activities',
        'park_website',
        'park_phone',
        'operating_hours',
        'entrance_fees',
        'reservations_required',
        'difficulty_level',
        'accessibility_features',
        'display_priority',
        'facts',
        'state_symbols',
        'geojson',
        'polygon_coordinates',
        'alternate_names',
        'is_user_defined',
        'created_by_user_id',
        'boundary',
        'center_point',
        'area_sq_km',
        'cache_updated_at'
    ];
    
    /**
     * Get the cloudflare_image_id attribute, handling missing column
     */
    public function getCloudflareImageIdAttribute($value)
    {
        // If the column doesn't exist in this database, return null
        if (!array_key_exists('cloudflare_image_id', $this->attributes)) {
            return null;
        }
        return $value;
    }

    protected $casts = [
        'metadata' => 'array',
        'level' => 'integer',
        'cached_place_count' => 'integer',
        'data_points' => 'array',
        'is_featured' => 'boolean',
        'custom_fields' => 'array',
        'display_priority' => 'integer',
        'facts' => 'array',
        'state_symbols' => 'array',
        'geojson' => 'array'
    ];

    protected $appends = ['cover_image_url'];

    // Boot method for model events
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($region) {
            if (empty($region->slug)) {
                $region->slug = Str::slug($region->name);
                
                // Handle duplicate slugs
                $count = static::where('slug', 'like', $region->slug . '%')
                    ->where('level', $region->level)
                    ->count();
                if ($count > 0) {
                    $region->slug = $region->slug . '-' . ($count + 1);
                }
            }
        });
    }

    // Relationships
    public function parent()
    {
        return $this->belongsTo(Region::class, 'parent_id');
    }

    public function coverImage()
    {
        return $this->morphMany(CloudflareImage::class, 'entity');
    }

    public function children()
    {
        return $this->hasMany(Region::class, 'parent_id');
    }

    public function places()
    {
        return $this->hasMany(Place::class);
    }

    // Alias for backward compatibility
    public function directoryEntries()
    {
        return $this->places();
    }
    
    // Alias for directoryEntries to match controller expectations
    public function entries()
    {
        return $this->places();
    }

    // New relationships for hierarchical regions
    public function stateEntries()
    {
        return $this->hasMany(Place::class, 'state_region_id');
    }

    public function cityEntries()
    {
        return $this->hasMany(Place::class, 'city_region_id');
    }

    public function neighborhoodEntries()
    {
        return $this->hasMany(Place::class, 'neighborhood_region_id');
    }

    // Curated lists relationship
    public function lists()
    {
        return $this->hasMany(CuratedList::class);
    }

    // Featured entries relationship
    public function featuredEntries()
    {
        return $this->belongsToMany(Place::class, 'region_featured_entries', 'region_id', 'directory_entry_id')
            ->withPivot('priority', 'label', 'tagline', 'custom_data', 'featured_until', 'is_active')
            ->withTimestamps()
            ->wherePivot('is_active', true)
            ->where(function ($query) {
                $query->whereNull('region_featured_entries.featured_until')
                    ->orWhere('region_featured_entries.featured_until', '>=', now());
            })
            ->orderByPivot('priority', 'asc');
    }

    // Featured lists relationship
    public function featuredLists()
    {
        return $this->belongsToMany(\App\Models\UserList::class, 'region_featured_lists', 'region_id', 'list_id')
            ->withPivot('priority', 'is_active')
            ->withTimestamps()
            ->wherePivot('is_active', true)
            ->orderByPivot('priority', 'asc');
    }

    // All featured entries (including inactive)
    public function allFeaturedEntries()
    {
        return $this->belongsToMany(Place::class, 'region_featured_entries', 'region_id', 'directory_entry_id')
            ->withPivot('priority', 'label', 'tagline', 'custom_data', 'featured_until', 'is_active')
            ->withTimestamps()
            ->orderByPivot('priority', 'asc');
    }

    // Scopes
    public function scopeStates($query)
    {
        return $query->where('type', 'state');
    }

    public function scopeCities($query)
    {
        return $query->where('type', 'city');
    }

    public function scopeCounties($query)
    {
        return $query->where('type', 'county');
    }

    public function scopeRootRegions($query)
    {
        return $query->whereNull('parent_id');
    }

    // Helper methods
    public function isRoot()
    {
        return is_null($this->parent_id);
    }

    public function hasChildren()
    {
        return $this->children()->exists();
    }

    public function getFullNameAttribute()
    {
        // If full_name is set in the database, use it
        if (!empty($this->attributes['full_name'])) {
            return $this->attributes['full_name'];
        }
        
        // Otherwise fall back to concatenating with parent
        if ($this->parent) {
            return $this->name . ', ' . $this->parent->name;
        }
        return $this->name;
    }

    // Get display name (prioritizes full_name over name)
    public function getDisplayNameAttribute()
    {
        return $this->full_name ?: $this->name;
    }

    // Get hierarchical path
    public function getPathAttribute()
    {
        $path = collect([$this]);
        $parent = $this->parent;
        
        while ($parent) {
            $path->prepend($parent);
            $parent = $parent->parent;
        }
        
        return $path;
    }

    // Get all descendant regions
    public function descendants()
    {
        $descendants = collect();
        
        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->descendants());
        }
        
        return $descendants;
    }

    // Spatial Query Methods
    public function scopeContainingPoint($query, $latitude, $longitude)
    {
        if (config('database.default') !== 'pgsql') {
            return $query;
        }

        return $query->whereRaw(
            'ST_Contains(boundary, ST_SetSRID(ST_MakePoint(?, ?), 4326))',
            [$longitude, $latitude]
        );
    }

    public function scopeIntersectingBounds($query, $bounds)
    {
        if (config('database.default') !== 'pgsql') {
            return $query;
        }

        // $bounds should be [minLng, minLat, maxLng, maxLat]
        return $query->whereRaw(
            'ST_Intersects(boundary, ST_MakeEnvelope(?, ?, ?, ?, 4326))',
            $bounds
        );
    }

    public function scopeWithinDistance($query, $latitude, $longitude, $distanceInMeters)
    {
        if (config('database.default') !== 'pgsql') {
            return $query;
        }

        return $query->whereRaw(
            'ST_DWithin(center_point, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)',
            [$longitude, $latitude, $distanceInMeters]
        );
    }

    // Helper methods for spatial operations
    public function containsLocation($latitude, $longitude)
    {
        if (config('database.default') !== 'pgsql' || !$this->boundary) {
            return false;
        }

        $result = DB::selectOne(
            'SELECT ST_Contains(boundary, ST_SetSRID(ST_MakePoint(?, ?), 4326)) as contains FROM regions WHERE id = ?',
            [$longitude, $latitude, $this->id]
        );

        return $result->contains;
    }

    public function getBoundsArray()
    {
        if (config('database.default') !== 'pgsql' || !$this->boundary) {
            return null;
        }

        $result = DB::selectOne(
            'SELECT ST_Extent(boundary)::text as bounds FROM regions WHERE id = ?',
            [$this->id]
        );

        if (!$result->bounds) {
            return null;
        }

        // Parse BOX(minx miny, maxx maxy)
        preg_match('/BOX\(([\d\.\-]+) ([\d\.\-]+),([\d\.\-]+) ([\d\.\-]+)\)/', $result->bounds, $matches);
        
        return [
            'min_lng' => floatval($matches[1]),
            'min_lat' => floatval($matches[2]),
            'max_lng' => floatval($matches[3]),
            'max_lat' => floatval($matches[4])
        ];
    }

    public function getGeoJson($simplified = false)
    {
        if (config('database.default') !== 'pgsql') {
            return null;
        }

        // Only use boundary column since boundaries_simplified doesn't exist
        $column = 'boundary';
        
        $result = DB::selectOne(
            "SELECT ST_AsGeoJSON({$column}) as geojson FROM regions WHERE id = ?",
            [$this->id]
        );

        return $result->geojson ? json_decode($result->geojson) : null;
    }

    // Update cached place count
    public function updatePlaceCount()
    {
        $count = $this->places()
            ->where('status', 'published')
            ->count();

        // Also count places that have this region at any level
        if ($this->level == 1) { // State
            $count = Place::where('state_region_id', $this->id)
                ->where('status', 'published')
                ->count();
        } elseif ($this->level == 2) { // City
            $count = Place::where('city_region_id', $this->id)
                ->where('status', 'published')
                ->count();
        } elseif ($this->level == 3) { // Neighborhood
            $count = Place::where('neighborhood_region_id', $this->id)
                ->where('status', 'published')
                ->count();
        }

        $this->update(['cached_place_count' => $count]);
    }

    // Get URL path for this region
    public function getUrlPath()
    {
        $path = collect([$this->slug]);
        $parent = $this->parent;
        
        while ($parent && $parent->level > 0) {
            $path->prepend($parent->slug);
            $parent = $parent->parent;
        }
        
        return $path->implode('/');
    }

    // Scope for efficient hierarchy loading
    public function scopeWithHierarchy($query)
    {
        return $query->with(['parent.parent.parent']);
    }

    // Level-specific scopes
    public function scopeNeighborhoods($query)
    {
        return $query->where('level', 3);
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    // Featured regions scope
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)
            ->orderBy('display_priority', 'desc')
            ->orderBy('cached_place_count', 'desc');
    }

    // Check if region has content
    public function hasContent()
    {
        return $this->intro_text || $this->cover_image || $this->data_points || $this->featuredEntries()->exists();
    }

    // Get formatted data points
    public function getFormattedDataPoints()
    {
        if (!$this->data_points) {
            return collect();
        }

        return collect($this->data_points)->map(function ($value, $key) {
            return [
                'label' => ucwords(str_replace('_', ' ', $key)),
                'value' => $value,
                'key' => $key
            ];
        });
    }

    // Get cover image URL with fallback
    public function getCoverImageUrl($default = null)
    {
        // First check if cover_image already contains a Cloudflare URL
        if ($this->cover_image && strpos($this->cover_image, 'imagedelivery.net') !== false) {
            return $this->cover_image;
        }
        
        // Then check if we have a Cloudflare image ID
        if ($this->cloudflare_image_id) {
            // Use the Cloudflare image delivery URL from config
            $deliveryUrl = config('services.cloudflare.delivery_url', 'https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A');
            
            // Ensure we have a trailing slash before appending the image ID
            $deliveryUrl = rtrim($deliveryUrl, '/');
            return "{$deliveryUrl}/{$this->cloudflare_image_id}/public";
        }
        
        if ($this->cover_image) {
            // If it's a full URL, return as is
            if (filter_var($this->cover_image, FILTER_VALIDATE_URL)) {
                return $this->cover_image;
            }
            // Otherwise assume it's a storage path
            return asset('storage/' . $this->cover_image);
        }

        return $default ?: asset('images/default-region-cover.jpg');
    }

    // Accessor for cover_image_url attribute
    public function getCoverImageUrlAttribute()
    {
        return $this->getCoverImageUrl();
    }

    // Cache key helpers
    public function getCacheKey($suffix = '')
    {
        $key = "region_{$this->id}";
        return $suffix ? "{$key}_{$suffix}" : $key;
    }

    public function clearCache()
    {
        $cacheKeys = [
            $this->getCacheKey(),
            $this->getCacheKey('featured'),
            $this->getCacheKey('metadata'),
            $this->getCacheKey('entries'),
            $this->getCacheKey('children'),
            $this->getCacheKey('path')
        ];

        foreach ($cacheKeys as $key) {
            cache()->forget($key);
        }
    }

    // Override update to clear cache
    public function update(array $attributes = [], array $options = [])
    {
        $result = parent::update($attributes, $options);
        
        if ($result) {
            $this->clearCache();
        }

        return $result;
    }

    // Get region data for frontend
    public function toFrontend()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'display_name' => $this->display_name,
            'full_name' => $this->full_name,
            'slug' => $this->slug,
            'type' => $this->type,
            'level' => $this->level,
            'url' => '/local/' . $this->getUrlPath(),
            'cover_image' => $this->getCoverImageUrl(),
            'cover_image_url' => $this->getCoverImageUrl(),
            'cloudflare_image_id' => $this->cloudflare_image_id,
            'intro_text' => $this->intro_text,
            'data_points' => $this->getFormattedDataPoints()->values()->toArray(),
            'is_featured' => $this->is_featured,
            'place_count' => $this->cached_place_count,
            'meta' => [
                'title' => $this->meta_title ?: $this->display_name . ' Directory',
                'description' => $this->meta_description ?: "Explore {$this->display_name} - Find the best places, businesses, and services in {$this->display_name}."
            ],
            'has_content' => $this->hasContent(),
            'parent' => $this->parent ? [
                'id' => $this->parent->id,
                'name' => $this->parent->name,
                'display_name' => $this->parent->display_name,
                'slug' => $this->parent->slug,
                'url' => '/local/' . $this->parent->getUrlPath()
            ] : null
        ];
    }
    
    // Helper methods for facts
    public function getFact($key, $default = null)
    {
        return data_get($this->facts, $key, $default);
    }
    
    public function setFact($key, $value)
    {
        $facts = $this->facts ?: [];
        data_set($facts, $key, $value);
        $this->facts = $facts;
        return $this;
    }
    
    // Helper methods for state symbols
    public function getStateSymbol($key, $default = null)
    {
        return data_get($this->state_symbols, $key, $default);
    }
    
    public function setStateSymbol($key, $value)
    {
        $symbols = $this->state_symbols ?: [];
        data_set($symbols, $key, $value);
        $this->state_symbols = $symbols;
        return $this;
    }
    
    // Check if region has valid geodata
    public function hasGeodata()
    {
        return !empty($this->geojson) || !empty($this->polygon_coordinates);
    }
    
    // Convert polygon coordinates to GeoJSON
    public function polygonToGeojson()
    {
        if (empty($this->polygon_coordinates)) {
            return null;
        }
        
        // Parse PostgreSQL polygon format: ((x1,y1),(x2,y2),...)
        preg_match_all('/\(([^,]+),([^)]+)\)/', $this->polygon_coordinates, $matches);
        
        if (empty($matches[1])) {
            return null;
        }
        
        $coordinates = [];
        for ($i = 0; $i < count($matches[1]); $i++) {
            $coordinates[] = [(float)$matches[1][$i], (float)$matches[2][$i]];
        }
        
        // Close the polygon if not already closed
        if ($coordinates[0] !== $coordinates[count($coordinates) - 1]) {
            $coordinates[] = $coordinates[0];
        }
        
        return [
            'type' => 'Polygon',
            'coordinates' => [$coordinates]
        ];
    }

    // **NEW METHODS FOR ENHANCED PARK & GEOSPATIAL SUPPORT**

    /**
     * Get places within this region using optimized spatial queries
     */
    public function getPlacesWithinBoundary($limit = 500)
    {
        if (!$this->boundary) {
            return collect();
        }

        // Using Place model which maps to directory_entries table
        return Place::where('status', 'published')
            ->whereRaw('ST_Contains(?, ST_MakePoint(longitude, latitude))', [$this->boundary])
            ->with(['category:id,name,slug,color'])
            ->select(['id', 'title', 'slug', 'latitude', 'longitude', 'category_id', 'is_featured', 'is_verified'])
            ->limit($limit)
            ->get();
    }

    /**
     * Check if this region is a park type
     */
    public function isPark(): bool
    {
        return in_array($this->type, ['national_park', 'state_park', 'regional_park', 'local_park']);
    }

    /**
     * Get park-specific information
     */
    public function getParkInfo(): ?array
    {
        if (!$this->isPark()) {
            return null;
        }

        return [
            'system' => $this->park_system,
            'designation' => $this->park_designation,
            'established' => $this->established_date,
            'area_acres' => $this->area_acres,
            'features' => $this->park_features,
            'activities' => $this->park_activities,
            'website' => $this->park_website,
            'phone' => $this->park_phone,
            'hours' => $this->operating_hours,
            'fees' => $this->entrance_fees,
            'reservations_required' => $this->reservations_required,
            'difficulty' => $this->difficulty_level,
            'accessibility' => $this->accessibility_features
        ];
    }

    /**
     * Get clustered places for map display at specific zoom level
     */
    public function getClusteredPlaces(int $zoomLevel = 12): array
    {
        // Use cached clusters if available
        $clusters = Cache::remember(
            "region_{$this->id}_clusters_zoom_{$zoomLevel}",
            3600, // 1 hour cache
            function () use ($zoomLevel) {
                return DB::select('
                    SELECT * FROM place_clusters 
                    WHERE region_id = ? AND zoom_level = ?
                    ORDER BY place_count DESC
                ', [$this->id, $zoomLevel]);
            }
        );

        if (!empty($clusters)) {
            return $clusters;
        }

        // Fallback: generate clusters on-the-fly
        return $this->generateClustersForZoom($zoomLevel);
    }

    /**
     * Get region's relationship to parks (if it contains or is contained by parks)
     */
    public function getParkRelationships(): array
    {
        if (config('database.default') !== 'pgsql' || !$this->center_point) {
            return ['contains' => [], 'within' => []];
        }

        // Parks that contain this region
        $containingParks = Region::whereIn('type', ['national_park', 'state_park', 'regional_park', 'local_park'])
            ->whereNotNull('boundary')
            ->whereRaw('ST_Contains(boundary, ?)', [$this->center_point])
            ->select(['id', 'name', 'type', 'park_system'])
            ->get();

        // Parks contained within this region
        $containedParks = [];
        if ($this->boundary) {
            $containedParks = Region::whereIn('type', ['national_park', 'state_park', 'regional_park', 'local_park'])
                ->whereNotNull('boundary')
                ->whereRaw('ST_Contains(?, boundary)', [$this->boundary])
                ->select(['id', 'name', 'type', 'park_system'])
                ->get();
        }

        return [
            'within' => $containingParks,
            'contains' => $containedParks
        ];
    }

    /**
     * Get optimal viewport bounds for map display
     */
    public function getMapViewport(float $padding = 0.1): ?array
    {
        $bounds = $this->getBoundsArray();
        
        if (!$bounds) {
            return null;
        }

        // Add padding for better map display
        $latPadding = ($bounds['max_lat'] - $bounds['min_lat']) * $padding;
        $lngPadding = ($bounds['max_lng'] - $bounds['min_lng']) * $padding;

        return [
            'center' => [
                'lat' => ($bounds['max_lat'] + $bounds['min_lat']) / 2,
                'lng' => ($bounds['max_lng'] + $bounds['min_lng']) / 2
            ],
            'bounds' => [
                'north' => $bounds['max_lat'] + $latPadding,
                'south' => $bounds['min_lat'] - $latPadding,
                'east' => $bounds['max_lng'] + $lngPadding,
                'west' => $bounds['min_lng'] - $lngPadding
            ]
        ];
    }

    /**
     * Search places within region with spatial optimization
     */
    public function searchPlaces(string $query, array $options = []): \Illuminate\Support\Collection
    {
        $searchQuery = Place::where('status', 'published')
            ->whereRaw("to_tsvector('english', name || ' ' || COALESCE(description, '')) @@ plainto_tsquery('english', ?)", [$query]);

        // Apply region filter based on type
        if ($this->isPark() && $this->boundary) {
            // Use spatial containment for parks
            $searchQuery->whereRaw('ST_Contains(?, ST_MakePoint(longitude, latitude))', [$this->boundary]);
        } else {
            // Use hierarchical relationship for traditional regions
            switch ($this->level) {
                case 1:
                    $searchQuery->where('state_region_id', $this->id);
                    break;
                case 2:
                    $searchQuery->where('city_region_id', $this->id);
                    break;
                case 3:
                    $searchQuery->where('neighborhood_region_id', $this->id);
                    break;
            }
        }

        if ($options['category_id'] ?? null) {
            $searchQuery->where('category_id', $options['category_id']);
        }

        return $searchQuery
            ->with(['category:id,name,slug'])
            ->limit($options['limit'] ?? 50)
            ->get();
    }

    /**
     * Generate clusters for specific zoom level
     */
    private function generateClustersForZoom(int $zoomLevel): array
    {
        $gridSize = $this->getGridSizeForZoom($zoomLevel);
        
        $places = $this->getPlacesWithinBoundary(1000);
        
        $clusters = [];
        foreach ($places as $place) {
            $gridX = floor($place->longitude / $gridSize) * $gridSize;
            $gridY = floor($place->latitude / $gridSize) * $gridSize;
            $key = "{$gridX}_{$gridY}";
            
            if (!isset($clusters[$key])) {
                $clusters[$key] = (object) [
                    'cluster_geom' => ['lat' => $gridY + $gridSize/2, 'lng' => $gridX + $gridSize/2],
                    'place_count' => 0,
                    'featured_count' => 0,
                    'verified_count' => 0
                ];
            }
            
            $clusters[$key]->place_count++;
            if ($place->is_featured) $clusters[$key]->featured_count++;
            if ($place->is_verified) $clusters[$key]->verified_count++;
        }

        return array_values($clusters);
    }

    /**
     * Get appropriate grid size for zoom level
     */
    private function getGridSizeForZoom(int $zoomLevel): float
    {
        return match (true) {
            $zoomLevel <= 8 => 0.1,    // ~10km
            $zoomLevel <= 10 => 0.05,  // ~5km
            $zoomLevel <= 12 => 0.01,  // ~1km
            $zoomLevel <= 14 => 0.005, // ~500m
            default => 0.001           // ~100m
        };
    }
}