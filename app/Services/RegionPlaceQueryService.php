<?php

namespace App\Services;

use App\Models\Region;
use App\Models\Place;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Optimized service for region-place geospatial queries
 * Designed for high performance with 10K+ places per region
 */
class RegionPlaceQueryService
{
    /**
     * Get places within region boundaries with clustering support
     * Optimized for Mapbox clustering and two-column layout
     */
    public function getPlacesInRegion(Region $region, array $options = []): array
    {
        $cacheKey = "region_{$region->id}_places_" . md5(serialize($options));
        $cacheMinutes = $options['cache_minutes'] ?? 30;

        return Cache::remember($cacheKey, $cacheMinutes * 60, function () use ($region, $options) {
            // Base query with spatial optimization
            $query = Place::where('status', 'published')
                ->select([
                    'id', 'title', 'slug', 'latitude', 'longitude', 
                    'category_id', 'is_featured', 'is_verified', 'view_count'
                ])
                ->with(['category:id,name,slug,color']);

            // **METHOD 1: Direct region relationship (fastest for most cases)**
            if ($region->type !== 'national_park' && $region->type !== 'state_park') {
                $query = $this->addRegionHierarchyFilter($query, $region);
            } else {
                // **METHOD 2: Spatial containment for parks (more accurate)**
                $query = $this->addSpatialContainmentFilter($query, $region);
            }

            // Apply filters
            if ($options['category_id'] ?? null) {
                $query->where('category_id', $options['category_id']);
            }

            if ($options['featured_only'] ?? false) {
                $query->where('is_featured', true);
            }

            // Clustering optimization for map display
            $zoom = $options['zoom_level'] ?? 12;
            if ($zoom < 10) {
                // Low zoom: prioritize featured/verified places
                $query->orderByDesc('is_featured')
                      ->orderByDesc('is_verified')
                      ->orderByDesc('view_count')
                      ->limit(100);
            } else {
                // High zoom: show all places with spatial ordering
                if ($region->center_point) {
                    $query->orderBy(DB::raw('ST_Distance(ST_MakePoint(longitude, latitude), ?)'), [$region->center_point])
                          ->limit($options['limit'] ?? 500);
                } else {
                    $query->limit($options['limit'] ?? 500);
                }
            }

            $places = $query->get();

            return [
                'places' => $places,
                'total_count' => $region->cached_place_count,
                'bounds' => $this->getRegionBounds($region),
                'center' => $this->getRegionCenter($region),
                'clustering_enabled' => $zoom < 12,
                'region_info' => [
                    'id' => $region->id,
                    'name' => $region->name,
                    'type' => $region->type,
                    'is_park' => in_array($region->type, ['national_park', 'state_park', 'regional_park', 'local_park'])
                ]
            ];
        });
    }

    /**
     * Optimized nearby places query with region context
     */
    public function getNearbyPlacesWithRegionContext(float $lat, float $lng, array $options = []): array
    {
        $radius = $options['radius'] ?? 25; // miles
        $limit = $options['limit'] ?? 50;

        // Find containing regions first (uses spatial index)
        $containingRegions = Region::containingPoint($lat, $lng)
            ->select(['id', 'name', 'type', 'level', 'slug'])
            ->orderBy('level', 'desc') // Most specific first
            ->limit(3)
            ->get();

        // Use direct table for performance
        $places = DB::table('directory_entries as de')
            ->select([
                'de.*',
                DB::raw('ST_Distance(ST_MakePoint(longitude, latitude)::geography, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography) / 1609.34 as distance_miles')
            ])
            ->whereRaw('ST_DWithin(ST_MakePoint(longitude, latitude)::geography, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)', 
                       [$lng, $lat, $lng, $lat, $radius * 1609.34]) // Convert miles to meters
            ->orderBy('distance_miles')
            ->limit($limit)
            ->get();

        return [
            'places' => $places,
            'search_center' => ['lat' => $lat, 'lng' => $lng],
            'radius_miles' => $radius,
            'containing_regions' => $containingRegions,
            'total_found' => $places->count()
        ];
    }

    /**
     * Mapbox-optimized clustering query
     * Returns data optimized for map viewport with clustering
     */
    public function getPlacesForMapViewport(array $bounds, array $options = []): array
    {
        [$north, $south, $east, $west] = $bounds;
        $zoom = $options['zoom_level'] ?? 12;
        $limit = $this->getViewportLimit($zoom);

        // Use specialized clustering index
        $placesQuery = DB::table('directory_entries')
            ->select([
                'id', 'title', 'slug', 'latitude', 'longitude',
                'category_id', 'is_featured', 'is_verified'
            ])
            ->where('status', 'published')
            ->whereNotNull('latitude')
            ->whereBetween('latitude', [$south, $north])
            ->whereBetween('longitude', [$west, $east]);

        if ($zoom < 10) {
            // Low zoom: cluster and prioritize
            $places = $placesQuery
                ->where('is_featured', true)
                ->orderByDesc('view_count')
                ->limit($limit)
                ->get();
        } else {
            // High zoom: show all places in viewport
            $places = $placesQuery->limit($limit)->get();
        }

        // Get regions in viewport for context
        $regions = Region::intersectingBounds([$west, $south, $east, $north])
            ->whereIn('type', ['state', 'city', 'national_park', 'state_park'])
            ->select(['id', 'name', 'type', 'level', 'slug'])
            ->limit(20)
            ->get();

        return [
            'places' => $places,
            'regions' => $regions->map(function ($region) {
                return [
                    'id' => $region->id,
                    'name' => $region->name,
                    'type' => $region->type,
                    'level' => $region->level,
                    'slug' => $region->slug
                ];
            }),
            'bounds' => ['north' => $north, 'south' => $south, 'east' => $east, 'west' => $west],
            'zoom_level' => $zoom,
            'clustering_recommended' => $zoom < 12,
            'total_in_viewport' => $places->count()
        ];
    }

    /**
     * Two-column layout optimized query
     * Left: List of places, Right: Map with markers
     */
    public function getPlacesForTwoColumnLayout(Region $region, array $options = []): array
    {
        $page = $options['page'] ?? 1;
        $perPage = $options['per_page'] ?? 20;
        $offset = ($page - 1) * $perPage;

        // **LIST COLUMN**: Paginated places with full details
        $listQuery = Place::where('status', 'published');
        
        // Apply region filter based on level
        $this->addRegionHierarchyFilter($listQuery, $region);
        
        $listPlaces = $listQuery
            ->with(['category:id,name,slug,color'])
            ->select([
                'id', 'title', 'slug', 'description', 'featured_image',
                'latitude', 'longitude', 'category_id', 'is_featured', 'is_verified'
            ])
            ->orderByDesc('is_featured')
            ->orderByDesc('view_count')
            ->offset($offset)
            ->limit($perPage)
            ->get();

        // **MAP COLUMN**: All places in region for markers (lightweight)
        $mapPlaces = Cache::remember("region_{$region->id}_map_markers", 60 * 30, function () use ($region) {
            $mapQuery = Place::where('status', 'published');
            $this->addRegionHierarchyFilter($mapQuery, $region);
            return $mapQuery->select(['id', 'title', 'latitude', 'longitude', 'category_id', 'is_featured'])
                ->get();
        });

        $totalCount = $region->cached_place_count ?? $listPlaces->count();

        return [
            'list_places' => $listPlaces,
            'map_places' => $mapPlaces,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $totalCount,
                'last_page' => ceil($totalCount / $perPage),
                'has_more' => ($page * $perPage) < $totalCount
            ],
            'region' => $region->only(['id', 'name', 'type', 'slug']),
            'bounds' => $this->getRegionBounds($region)
        ];
    }

    /**
     * Add region hierarchy filter to query
     */
    private function addRegionHierarchyFilter($query, Region $region)
    {
        switch ($region->level) {
            case 1: // State
                return $query->where('state_region_id', $region->id);
            case 2: // City
                return $query->where('city_region_id', $region->id);
            case 3: // Neighborhood
                return $query->where('neighborhood_region_id', $region->id);
            default:
                return $query->where('region_id', $region->id);
        }
    }

    /**
     * Add spatial containment filter for parks
     */
    private function addSpatialContainmentFilter($query, Region $region)
    {
        if (!$region->boundary) {
            return $query->where('id', -1); // No results if no boundary
        }

        return $query->whereRaw(
            'ST_Contains(?, ST_MakePoint(longitude, latitude))',
            [$region->boundary]
        );
    }

    /**
     * Get region bounds for map display
     */
    private function getRegionBounds(Region $region): ?array
    {
        if ($region->boundary) {
            return $region->getBoundsArray();
        }

        // Fallback: calculate from places
        $bounds = DB::table('directory_entries')
            ->selectRaw('
                MIN(latitude) as min_lat,
                MAX(latitude) as max_lat,
                MIN(longitude) as min_lng,
                MAX(longitude) as max_lng
            ')
            ->where('state_region_id', $region->id)
            ->whereNotNull('latitude')
            ->first();

        return $bounds ? [
            'min_lat' => $bounds->min_lat,
            'max_lat' => $bounds->max_lat,
            'min_lng' => $bounds->min_lng,
            'max_lng' => $bounds->max_lng
        ] : null;
    }

    /**
     * Get region center point
     */
    private function getRegionCenter(Region $region): ?array
    {
        if ($region->center_point) {
            $center = DB::selectOne('SELECT ST_X(center_point) as lng, ST_Y(center_point) as lat FROM regions WHERE id = ?', [$region->id]);
            return ['lat' => $center->lat, 'lng' => $center->lng];
        }

        return null;
    }

    /**
     * Determine appropriate limit based on zoom level
     */
    private function getViewportLimit(int $zoom): int
    {
        return match (true) {
            $zoom <= 8 => 50,   // Country/state level
            $zoom <= 10 => 100, // State/large city level
            $zoom <= 12 => 250, // City level
            $zoom <= 14 => 500, // Neighborhood level
            default => 1000     // Street level
        };
    }
}