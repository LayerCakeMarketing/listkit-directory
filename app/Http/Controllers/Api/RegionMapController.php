<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Models\Place;
use App\Services\RegionPlaceQueryService;
use App\Services\RegionCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RegionMapController extends Controller
{
    protected RegionPlaceQueryService $queryService;
    protected RegionCacheService $cacheService;

    public function __construct(
        RegionPlaceQueryService $queryService,
        RegionCacheService $cacheService
    ) {
        $this->queryService = $queryService;
        $this->cacheService = $cacheService;
    }

    /**
     * Get region data for two-column layout (list + map)
     */
    public function getTwoColumnLayout($regionId, Request $request)
    {
        $request->validate([
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:10|max:50',
            'category_id' => 'integer|exists:categories,id',
            'search' => 'string|max:100',
            'sort' => 'in:name,rating,distance,created_at',
            'bounds' => 'array',
            'bounds.*' => 'numeric',
        ]);

        $region = Region::with(['parent', 'children'])
            ->findOrFail($regionId);

        // Check if this is a park region
        $isPark = in_array($region->park_designation, ['state_park', 'national_park']);

        // Get places for list view
        $placesData = $this->queryService->getPlacesForTwoColumnLayout($region, [
            'page' => $request->input('page', 1),
            'per_page' => $request->input('per_page', 20),
            'category_id' => $request->input('category_id'),
            'search' => $request->input('search'),
            'sort' => $request->input('sort', 'name'),
            'use_boundaries' => $isPark, // Use spatial boundary for parks
        ]);

        // Get map data (all places for initial load, with clustering info)
        $mapData = $this->getMapDataForRegion($region, $request);

        // Get region metadata
        $regionData = $this->getRegionMetadata($region);

        return response()->json([
            'region' => $regionData,
            'places' => $placesData['list_places'] ?? [],
            'map_places' => $placesData['map_places'] ?? [],
            'pagination' => $placesData['pagination'] ?? [],
            'map' => $mapData,
            'filters' => $this->getAvailableFilters($region),
        ]);
    }

    /**
     * Get places for map viewport (with clustering)
     */
    public function getMapViewportPlaces(Request $request)
    {
        $request->validate([
            'bounds' => 'required|array|size:4',
            'bounds.*' => 'numeric',
            'zoom_level' => 'required|integer|min:1|max:20',
            'region_id' => 'integer|exists:regions,id',
            'category_id' => 'integer|exists:categories,id',
            'cluster' => 'boolean',
        ]);

        $bounds = $request->input('bounds'); // [north, south, east, west]
        $zoomLevel = $request->input('zoom_level');
        $shouldCluster = $request->input('cluster', true);

        // If region_id provided, ensure viewport is within region
        $regionId = $request->input('region_id');
        if ($regionId) {
            $region = Region::find($regionId);
            if ($region && $region->boundary) {
                // Validate viewport intersects with region
                $intersects = DB::selectOne("
                    SELECT ST_Intersects(
                        boundary,
                        ST_MakeEnvelope(?, ?, ?, ?, 4326)
                    ) as intersects
                    FROM regions WHERE id = ?
                ", [$bounds[3], $bounds[1], $bounds[2], $bounds[0], $regionId]);

                if (!$intersects->intersects) {
                    return response()->json([
                        'places' => [],
                        'clusters' => [],
                        'total' => 0,
                    ]);
                }
            }
        }

        $mapData = $this->queryService->getPlacesForMapViewport($bounds, [
            'zoom_level' => $zoomLevel,
            'region_id' => $regionId,
            'category_id' => $request->input('category_id'),
            'cluster' => $shouldCluster,
        ]);

        return response()->json($mapData);
    }

    /**
     * Get specific region details for parks and neighborhoods
     */
    public function getRegionDetails($regionId)
    {
        $region = Region::with([
            'parent.parent',
            'children',
            'featuredEntries' => function ($q) {
                $q->limit(6);
            },
            'featuredLists' => function ($q) {
                $q->limit(4);
            }
        ])->findOrFail($regionId);

        // Get park-specific information if applicable
        $parkInfo = null;
        if ($region->park_designation) {
            $parkInfo = $this->getParkInformation($region);
        }

        // Get region statistics
        $stats = $this->cacheService->getRegionStatistics($region->id);

        // Get boundary GeoJSON for map rendering
        $geoJson = null;
        if ($region->boundary) {
            $geoJson = $region->getGeoJson(true); // Use simplified boundary
        }

        return response()->json([
            'region' => $region->toFrontend(),
            'park_info' => $parkInfo,
            'statistics' => $stats,
            'geojson' => $geoJson,
            'breadcrumbs' => $this->getBreadcrumbs($region),
        ]);
    }

    /**
     * Get clustered places for a region
     */
    public function getClusteredPlaces($regionId, Request $request)
    {
        $request->validate([
            'zoom_level' => 'required|integer|min:1|max:20',
            'bounds' => 'array|size:4',
            'bounds.*' => 'numeric',
        ]);

        $region = Region::findOrFail($regionId);
        $zoomLevel = $request->input('zoom_level');

        // Get pre-computed clusters from cache or database
        $cacheKey = "region_{$regionId}_clusters_z{$zoomLevel}";
        
        $clusters = Cache::remember($cacheKey, 3600, function () use ($region, $zoomLevel, $request) {
            return $this->computeClusters($region, $zoomLevel, $request->input('bounds'));
        });

        return response()->json([
            'clusters' => $clusters,
            'zoom_level' => $zoomLevel,
        ]);
    }

    /**
     * Get nearby regions (for parks and neighborhoods)
     */
    public function getNearbyRegions($regionId)
    {
        $region = Region::findOrFail($regionId);
        
        $nearbyRegions = [];

        if ($region->center_point) {
            // Get nearby regions within 50km
            $nearbyRegions = Region::where('id', '!=', $regionId)
                ->where('level', $region->level) // Same level only
                ->whereRaw("
                    ST_DWithin(
                        center_point::geography,
                        (SELECT center_point::geography FROM regions WHERE id = ?),
                        50000
                    )
                ", [$regionId])
                ->withCount('places')
                ->orderByRaw("
                    ST_Distance(
                        center_point::geography,
                        (SELECT center_point::geography FROM regions WHERE id = ?)
                    )
                ", [$regionId])
                ->limit(10)
                ->get();
        }

        return response()->json([
            'data' => $nearbyRegions,
        ]);
    }

    /**
     * Helper: Get map data for a region
     */
    protected function getMapDataForRegion(Region $region, Request $request)
    {
        $isPark = in_array($region->park_designation, ['state_park', 'national_park']);
        
        // Get initial viewport bounds
        $bounds = null;
        if ($region->boundary) {
            $bounds = $region->getBoundsArray();
        }

        // Get places for initial map load (limited set)
        $mapPlaces = [];
        if ($isPark && $region->boundary) {
            // For parks, get places within boundaries
            $mapPlaces = Place::select('id', 'title', 'slug', 'latitude', 'longitude', 'category_id', 'featured_image')
                ->whereRaw("ST_Contains(?, ST_MakePoint(longitude, latitude))", [$region->boundary])
                ->where('status', 'published')
                ->limit(500)
                ->get();
        } else {
            // For neighborhoods/cities, use hierarchical relationship
            $mapPlaces = $region->places()
                ->select('id', 'title', 'slug', 'latitude', 'longitude', 'category_id', 'featured_image')
                ->where('status', 'published')
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->limit(500)
                ->get();
        }

        return [
            'bounds' => $bounds,
            'center' => $region->center_point ? [
                'lat' => DB::selectOne("SELECT ST_Y(center_point) as lat FROM regions WHERE id = ?", [$region->id])->lat ?? null,
                'lng' => DB::selectOne("SELECT ST_X(center_point) as lng FROM regions WHERE id = ?", [$region->id])->lng ?? null,
            ] : null,
            'places' => $mapPlaces,
            'total_places' => $region->cached_place_count,
            'cluster_enabled' => $region->cached_place_count > 50,
            'boundary_geojson' => $region->getGeoJson(true),
        ];
    }

    /**
     * Helper: Get region metadata
     */
    protected function getRegionMetadata(Region $region)
    {
        $data = $region->toFrontend();
        
        // Add park-specific data
        if ($region->park_designation) {
            $data['park_info'] = [
                'designation' => $region->park_designation,
                'area_acres' => $region->area_acres,
                'established_date' => $region->established_date,
                'features' => $region->park_features,
                'activities' => $region->park_activities,
                'website' => $region->park_website,
                'phone' => $region->park_phone,
                'hours' => $region->operating_hours,
                'fees' => $region->entrance_fees,
                'reservations_required' => $region->reservations_required,
                'difficulty_level' => $region->difficulty_level,
                'accessibility' => $region->accessibility_features,
            ];
        }

        return $data;
    }

    /**
     * Helper: Get park information
     */
    protected function getParkInformation(Region $region)
    {
        return [
            'system' => $region->park_system,
            'designation' => $region->park_designation,
            'area' => [
                'acres' => $region->area_acres,
                'sq_km' => $region->area_sq_km,
            ],
            'established' => $region->established_date,
            'features' => $region->park_features ?? [],
            'activities' => $region->park_activities ?? [],
            'contact' => [
                'website' => $region->park_website,
                'phone' => $region->park_phone,
            ],
            'visiting' => [
                'hours' => $region->operating_hours,
                'fees' => $region->entrance_fees,
                'reservations' => $region->reservations_required,
                'difficulty' => $region->difficulty_level,
                'accessibility' => $region->accessibility_features,
            ],
        ];
    }

    /**
     * Helper: Get available filters for region
     */
    protected function getAvailableFilters(Region $region)
    {
        $cacheKey = "region_{$region->id}_filters";
        
        return Cache::remember($cacheKey, 1800, function () use ($region) {
            $categories = DB::table('directory_entries')
                ->select('categories.id', 'categories.name', 'categories.slug', DB::raw('count(*) as count'))
                ->join('categories', 'categories.id', '=', 'directory_entries.category_id')
                ->where('directory_entries.region_id', $region->id)
                ->where('directory_entries.status', 'published')
                ->groupBy('categories.id', 'categories.name', 'categories.slug')
                ->orderBy('count', 'desc')
                ->get();

            return [
                'categories' => $categories,
                'total_places' => $region->cached_place_count,
            ];
        });
    }

    /**
     * Helper: Get breadcrumbs for region
     */
    protected function getBreadcrumbs(Region $region)
    {
        $breadcrumbs = [];
        $current = $region;
        
        while ($current) {
            array_unshift($breadcrumbs, [
                'id' => $current->id,
                'name' => $current->name,
                'slug' => $current->slug,
                'url' => '/regions/' . $current->getUrlPath(),
            ]);
            $current = $current->parent;
        }

        return $breadcrumbs;
    }

    /**
     * Helper: Compute clusters for a region at specific zoom level
     */
    protected function computeClusters(Region $region, int $zoomLevel, ?array $bounds = null)
    {
        // Clustering parameters based on zoom level
        $clusterRadius = match (true) {
            $zoomLevel <= 8 => 60,
            $zoomLevel <= 12 => 40,
            $zoomLevel <= 15 => 20,
            default => 10,
        };

        $query = Place::select('id', 'title', 'latitude', 'longitude', 'category_id')
            ->where('region_id', $region->id)
            ->where('status', 'published')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        if ($bounds) {
            $query->whereBetween('latitude', [$bounds[1], $bounds[0]])
                  ->whereBetween('longitude', [$bounds[3], $bounds[2]]);
        }

        $places = $query->get();

        // Simple clustering algorithm (can be optimized with PostGIS clustering)
        $clusters = [];
        $processedPlaces = [];

        foreach ($places as $place) {
            if (in_array($place->id, $processedPlaces)) {
                continue;
            }

            $cluster = [
                'lat' => $place->latitude,
                'lng' => $place->longitude,
                'count' => 1,
                'place_ids' => [$place->id],
            ];

            // Find nearby places within cluster radius
            foreach ($places as $otherPlace) {
                if ($place->id === $otherPlace->id || in_array($otherPlace->id, $processedPlaces)) {
                    continue;
                }

                $distance = $this->calculateDistance(
                    $place->latitude, $place->longitude,
                    $otherPlace->latitude, $otherPlace->longitude
                );

                if ($distance <= $clusterRadius) {
                    $cluster['count']++;
                    $cluster['place_ids'][] = $otherPlace->id;
                    $processedPlaces[] = $otherPlace->id;
                }
            }

            if ($cluster['count'] > 1) {
                // Calculate cluster center
                $cluster['is_cluster'] = true;
                $clusters[] = $cluster;
            } else {
                // Single place
                $clusters[] = [
                    'is_cluster' => false,
                    'place' => $place,
                ];
            }

            $processedPlaces[] = $place->id;
        }

        return $clusters;
    }

    /**
     * Helper: Calculate distance between two points (in pixels at zoom level)
     */
    protected function calculateDistance($lat1, $lng1, $lat2, $lng2)
    {
        // Simplified pixel distance calculation
        $latDiff = abs($lat1 - $lat2);
        $lngDiff = abs($lng1 - $lng2);
        return sqrt(($latDiff * $latDiff) + ($lngDiff * $lngDiff)) * 111; // Rough km conversion
    }
}