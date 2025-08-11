<?php

namespace App\Services;

use App\Models\Region;
use App\Models\Place;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Multi-layer caching strategy for region-place queries
 * Optimized for high-traffic scenarios with frequent map interactions
 */
class RegionCacheService
{
    /**
     * Cache duration constants (seconds)
     */
    const CACHE_FOREVER = 86400;        // 24 hours - Region metadata
    const CACHE_LONG = 3600;           // 1 hour - Place counts, statistics
    const CACHE_MEDIUM = 1800;         // 30 minutes - Place lists
    const CACHE_SHORT = 300;           // 5 minutes - Map viewport data
    const CACHE_REALTIME = 60;         // 1 minute - User-specific data

    /**
     * **LAYER 1: Region Metadata Cache**
     * Cache region boundaries, center points, and static data
     */
    public function cacheRegionMetadata(Region $region): array
    {
        return Cache::remember(
            "region_metadata_{$region->id}",
            self::CACHE_FOREVER,
            function () use ($region) {
                $bounds = null;
                $center = null;

                if ($region->boundary) {
                    $spatial = DB::selectOne("
                        SELECT 
                            ST_Extent(boundary)::text as bounds,
                            ST_AsGeoJSON(ST_Centroid(boundary)) as center
                        FROM regions WHERE id = ?
                    ", [$region->id]);

                    if ($spatial) {
                        // Parse PostgreSQL BOX format
                        if (preg_match('/BOX\(([\d\.\-]+) ([\d\.\-]+),([\d\.\-]+) ([\d\.\-]+)\)/', $spatial->bounds, $matches)) {
                            $bounds = [
                                'min_lng' => floatval($matches[1]),
                                'min_lat' => floatval($matches[2]),
                                'max_lng' => floatval($matches[3]),
                                'max_lat' => floatval($matches[4])
                            ];
                        }

                        if ($spatial->center) {
                            $centerData = json_decode($spatial->center);
                            $center = [
                                'lng' => $centerData->coordinates[0],
                                'lat' => $centerData->coordinates[1]
                            ];
                        }
                    }
                }

                return [
                    'id' => $region->id,
                    'name' => $region->name,
                    'type' => $region->type,
                    'level' => $region->level,
                    'bounds' => $bounds,
                    'center' => $center,
                    'is_park' => in_array($region->type, ['national_park', 'state_park', 'regional_park', 'local_park']),
                    'park_info' => $region->type === 'national_park' || $region->type === 'state_park' ? [
                        'park_system' => $region->park_system,
                        'established_date' => $region->established_date,
                        'area_acres' => $region->area_acres,
                        'entrance_fees' => $region->entrance_fees
                    ] : null
                ];
            }
        );
    }

    /**
     * **LAYER 2: Place Count Cache**
     * Cache aggregated statistics per region
     */
    public function cacheRegionStatistics(Region $region): array
    {
        return Cache::remember(
            "region_stats_{$region->id}",
            self::CACHE_LONG,
            function () use ($region) {
                // Use the materialized view if available
                $stats = DB::table('region_place_statistics')
                    ->where('region_id', $region->id)
                    ->first();

                if ($stats) {
                    return [
                        'total_places' => $stats->total_places,
                        'featured_places' => $stats->featured_places,
                        'verified_places' => $stats->verified_places,
                        'last_updated' => $stats->last_place_update
                    ];
                }

                // Fallback: direct query
                $directStats = $this->calculateDirectStats($region);
                
                // Update cached count in region model
                $region->update(['cached_place_count' => $directStats['total_places']]);
                
                return $directStats;
            }
        );
    }

    /**
     * **LAYER 3: Map Viewport Cache**
     * Cache places within common viewport bounds
     */
    public function cacheMapViewport(array $bounds, int $zoom): array
    {
        $key = "map_viewport_" . md5(implode('_', $bounds) . "_zoom_{$zoom}");
        
        return Cache::remember(
            $key,
            self::CACHE_SHORT,
            function () use ($bounds, $zoom) {
                [$north, $south, $east, $west] = $bounds;
                
                $query = DB::table('directory_entries_geospatial_cache')
                    ->whereBetween('latitude', [$south, $north])
                    ->whereBetween('longitude', [$west, $east]);

                if ($zoom < 10) {
                    // Low zoom: prioritize important places
                    $query->where('is_featured', true)
                          ->orderBy('is_verified', 'desc')
                          ->limit(100);
                } else {
                    $query->limit(500);
                }

                return [
                    'places' => $query->get()->toArray(),
                    'bounds' => $bounds,
                    'zoom' => $zoom,
                    'cached_at' => now()->toISOString()
                ];
            }
        );
    }

    /**
     * **LAYER 4: Place List Cache**
     * Cache paginated place lists for regions
     */
    public function cachePlaceList(Region $region, array $filters = [], int $page = 1): array
    {
        $filterKey = md5(serialize($filters));
        $key = "region_{$region->id}_places_p{$page}_{$filterKey}";
        
        return Cache::remember(
            $key,
            self::CACHE_MEDIUM,
            function () use ($region, $filters, $page) {
                $perPage = $filters['per_page'] ?? 20;
                $offset = ($page - 1) * $perPage;

                $query = Place::published()
                    ->select([
                        'id', 'name', 'slug', 'description', 'featured_image',
                        'latitude', 'longitude', 'category_id', 'is_featured', 'is_verified'
                    ])
                    ->with(['category:id,name,slug,color']);

                // Apply region filter
                $this->applyRegionFilter($query, $region);

                // Apply additional filters
                if ($filters['category_id'] ?? null) {
                    $query->where('category_id', $filters['category_id']);
                }

                if ($filters['featured_only'] ?? false) {
                    $query->where('is_featured', true);
                }

                $total = $query->count();
                $places = $query->orderByDesc('is_featured')
                               ->orderByDesc('view_count')
                               ->offset($offset)
                               ->limit($perPage)
                               ->get();

                return [
                    'data' => $places,
                    'meta' => [
                        'current_page' => $page,
                        'per_page' => $perPage,
                        'total' => $total,
                        'last_page' => ceil($total / $perPage),
                        'from' => $offset + 1,
                        'to' => min($offset + $perPage, $total)
                    ]
                ];
            }
        );
    }

    /**
     * **LAYER 5: Regional Cluster Cache**
     * Pre-compute common clustering scenarios
     */
    public function cacheRegionClusters(Region $region): array
    {
        return Cache::remember(
            "region_{$region->id}_clusters",
            self::CACHE_LONG,
            function () use ($region) {
                // Generate cluster data for zoom levels 8-15
                $clusters = [];
                
                for ($zoom = 8; $zoom <= 15; $zoom++) {
                    $clusterSize = $this->getClusterSize($zoom);
                    
                    $places = Place::published()
                        ->whereHas('regions', function ($query) use ($region) {
                            $query->where('region_id', $region->id);
                        })
                        ->select(['latitude', 'longitude', 'is_featured'])
                        ->whereNotNull('latitude')
                        ->get();

                    // Simple grid-based clustering
                    $clusteredPlaces = $this->gridCluster($places, $clusterSize);
                    
                    $clusters[$zoom] = [
                        'cluster_size' => $clusterSize,
                        'clusters' => $clusteredPlaces,
                        'total_places' => $places->count()
                    ];
                }

                return $clusters;
            }
        );
    }

    /**
     * **CACHE WARMING**: Pre-populate caches for popular regions
     */
    public function warmPopularRegionCaches(): int
    {
        $warmed = 0;
        
        // Get top 50 regions by place count
        $popularRegions = Region::where('cached_place_count', '>', 10)
            ->orderByDesc('cached_place_count')
            ->limit(50)
            ->get();

        foreach ($popularRegions as $region) {
            // Warm metadata cache
            $this->cacheRegionMetadata($region);
            
            // Warm statistics cache
            $this->cacheRegionStatistics($region);
            
            // Warm first page of places
            $this->cachePlaceList($region, [], 1);
            
            // For large regions, warm cluster cache
            if ($region->cached_place_count > 100) {
                $this->cacheRegionClusters($region);
            }
            
            $warmed++;
        }

        return $warmed;
    }

    /**
     * **CACHE INVALIDATION**: Smart cache clearing
     */
    public function invalidateRegionCache(Region $region, string $reason = 'update'): void
    {
        $patterns = [
            "region_metadata_{$region->id}",
            "region_stats_{$region->id}",
            "region_{$region->id}_places_*",
            "region_{$region->id}_clusters",
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($pattern, '*')) {
                // Clear pattern-based keys (Redis-specific)
                $this->clearCachePattern($pattern);
            } else {
                Cache::forget($pattern);
            }
        }

        // Also invalidate parent/child regions
        if ($region->parent_id) {
            $this->invalidateRegionCache($region->parent, 'child_update');
        }

        foreach ($region->children as $child) {
            $this->invalidateRegionCache($child, 'parent_update');
        }

        \Log::info("Invalidated cache for region {$region->id} due to: {$reason}");
    }

    /**
     * Calculate statistics directly from database
     */
    private function calculateDirectStats(Region $region): array
    {
        $query = Place::published();
        $this->applyRegionFilter($query, $region);

        return [
            'total_places' => $query->count(),
            'featured_places' => (clone $query)->where('is_featured', true)->count(),
            'verified_places' => (clone $query)->where('is_verified', true)->count(),
            'last_updated' => now()->toISOString()
        ];
    }

    /**
     * Apply region filter to query based on region level
     */
    private function applyRegionFilter($query, Region $region)
    {
        switch ($region->level) {
            case 1: // State
                $query->where('state_region_id', $region->id);
                break;
            case 2: // City
                $query->where('city_region_id', $region->id);
                break;
            case 3: // Neighborhood
                $query->where('neighborhood_region_id', $region->id);
                break;
            default:
                $query->where('region_id', $region->id);
        }
    }

    /**
     * Get appropriate cluster size for zoom level
     */
    private function getClusterSize(int $zoom): float
    {
        return match (true) {
            $zoom <= 8 => 10.0,    // ~10km clusters
            $zoom <= 10 => 5.0,    // ~5km clusters  
            $zoom <= 12 => 1.0,    // ~1km clusters
            $zoom <= 14 => 0.5,    // ~500m clusters
            default => 0.1         // ~100m clusters
        };
    }

    /**
     * Simple grid-based clustering
     */
    private function gridCluster($places, float $gridSize): array
    {
        $clusters = [];
        
        foreach ($places as $place) {
            $gridX = floor($place->longitude / $gridSize) * $gridSize;
            $gridY = floor($place->latitude / $gridSize) * $gridSize;
            $key = "{$gridX}_{$gridY}";
            
            if (!isset($clusters[$key])) {
                $clusters[$key] = [
                    'center' => ['lat' => $gridY + $gridSize/2, 'lng' => $gridX + $gridSize/2],
                    'count' => 0,
                    'featured' => 0
                ];
            }
            
            $clusters[$key]['count']++;
            if ($place->is_featured) {
                $clusters[$key]['featured']++;
            }
        }

        return array_values($clusters);
    }

    /**
     * Clear cache keys matching pattern (Redis-specific)
     */
    private function clearCachePattern(string $pattern): void
    {
        if (config('cache.default') === 'redis') {
            $redis = app('redis');
            $keys = $redis->keys(str_replace('*', '*', config('cache.prefix') . ':' . $pattern));
            if (!empty($keys)) {
                $redis->del($keys);
            }
        }
    }
}