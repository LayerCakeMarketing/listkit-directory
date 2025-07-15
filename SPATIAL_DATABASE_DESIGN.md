# Spatial Database Design for Location-Based Directory

## Overview
This document outlines the database architecture for a Zillow-inspired location-based directory system that supports both rigid hierarchical regions and fluid, user-defined boundaries.

## Core Database Schema

### 1. Enhanced Regions Table
```sql
-- Already exists, enhanced with new fields via migration
CREATE TABLE regions (
    id BIGSERIAL PRIMARY KEY,
    parent_id BIGINT REFERENCES regions(id),
    name VARCHAR(255) NOT NULL,          -- Display name (e.g., "CA", "Irvine")
    full_name VARCHAR(255),              -- Full display name (e.g., "California")
    slug VARCHAR(255) NOT NULL,          -- URL-friendly (e.g., "ca", "irvine")
    abbreviation VARCHAR(10),            -- Standard abbreviation (e.g., "CA")
    type VARCHAR(50) NOT NULL,           -- 'state', 'city', 'neighborhood', 'custom'
    level INTEGER NOT NULL,              -- 1=state, 2=city, 3=neighborhood, 4+=custom
    
    -- Spatial data
    boundary GEOMETRY(POLYGON, 4326),    -- GeoJSON polygon boundary
    center_point GEOMETRY(POINT, 4326),  -- Center point for quick distance queries
    area_sq_km DECIMAL(10,2),           -- Pre-calculated area
    
    -- Metadata
    metadata JSONB,                      -- Flexible metadata storage
    alternate_names JSONB,               -- Array of alternate names
    is_user_defined BOOLEAN DEFAULT FALSE,
    created_by_user_id BIGINT REFERENCES users(id),
    
    -- Performance
    cached_place_count INTEGER DEFAULT 0,
    cache_updated_at TIMESTAMP,
    
    -- Timestamps
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    -- Indexes
    UNIQUE(slug, type, parent_id),
    INDEX idx_parent_id (parent_id),
    INDEX idx_type_level (type, level),
    SPATIAL INDEX idx_boundary (boundary),
    SPATIAL INDEX idx_center_point (center_point)
);
```

### 2. Place-Region Association Strategy

#### Option A: Materialized Association Table (Recommended for Performance)
```sql
CREATE TABLE place_regions (
    id BIGSERIAL PRIMARY KEY,
    place_id BIGINT NOT NULL REFERENCES directory_entries(id) ON DELETE CASCADE,
    region_id BIGINT NOT NULL REFERENCES regions(id) ON DELETE CASCADE,
    association_type VARCHAR(50) NOT NULL, -- 'contained', 'nearby', 'user_assigned'
    distance_meters DECIMAL(10,2),         -- For 'nearby' associations
    confidence_score DECIMAL(3,2),         -- 0-1 confidence in association
    
    -- Denormalized for performance
    region_type VARCHAR(50),               -- Copied from regions.type
    region_level INTEGER,                  -- Copied from regions.level
    
    created_at TIMESTAMP DEFAULT NOW(),
    
    -- Indexes for fast lookups
    UNIQUE(place_id, region_id),
    INDEX idx_region_place (region_id, place_id),
    INDEX idx_region_type (region_id, region_type),
    INDEX idx_place_region_type (place_id, region_type)
);

-- Example: When adding a place to Woodbridge
-- It gets associated with:
-- - Woodbridge (neighborhood)
-- - Irvine (city)  
-- - CA (state)
-- All associations are stored explicitly for fast retrieval
```

#### Option B: Dynamic Spatial Queries (More Flexible, Requires PostGIS)
```sql
-- No association table needed
-- Places have location, regions have boundaries
-- Query dynamically using spatial functions

-- Example query to find all places in a region:
SELECT p.*
FROM directory_entries p
JOIN locations l ON p.id = l.directory_entry_id
JOIN regions r ON ST_Contains(r.boundary, l.coordinates)
WHERE r.slug = 'woodbridge';
```

### 3. Optimized Places Table Structure
```sql
-- Enhanced directory_entries table
ALTER TABLE directory_entries ADD COLUMN IF NOT EXISTS (
    -- Spatial data
    coordinates GEOMETRY(POINT, 4326),
    
    -- Denormalized region data for performance
    state_name VARCHAR(255),
    city_name VARCHAR(255),
    neighborhood_name VARCHAR(255),
    
    -- Search optimization
    search_vector tsvector,
    popularity_score INTEGER DEFAULT 0,
    
    -- Spatial indexing
    SPATIAL INDEX idx_coordinates (coordinates)
);

-- Materialized view for fast region queries
CREATE MATERIALIZED VIEW mv_places_by_region AS
SELECT 
    r.id as region_id,
    r.slug as region_slug,
    r.type as region_type,
    r.full_name as region_name,
    p.id as place_id,
    p.title,
    p.coordinates,
    p.popularity_score
FROM regions r
JOIN place_regions pr ON r.id = pr.region_id
JOIN directory_entries p ON pr.place_id = p.id
WHERE p.status = 'published'
ORDER BY r.id, p.popularity_score DESC;

CREATE INDEX idx_mv_region_slug ON mv_places_by_region(region_slug);
CREATE INDEX idx_mv_region_type ON mv_places_by_region(region_type);
```

## Implementation Examples

### 1. Adding a Place to Woodbridge
```php
// PlaceService.php
public function addPlace($placeData, $location) 
{
    DB::transaction(function() use ($placeData, $location) {
        // 1. Create the place
        $place = Place::create($placeData);
        
        // 2. Add location with coordinates
        $location['coordinates'] = DB::raw("ST_SetSRID(ST_MakePoint({$location['longitude']}, {$location['latitude']}), 4326)");
        $place->location()->create($location);
        
        // 3. Find all containing regions using spatial query
        $regions = DB::select("
            SELECT id, type, level, slug, full_name
            FROM regions
            WHERE ST_Contains(boundary, ST_SetSRID(ST_MakePoint(?, ?), 4326))
            ORDER BY level DESC
        ", [$location['longitude'], $location['latitude']]);
        
        // 4. Create associations
        foreach ($regions as $region) {
            DB::table('place_regions')->insert([
                'place_id' => $place->id,
                'region_id' => $region->id,
                'association_type' => 'contained',
                'region_type' => $region->type,
                'region_level' => $region->level,
                'confidence_score' => 1.0
            ]);
        }
        
        // 5. Update denormalized fields for performance
        $place->update([
            'state_name' => $regions->firstWhere('type', 'state')->full_name ?? null,
            'city_name' => $regions->firstWhere('type', 'city')->full_name ?? null,
            'neighborhood_name' => $regions->firstWhere('type', 'neighborhood')->full_name ?? null,
        ]);
        
        // 6. Update region cache counts
        DB::table('regions')
            ->whereIn('id', collect($regions)->pluck('id'))
            ->increment('cached_place_count');
    });
}
```

### 2. Efficient Region Queries

```php
// RegionRepository.php
class RegionRepository 
{
    // Get places for a region with caching
    public function getPlacesForRegion($regionSlug, $options = [])
    {
        $cacheKey = "region_places_{$regionSlug}_" . md5(json_encode($options));
        
        return Cache::remember($cacheKey, 3600, function() use ($regionSlug, $options) {
            $query = DB::table('mv_places_by_region')
                ->where('region_slug', $regionSlug);
                
            if (!empty($options['category_id'])) {
                $query->join('directory_entries', 'place_id', '=', 'directory_entries.id')
                      ->where('category_id', $options['category_id']);
            }
            
            return $query->limit($options['limit'] ?? 50)->get();
        });
    }
    
    // Find places within custom polygon
    public function getPlacesInPolygon($geoJson)
    {
        return DB::select("
            SELECT p.*, ST_Distance(p.coordinates, ST_Centroid(?::geometry)) as distance
            FROM directory_entries p
            WHERE ST_Contains(?::geometry, p.coordinates)
            AND p.status = 'published'
            ORDER BY distance
            LIMIT 100
        ", [$geoJson, $geoJson]);
    }
    
    // Find nearby places
    public function getNearbyPlaces($lat, $lng, $radiusMeters = 5000)
    {
        return DB::select("
            SELECT p.*, 
                   ST_Distance(p.coordinates::geography, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography) as distance
            FROM directory_entries p
            WHERE ST_DWithin(
                p.coordinates::geography,
                ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography,
                ?
            )
            AND p.status = 'published'
            ORDER BY distance
            LIMIT 50
        ", [$lng, $lat, $lng, $lat, $radiusMeters]);
    }
}
```

### 3. Display Name Mapping

```php
// Region.php model
class Region extends Model
{
    public function getDisplayNameAttribute()
    {
        return $this->full_name ?: $this->name;
    }
    
    public function getUrlAttribute()
    {
        $path = [$this->slug];
        $parent = $this->parent;
        
        while ($parent) {
            array_unshift($path, $parent->slug);
            $parent = $parent->parent;
        }
        
        return '/local/' . implode('/', $path);
    }
}
```

## Performance Optimization Strategies

### 1. Indexing Strategy
```sql
-- Spatial indexes (already created)
CREATE INDEX idx_regions_boundary ON regions USING GIST(boundary);
CREATE INDEX idx_places_coordinates ON directory_entries USING GIST(coordinates);

-- Composite indexes for common queries
CREATE INDEX idx_place_regions_lookup ON place_regions(region_id, region_type, place_id);
CREATE INDEX idx_places_status_popularity ON directory_entries(status, popularity_score DESC);

-- Full-text search
CREATE INDEX idx_places_search ON directory_entries USING GIN(search_vector);
```

### 2. Query Optimization Examples

```sql
-- Fast region page load (using materialized view)
SELECT * FROM mv_places_by_region 
WHERE region_slug = 'irvine' 
LIMIT 50;

-- Autocomplete search (using tsvector)
SELECT id, title, city_name, state_name
FROM directory_entries
WHERE search_vector @@ plainto_tsquery('coffee shop')
AND status = 'published'
ORDER BY popularity_score DESC
LIMIT 10;

-- Multi-region search (e.g., show places in multiple neighborhoods)
SELECT DISTINCT p.*
FROM directory_entries p
JOIN place_regions pr ON p.id = pr.place_id
WHERE pr.region_id IN (
    SELECT id FROM regions WHERE slug IN ('woodbridge', 'turtle-rock', 'university-park')
)
AND p.status = 'published'
ORDER BY p.popularity_score DESC;
```

### 3. Caching Strategy

```php
// Use Redis for hot data
class PlaceCache 
{
    public function getRegionPlaces($regionId, $page = 1)
    {
        $key = "region:{$regionId}:places:page:{$page}";
        
        return Cache::tags(['regions', "region:{$regionId}"])->remember($key, 3600, function() use ($regionId, $page) {
            return Place::whereHas('regions', function($q) use ($regionId) {
                $q->where('region_id', $regionId);
            })
            ->published()
            ->orderBy('popularity_score', 'desc')
            ->paginate(50, ['*'], 'page', $page);
        });
    }
    
    public function invalidateRegion($regionId)
    {
        Cache::tags(["region:{$regionId}"])->flush();
    }
}
```

## Handling Fluid Boundaries

### 1. User-Defined Regions
```sql
-- Allow users to create custom regions
INSERT INTO regions (name, full_name, slug, type, level, boundary, is_user_defined, created_by_user_id)
VALUES (
    'My Favorite Coffee Spots',
    'My Favorite Coffee Spots in Irvine',
    'my-favorite-coffee-spots',
    'custom',
    4,
    ST_GeomFromGeoJSON('{"type":"Polygon","coordinates":[...]}'),
    true,
    123
);
```

### 2. Overlapping Regions
```sql
-- Find all regions containing a point (handles overlaps)
SELECT r.*, 
       CASE 
           WHEN r.type = 'neighborhood' THEN 1
           WHEN r.type = 'custom' THEN 2
           WHEN r.type = 'city' THEN 3
           WHEN r.type = 'state' THEN 4
       END as priority
FROM regions r
WHERE ST_Contains(r.boundary, ST_SetSRID(ST_MakePoint(-117.8265, 33.6846), 4326))
ORDER BY priority;
```

### 3. Fuzzy Boundaries
```sql
-- Associate places near but not within a region
INSERT INTO place_regions (place_id, region_id, association_type, distance_meters, confidence_score)
SELECT 
    p.id,
    r.id,
    'nearby',
    ST_Distance(p.coordinates::geography, r.boundary::geography),
    CASE 
        WHEN ST_Distance(p.coordinates::geography, r.boundary::geography) < 500 THEN 0.9
        WHEN ST_Distance(p.coordinates::geography, r.boundary::geography) < 1000 THEN 0.7
        ELSE 0.5
    END
FROM directory_entries p, regions r
WHERE ST_DWithin(p.coordinates::geography, r.boundary::geography, 1000)
AND NOT ST_Contains(r.boundary, p.coordinates);
```

## Potential Pitfalls & Solutions

### 1. **Pitfall**: Slow spatial queries on large datasets
**Solution**: Use spatial indexes, materialized views, and consider PostgreSQL table partitioning by region for very large datasets.

### 2. **Pitfall**: Stale cache with user-defined regions
**Solution**: Implement cache tags and invalidation strategies that update when boundaries change.

### 3. **Pitfall**: Complex hierarchy updates
**Solution**: Use recursive CTEs for hierarchy queries and materialized paths for denormalization.

### 4. **Pitfall**: Inconsistent place-region associations
**Solution**: Use database triggers to maintain consistency:

```sql
CREATE OR REPLACE FUNCTION update_place_regions()
RETURNS TRIGGER AS $$
BEGIN
    -- When a place's location changes, update region associations
    IF TG_OP = 'UPDATE' AND 
       OLD.coordinates IS DISTINCT FROM NEW.coordinates THEN
        
        -- Remove old associations
        DELETE FROM place_regions WHERE place_id = NEW.id;
        
        -- Add new associations
        INSERT INTO place_regions (place_id, region_id, association_type, region_type, region_level)
        SELECT NEW.id, r.id, 'contained', r.type, r.level
        FROM regions r
        WHERE ST_Contains(r.boundary, NEW.coordinates);
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_update_place_regions
AFTER UPDATE ON directory_entries
FOR EACH ROW
EXECUTE FUNCTION update_place_regions();
```

## Implementation Checklist

1. ✅ Add spatial extensions: `CREATE EXTENSION postgis;`
2. ✅ Run migration for regions table enhancements
3. ✅ Create place_regions association table
4. ✅ Add spatial columns to directory_entries
5. ✅ Create materialized views for performance
6. ✅ Implement caching layer (in PlaceRegionService)
7. ✅ Add triggers for consistency
8. ✅ Update API endpoints for spatial queries (PlaceRegionService)
9. ⬜ Implement region boundary editor UI
10. ✅ Add monitoring for query performance (scheduled refresh command)

This architecture provides the flexibility of Zillow-style searches while maintaining performance at scale.