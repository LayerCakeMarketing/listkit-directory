<?php

namespace App\Services;

use App\Models\Place;
use App\Models\Region;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlaceRegionService
{
    /**
     * Associate a place with all relevant regions based on its location
     */
    public function associatePlaceWithRegions(Place $place)
    {
        if (!$place->location) {
            return false;
        }

        $location = $place->location;
        
        // Start a database transaction
        DB::transaction(function() use ($place, $location) {
            // Remove existing associations
            DB::table('place_regions')->where('place_id', $place->id)->delete();
            
            // Get all regions that should be associated
            $regions = $this->findRegionsForLocation($location);
            
            // Create associations
            foreach ($regions as $region) {
                DB::table('place_regions')->insert([
                    'place_id' => $place->id,
                    'region_id' => $region->id,
                    'association_type' => 'contained',
                    'region_type' => $region->type,
                    'region_level' => $region->level,
                    'confidence_score' => 1.0,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
            
            // Update denormalized region IDs on the place
            $this->updatePlaceRegionIds($place, $regions);
            
            // Update region cache counts
            $this->updateRegionCounts($regions->pluck('id')->toArray());
        });
        
        return true;
    }
    
    /**
     * Find all regions that contain or are near a location
     */
    public function findRegionsForLocation($location)
    {
        $regions = collect();
        
        // First, try to find by exact matching (state, city, neighborhood)
        if ($location->state) {
            $stateRegion = Region::where('type', 'state')
                ->where('level', 1)
                ->where(function($q) use ($location) {
                    $q->where('name', $location->state)
                      ->orWhere('abbreviation', strtoupper($location->state));
                })
                ->first();
                
            // If state doesn't exist, create it
            if (!$stateRegion && $location->state) {
                $fullStateName = $this->getFullStateName($location->state);
                $stateSlug = \Str::slug($fullStateName);
                $stateRegion = Region::create([
                    'name' => $fullStateName,
                    'full_name' => $fullStateName,
                    'abbreviation' => strlen($location->state) == 2 ? strtoupper($location->state) : $this->getStateAbbreviation($fullStateName),
                    'slug' => $stateSlug,
                    'type' => 'state',
                    'level' => 1
                ]);
            }
                
            if ($stateRegion) {
                $regions->push($stateRegion);
                
                // Look for city within the state
                if ($location->city) {
                    $cityRegion = Region::where('type', 'city')
                        ->where('level', 2)
                        ->where('parent_id', $stateRegion->id)
                        ->where('name', $location->city)
                        ->first();
                        
                    // If city doesn't exist, create it
                    if (!$cityRegion) {
                        $citySlug = \Str::slug($location->city);
                        $cityRegion = Region::create([
                            'name' => $location->city,
                            'full_name' => $location->city,
                            'slug' => $citySlug,
                            'type' => 'city',
                            'level' => 2,
                            'parent_id' => $stateRegion->id
                        ]);
                    }
                        
                    if ($cityRegion) {
                        $regions->push($cityRegion);
                        
                        // Look for neighborhood within the city
                        if ($location->neighborhood) {
                            $neighborhoodRegion = Region::where('type', 'neighborhood')
                                ->where('level', 3)
                                ->where('parent_id', $cityRegion->id)
                                ->where('name', $location->neighborhood)
                                ->first();
                                
                            // If neighborhood doesn't exist, create it
                            if (!$neighborhoodRegion) {
                                $neighborhoodSlug = \Str::slug($location->neighborhood);
                                $neighborhoodRegion = Region::create([
                                    'name' => $location->neighborhood,
                                    'full_name' => $location->neighborhood,
                                    'slug' => $neighborhoodSlug,
                                    'type' => 'neighborhood',
                                    'level' => 3,
                                    'parent_id' => $cityRegion->id
                                ]);
                            }
                                
                            if ($neighborhoodRegion) {
                                $regions->push($neighborhoodRegion);
                            }
                        }
                    }
                }
            }
        }
        
        // If we have coordinates and PostGIS is available, find containing regions spatially
        if ($location->latitude && $location->longitude && config('database.default') === 'pgsql') {
            $spatialRegions = $this->findRegionsSpatially($location->latitude, $location->longitude);
            $regions = $regions->merge($spatialRegions);
        }
        
        return $regions->unique('id');
    }
    
    /**
     * Find regions spatially using PostGIS
     */
    private function findRegionsSpatially($latitude, $longitude)
    {
        try {
            $regions = DB::select("
                SELECT id, type, level, name
                FROM regions
                WHERE boundary IS NOT NULL
                AND ST_Contains(boundary, ST_SetSRID(ST_MakePoint(?, ?), 4326))
                ORDER BY level DESC
            ", [$longitude, $latitude]);
            
            return collect($regions)->map(function($r) {
                return Region::find($r->id);
            });
        } catch (\Exception $e) {
            Log::warning('Spatial region lookup failed', [
                'error' => $e->getMessage(),
                'lat' => $latitude,
                'lng' => $longitude
            ]);
            return collect();
        }
    }
    
    /**
     * Update denormalized region IDs on the place
     */
    private function updatePlaceRegionIds(Place $place, $regions)
    {
        $updates = [
            'state_region_id' => null,
            'city_region_id' => null,
            'neighborhood_region_id' => null,
            'regions_updated_at' => now()
        ];
        
        foreach ($regions as $region) {
            switch ($region->level) {
                case 1: // State
                    $updates['state_region_id'] = $region->id;
                    break;
                case 2: // City
                    $updates['city_region_id'] = $region->id;
                    break;
                case 3: // Neighborhood
                    $updates['neighborhood_region_id'] = $region->id;
                    break;
            }
        }
        
        $place->update($updates);
    }
    
    /**
     * Update cached place counts for regions
     */
    private function updateRegionCounts($regionIds)
    {
        foreach ($regionIds as $regionId) {
            $count = DB::table('place_regions')
                ->where('region_id', $regionId)
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('directory_entries')
                        ->whereRaw('directory_entries.id = place_regions.place_id')
                        ->where('status', 'published');
                })
                ->count();
                
            Region::where('id', $regionId)->update([
                'cached_place_count' => $count,
                'cache_updated_at' => now()
            ]);
        }
    }
    
    /**
     * Find places within a custom polygon
     */
    public function findPlacesInPolygon($geoJson)
    {
        if (config('database.default') !== 'pgsql') {
            throw new \Exception('Spatial queries require PostgreSQL with PostGIS');
        }
        
        return DB::select("
            SELECT p.*, ST_Distance(p.coordinates, ST_Centroid(?::geometry)) as distance
            FROM directory_entries p
            WHERE p.coordinates IS NOT NULL
            AND ST_Contains(?::geometry, p.coordinates)
            AND p.status = 'published'
            ORDER BY distance
            LIMIT 100
        ", [$geoJson, $geoJson]);
    }
    
    /**
     * Find nearby places within a radius
     */
    public function findNearbyPlaces($latitude, $longitude, $radiusMeters = 5000)
    {
        if (config('database.default') !== 'pgsql') {
            // Fallback to simple distance calculation
            return Place::published()
                ->whereHas('location')
                ->get()
                ->filter(function($place) use ($latitude, $longitude, $radiusMeters) {
                    $distance = $this->calculateDistance(
                        $latitude, 
                        $longitude, 
                        $place->location->latitude, 
                        $place->location->longitude
                    );
                    return $distance <= $radiusMeters;
                })
                ->sortBy(function($place) use ($latitude, $longitude) {
                    return $this->calculateDistance(
                        $latitude, 
                        $longitude, 
                        $place->location->latitude, 
                        $place->location->longitude
                    );
                })
                ->take(50);
        }
        
        // Use PostGIS for accurate spatial queries
        $results = DB::select("
            SELECT p.*, l.*, 
                   ST_Distance(
                       ST_SetSRID(ST_MakePoint(l.longitude, l.latitude), 4326)::geography,
                       ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography
                   ) as distance
            FROM directory_entries p
            JOIN locations l ON p.id = l.directory_entry_id
            WHERE ST_DWithin(
                ST_SetSRID(ST_MakePoint(l.longitude, l.latitude), 4326)::geography,
                ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography,
                ?
            )
            AND p.status = 'published'
            ORDER BY distance
            LIMIT 50
        ", [$longitude, $latitude, $longitude, $latitude, $radiusMeters]);
        
        return collect($results)->map(function($result) {
            return Place::hydrate([$result])->first();
        });
    }
    
    /**
     * Calculate distance between two coordinates (in meters)
     * Using Haversine formula
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // meters
        
        $latDiff = deg2rad($lat2 - $lat1);
        $lonDiff = deg2rad($lon2 - $lon1);
        
        $a = sin($latDiff / 2) * sin($latDiff / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDiff / 2) * sin($lonDiff / 2);
             
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }
    
    /**
     * Get state abbreviation from full name
     */
    private function getStateAbbreviation($fullName)
    {
        $stateAbbreviations = [
            'alabama' => 'AL',
            'alaska' => 'AK',
            'arizona' => 'AZ',
            'arkansas' => 'AR',
            'california' => 'CA',
            'colorado' => 'CO',
            'connecticut' => 'CT',
            'delaware' => 'DE',
            'florida' => 'FL',
            'georgia' => 'GA',
            'hawaii' => 'HI',
            'idaho' => 'ID',
            'illinois' => 'IL',
            'indiana' => 'IN',
            'iowa' => 'IA',
            'kansas' => 'KS',
            'kentucky' => 'KY',
            'louisiana' => 'LA',
            'maine' => 'ME',
            'maryland' => 'MD',
            'massachusetts' => 'MA',
            'michigan' => 'MI',
            'minnesota' => 'MN',
            'mississippi' => 'MS',
            'missouri' => 'MO',
            'montana' => 'MT',
            'nebraska' => 'NE',
            'nevada' => 'NV',
            'new hampshire' => 'NH',
            'new jersey' => 'NJ',
            'new mexico' => 'NM',
            'new york' => 'NY',
            'north carolina' => 'NC',
            'north dakota' => 'ND',
            'ohio' => 'OH',
            'oklahoma' => 'OK',
            'oregon' => 'OR',
            'pennsylvania' => 'PA',
            'rhode island' => 'RI',
            'south carolina' => 'SC',
            'south dakota' => 'SD',
            'tennessee' => 'TN',
            'texas' => 'TX',
            'utah' => 'UT',
            'vermont' => 'VT',
            'virginia' => 'VA',
            'washington' => 'WA',
            'west virginia' => 'WV',
            'wisconsin' => 'WI',
            'wyoming' => 'WY',
            'district of columbia' => 'DC'
        ];
        
        $normalized = strtolower($fullName);
        return $stateAbbreviations[$normalized] ?? strtoupper(substr($fullName, 0, 2));
    }

    /**
     * Get full state name from abbreviation
     */
    private function getFullStateName($abbreviation)
    {
        $stateNames = [
            'al' => 'Alabama',
            'ak' => 'Alaska',
            'az' => 'Arizona',
            'ar' => 'Arkansas',
            'ca' => 'California',
            'co' => 'Colorado',
            'ct' => 'Connecticut',
            'de' => 'Delaware',
            'fl' => 'Florida',
            'ga' => 'Georgia',
            'hi' => 'Hawaii',
            'id' => 'Idaho',
            'il' => 'Illinois',
            'in' => 'Indiana',
            'ia' => 'Iowa',
            'ks' => 'Kansas',
            'ky' => 'Kentucky',
            'la' => 'Louisiana',
            'me' => 'Maine',
            'md' => 'Maryland',
            'ma' => 'Massachusetts',
            'mi' => 'Michigan',
            'mn' => 'Minnesota',
            'ms' => 'Mississippi',
            'mo' => 'Missouri',
            'mt' => 'Montana',
            'ne' => 'Nebraska',
            'nv' => 'Nevada',
            'nh' => 'New Hampshire',
            'nj' => 'New Jersey',
            'nm' => 'New Mexico',
            'ny' => 'New York',
            'nc' => 'North Carolina',
            'nd' => 'North Dakota',
            'oh' => 'Ohio',
            'ok' => 'Oklahoma',
            'or' => 'Oregon',
            'pa' => 'Pennsylvania',
            'ri' => 'Rhode Island',
            'sc' => 'South Carolina',
            'sd' => 'South Dakota',
            'tn' => 'Tennessee',
            'tx' => 'Texas',
            'ut' => 'Utah',
            'vt' => 'Vermont',
            'va' => 'Virginia',
            'wa' => 'Washington',
            'wv' => 'West Virginia',
            'wi' => 'Wisconsin',
            'wy' => 'Wyoming',
            'dc' => 'District of Columbia'
        ];
        
        $abbrev = strtolower($abbreviation);
        return $stateNames[$abbrev] ?? $abbreviation;
    }

    /**
     * Populate place_regions table for existing places
     */
    public function populateExistingPlaceRegions($batchSize = 100)
    {
        $totalProcessed = 0;
        $totalSuccess = 0;
        $totalFailed = 0;
        
        Place::with('location')
            ->whereHas('location')
            ->chunk($batchSize, function($places) use (&$totalProcessed, &$totalSuccess, &$totalFailed) {
                foreach ($places as $place) {
                    $totalProcessed++;
                    
                    try {
                        if ($this->associatePlaceWithRegions($place)) {
                            $totalSuccess++;
                        } else {
                            $totalFailed++;
                        }
                    } catch (\Exception $e) {
                        $totalFailed++;
                        Log::error('Failed to associate place with regions', [
                            'place_id' => $place->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                    
                    if ($totalProcessed % 100 === 0) {
                        Log::info("Processed {$totalProcessed} places...");
                    }
                }
            });
            
        return [
            'total_processed' => $totalProcessed,
            'success' => $totalSuccess,
            'failed' => $totalFailed
        ];
    }
}