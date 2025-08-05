<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Place;
use App\Services\GeocodingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GeospatialController extends Controller
{
    private GeocodingService $geocodingService;

    public function __construct(GeocodingService $geocodingService)
    {
        $this->geocodingService = $geocodingService;
    }

    /**
     * Find places within radius of coordinates
     * GET /api/geospatial/nearby?lat=40.7128&lng=-74.0060&radius=5&limit=20
     */
    public function nearby(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius' => 'sometimes|numeric|min:0.1|max:100',
            'limit' => 'sometimes|integer|min:1|max:100',
            'category_id' => 'sometimes|exists:categories,id',
            'featured_only' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $latitude = $request->get('lat');
        $longitude = $request->get('lng');
        $radius = $request->get('radius', 25); // Default 25 miles
        $limit = $request->get('limit', 20);

        $query = Place::published()
            ->withinRadius($latitude, $longitude, $radius)
            ->orderByDistance($latitude, $longitude);

        if ($request->has('category_id')) {
            $query->where('category_id', $request->get('category_id'));
        }

        if ($request->boolean('featured_only')) {
            $query->featured();
        }

        $places = $query->with(['category:id,name,slug', 'stateRegion:id,name,slug', 'cityRegion:id,name,slug'])
            ->limit($limit)
            ->get();

        return response()->json([
            'places' => $places,
            'search_center' => [
                'lat' => $latitude,
                'lng' => $longitude
            ],
            'radius_miles' => $radius,
            'total_found' => $places->count()
        ]);
    }

    /**
     * Find places within a bounding box (for map viewport searches)
     * GET /api/geospatial/in-bounds?north=40.8&south=40.6&east=-73.8&west=-74.2
     */
    public function inBounds(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'north' => 'required|numeric|between:-90,90',
            'south' => 'required|numeric|between:-90,90',
            'east' => 'required|numeric|between:-180,180',
            'west' => 'required|numeric|between:-180,180',
            'limit' => 'sometimes|integer|min:1|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $north = $request->get('north');
        $south = $request->get('south');
        $east = $request->get('east');
        $west = $request->get('west');
        $limit = $request->get('limit', 100);

        // Validate bounds
        if ($north <= $south || $east <= $west) {
            return response()->json(['error' => 'Invalid bounding box coordinates'], 422);
        }

        $places = Place::published()
            ->withinBoundingBox($north, $south, $east, $west)
            ->with(['category:id,name,slug,color'])
            ->select(['id', 'title', 'slug', 'latitude', 'longitude', 'category_id', 'is_featured', 'is_verified'])
            ->limit($limit)
            ->get();

        return response()->json([
            'places' => $places,
            'bounds' => [
                'north' => $north,
                'south' => $south,
                'east' => $east,
                'west' => $west
            ],
            'total_found' => $places->count()
        ]);
    }

    /**
     * Get detailed place info with nearby places
     * GET /api/geospatial/place/{id}/details
     */
    public function placeDetails(Request $request, Place $place)
    {
        if (!$place->hasValidCoordinates()) {
            return response()->json(['error' => 'Place has no valid coordinates'], 404);
        }

        $nearbyRadius = $request->get('nearby_radius', 5);
        $nearbyLimit = $request->get('nearby_limit', 10);

        $nearby = $place->getNearbyPlaces($nearbyRadius, $nearbyLimit);

        return response()->json([
            'place' => $place->load(['category', 'stateRegion', 'cityRegion', 'location']),
            'coordinates' => $place->coordinates,
            'nearby_places' => $nearby,
            'nearby_count' => $nearby->count()
        ]);
    }

    /**
     * Calculate distance between two places
     * GET /api/geospatial/distance?from_id=1&to_id=2
     * GET /api/geospatial/distance?from_id=1&to_lat=40.7128&to_lng=-74.0060
     */
    public function distance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_id' => 'required|exists:directory_entries,id',
            'to_id' => 'sometimes|exists:directory_entries,id',
            'to_lat' => 'sometimes|numeric|between:-90,90',
            'to_lng' => 'sometimes|numeric|between:-180,180',
            'unit' => 'sometimes|in:miles,kilometers,meters'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $fromPlace = Place::findOrFail($request->get('from_id'));
        $unit = $request->get('unit', 'miles');

        if ($request->has('to_id')) {
            $toPlace = Place::findOrFail($request->get('to_id'));
            $distance = $fromPlace->distanceTo($toPlace, $unit);
            $destination = [
                'type' => 'place',
                'id' => $toPlace->id,
                'name' => $toPlace->title,
                'coordinates' => $toPlace->coordinates
            ];
        } elseif ($request->has('to_lat') && $request->has('to_lng')) {
            $toLat = $request->get('to_lat');
            $toLng = $request->get('to_lng');
            $distance = $fromPlace->distanceTo(['lat' => $toLat, 'lng' => $toLng], $unit);
            $destination = [
                'type' => 'coordinates',
                'coordinates' => ['lat' => $toLat, 'lng' => $toLng]
            ];
        } else {
            return response()->json(['error' => 'Must provide either to_id or to_lat/to_lng'], 422);
        }

        return response()->json([
            'from' => [
                'id' => $fromPlace->id,
                'name' => $fromPlace->title,
                'coordinates' => $fromPlace->coordinates
            ],
            'to' => $destination,
            'distance' => $distance,
            'unit' => $unit
        ]);
    }

    /**
     * Geocode an address
     * POST /api/geospatial/geocode
     */
    public function geocode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'address' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $address = $request->get('address');
        $result = $this->geocodingService->geocode($address);

        if (!$result) {
            return response()->json(['error' => 'Address could not be geocoded'], 404);
        }

        return response()->json([
            'address' => $address,
            'result' => $result
        ]);
    }

    /**
     * Reverse geocode coordinates
     * POST /api/geospatial/reverse-geocode
     */
    public function reverseGeocode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $latitude = $request->get('lat');
        $longitude = $request->get('lng');
        
        $result = $this->geocodingService->reverseGeocode($latitude, $longitude);

        if (!$result) {
            return response()->json(['error' => 'Coordinates could not be reverse geocoded'], 404);
        }

        return response()->json([
            'coordinates' => ['lat' => $latitude, 'lng' => $longitude],
            'result' => $result
        ]);
    }

    /**
     * Get geospatial statistics
     * GET /api/geospatial/stats
     */
    public function stats()
    {
        $stats = $this->geocodingService->getGeocodingStats();
        
        // Add database-specific stats
        $additionalStats = [
            'places_by_region' => DB::table('directory_entries')
                ->select('state_region_id', DB::raw('count(*) as count'))
                ->whereNotNull('state_region_id')
                ->where('status', 'published')
                ->groupBy('state_region_id')
                ->orderByDesc('count')
                ->limit(10)
                ->get(),
            
            'geocoding_providers' => DB::table('locations')
                ->select('geocoding_provider', DB::raw('count(*) as count'))
                ->whereNotNull('geocoding_provider')
                ->groupBy('geocoding_provider')
                ->get(),
            
            'location_sources' => DB::table('locations')
                ->select('location_source', DB::raw('count(*) as count'))
                ->whereNotNull('location_source')
                ->groupBy('location_source')
                ->get()
        ];

        return response()->json(array_merge($stats, $additionalStats));
    }

    /**
     * Batch geocode places that need it
     * POST /api/geospatial/batch-geocode
     */
    public function batchGeocode(Request $request)
    {
        $limit = $request->get('limit', 50);
        $processed = $this->geocodingService->batchGeocodePlaces($limit);

        return response()->json([
            'message' => 'Batch geocoding completed',
            'processed' => $processed,
            'limit' => $limit
        ]);
    }

    /**
     * Get places that need geocoding
     * GET /api/geospatial/needs-geocoding
     */
    public function needsGeocoding(Request $request)
    {
        $limit = $request->get('limit', 50);
        
        $places = Place::needsGeocoding()
            ->with(['stateRegion:id,name', 'cityRegion:id,name'])
            ->select(['id', 'title', 'address_line1', 'city', 'state', 'state_region_id', 'city_region_id', 'location_updated_at', 'location_verified'])
            ->limit($limit)
            ->get();

        return response()->json([
            'places' => $places,
            'total_needing_geocoding' => Place::needsGeocoding()->count()
        ]);
    }
}