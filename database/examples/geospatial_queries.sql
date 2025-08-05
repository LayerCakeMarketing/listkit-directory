-- PostgreSQL Geospatial Query Examples
-- These queries demonstrate the performance and capabilities of the enhanced geospatial system

-- 1. BASIC DISTANCE QUERIES
-- Find all published restaurants within 5 miles of NYC coordinates
SELECT 
    id,
    title,
    city,
    state,
    latitude,
    longitude,
    ST_Distance(geom, ST_SetSRID(ST_MakePoint(-74.0060, 40.7128), 4326)::geography) / 1609.34 as distance_miles
FROM directory_entries 
WHERE status = 'published'
    AND geom IS NOT NULL
    AND ST_DWithin(geom, ST_SetSRID(ST_MakePoint(-74.0060, 40.7128), 4326)::geography, 8047) -- 5 miles in meters
ORDER BY distance_miles
LIMIT 20;

-- 2. OPTIMIZED RADIUS SEARCH WITH CATEGORY FILTER
-- This query uses the specialized indexes for maximum performance
EXPLAIN ANALYZE
SELECT 
    de.id,
    de.title,
    de.city,
    de.state,
    c.name as category_name,
    ROUND(ST_Distance(de.geom, ST_SetSRID(ST_MakePoint(-74.0060, 40.7128), 4326)::geography) * 0.000621371, 2) as distance_miles
FROM directory_entries de
JOIN categories c ON de.category_id = c.id
WHERE de.status = 'published'
    AND de.geom IS NOT NULL
    AND de.category_id = 1 -- restaurants category
    AND ST_DWithin(de.geom, ST_SetSRID(ST_MakePoint(-74.0060, 40.7128), 4326)::geography, 16093.4) -- 10 miles
ORDER BY de.geom <-> ST_SetSRID(ST_MakePoint(-74.0060, 40.7128), 4326)
LIMIT 50;

-- 3. BOUNDING BOX SEARCH (MAP VIEWPORT)
-- Fast query for loading places visible in a map viewport
SELECT 
    id,
    title,
    latitude,
    longitude,
    is_featured,
    is_verified,
    category_id
FROM directory_entries
WHERE status = 'published'
    AND latitude BETWEEN 40.6 AND 40.8  -- South to North
    AND longitude BETWEEN -74.2 AND -73.8  -- West to East
    AND latitude IS NOT NULL
    AND longitude IS NOT NULL
ORDER BY is_featured DESC, is_verified DESC
LIMIT 100;

-- 4. HIERARCHICAL REGION + DISTANCE QUERY
-- Find places in a specific city region within distance of a point
SELECT 
    de.id,
    de.title,
    de.city,
    cr.name as city_region,
    sr.name as state_region,
    ROUND(ST_Distance(de.geom, ST_SetSRID(ST_MakePoint(-74.0060, 40.7128), 4326)::geography) * 0.000621371, 2) as distance_miles
FROM directory_entries de
LEFT JOIN regions cr ON de.city_region_id = cr.id
LEFT JOIN regions sr ON de.state_region_id = sr.id
WHERE de.status = 'published'
    AND de.city_region_id = 123 -- NYC region ID
    AND de.geom IS NOT NULL
    AND ST_DWithin(de.geom, ST_SetSRID(ST_MakePoint(-74.0060, 40.7128), 4326)::geography, 8047)
ORDER BY distance_miles
LIMIT 25;

-- 5. NEAREST NEIGHBOR SEARCH
-- Find the 10 closest places to a given point (any category)
SELECT 
    id,
    title,
    city,
    state,
    category_id,
    ST_Distance(geom, ST_SetSRID(ST_MakePoint(-74.0060, 40.7128), 4326)::geography) / 1609.34 as distance_miles
FROM directory_entries
WHERE status = 'published'
    AND geom IS NOT NULL
ORDER BY geom <-> ST_SetSRID(ST_MakePoint(-74.0060, 40.7128), 4326)
LIMIT 10;

-- 6. AGGREGATED STATISTICS BY DISTANCE BANDS
-- Count places by distance ranges from a point
WITH distance_bands AS (
    SELECT 
        id,
        title,
        ST_Distance(geom, ST_SetSRID(ST_MakePoint(-74.0060, 40.7128), 4326)::geography) / 1609.34 as distance_miles
    FROM directory_entries
    WHERE status = 'published'
        AND geom IS NOT NULL
        AND ST_DWithin(geom, ST_SetSRID(ST_MakePoint(-74.0060, 40.7128), 4326)::geography, 80467) -- 50 miles
)
SELECT 
    CASE 
        WHEN distance_miles <= 1 THEN '0-1 miles'
        WHEN distance_miles <= 5 THEN '1-5 miles'
        WHEN distance_miles <= 10 THEN '5-10 miles'
        WHEN distance_miles <= 25 THEN '10-25 miles'
        ELSE '25+ miles'
    END as distance_band,
    COUNT(*) as place_count
FROM distance_bands
GROUP BY 
    CASE 
        WHEN distance_miles <= 1 THEN '0-1 miles'
        WHEN distance_miles <= 5 THEN '1-5 miles'
        WHEN distance_miles <= 10 THEN '5-10 miles'
        WHEN distance_miles <= 25 THEN '10-25 miles'
        ELSE '25+ miles'
    END
ORDER BY MIN(distance_miles);

-- 7. HOT SPOTS - AREAS WITH HIGH PLACE DENSITY
-- Find 1km grid squares with the most places
SELECT 
    FLOOR(ST_X(geom) * 100) / 100 as lng_grid,
    FLOOR(ST_Y(geom) * 100) / 100 as lat_grid,
    COUNT(*) as place_count,
    AVG(ST_X(geom)) as avg_lng,
    AVG(ST_Y(geom)) as avg_lat
FROM directory_entries
WHERE status = 'published'
    AND geom IS NOT NULL
    AND ST_Within(geom, ST_MakeEnvelope(-75, 40, -73, 41, 4326)) -- NYC area
GROUP BY 
    FLOOR(ST_X(geom) * 100) / 100,
    FLOOR(ST_Y(geom) * 100) / 100
HAVING COUNT(*) >= 5
ORDER BY place_count DESC
LIMIT 20;

-- 8. CATEGORY CLUSTERING ANALYSIS
-- Find areas where specific categories cluster together
WITH category_centers AS (
    SELECT 
        category_id,
        ST_Centroid(ST_Collect(geom)) as center_point,
        COUNT(*) as total_places
    FROM directory_entries
    WHERE status = 'published'
        AND geom IS NOT NULL
        AND category_id IS NOT NULL
    GROUP BY category_id
    HAVING COUNT(*) >= 10
)
SELECT 
    cc.category_id,
    c.name as category_name,
    cc.total_places,
    ST_Y(cc.center_point) as center_lat,
    ST_X(cc.center_point) as center_lng,
    -- Find average distance from center (clustering measure)
    AVG(ST_Distance(de.geom, cc.center_point::geography)) / 1609.34 as avg_distance_from_center_miles
FROM category_centers cc
JOIN categories c ON cc.category_id = c.id
JOIN directory_entries de ON de.category_id = cc.category_id AND de.status = 'published'
GROUP BY cc.category_id, c.name, cc.total_places, cc.center_point
ORDER BY avg_distance_from_center_miles -- Tightest clusters first
LIMIT 15;

-- 9. PERFORMANCE MONITORING QUERIES
-- Check index usage and query performance

-- Index usage statistics
SELECT 
    schemaname,
    tablename,
    indexname,
    idx_scan as index_scans,
    idx_tup_read as tuples_read,
    idx_tup_fetch as tuples_fetched
FROM pg_stat_user_indexes 
WHERE tablename IN ('directory_entries', 'locations')
ORDER BY idx_scan DESC;

-- Table scan statistics
SELECT 
    schemaname,
    tablename,
    seq_scan as sequential_scans,
    seq_tup_read as sequential_tuples_read,
    idx_scan as index_scans,
    idx_tup_fetch as index_tuples_fetched,
    n_tup_ins as inserts,
    n_tup_upd as updates,
    n_tup_del as deletes
FROM pg_stat_user_tables 
WHERE tablename IN ('directory_entries', 'locations');

-- 10. DATA QUALITY CHECKS
-- Find places that might need geocoding attention

-- Places with coordinates but no verified location
SELECT COUNT(*) as unverified_with_coords
FROM directory_entries 
WHERE latitude IS NOT NULL 
    AND longitude IS NOT NULL 
    AND (location_verified = false OR location_verified IS NULL);

-- Places with addresses but no coordinates
SELECT COUNT(*) as needs_geocoding
FROM directory_entries 
WHERE (address_line1 IS NOT NULL OR city IS NOT NULL)
    AND (latitude IS NULL OR longitude IS NULL)
    AND status IN ('published', 'pending_review');

-- Places with invalid coordinates
SELECT id, title, latitude, longitude
FROM directory_entries 
WHERE latitude IS NOT NULL 
    AND longitude IS NOT NULL
    AND (
        latitude NOT BETWEEN -90 AND 90 
        OR longitude NOT BETWEEN -180 AND 180
    );

-- 11. MATERIALIZED VIEW REFRESH AND STATS
-- Check the geospatial cache performance
SELECT 
    schemaname,
    matviewname,
    ispopulated,
    n_tup_ins + n_tup_upd + n_tup_del as total_changes
FROM pg_stat_user_tables 
WHERE tablename = 'directory_entries_geospatial_cache';

-- Refresh the materialized view (run periodically)
-- REFRESH MATERIALIZED VIEW CONCURRENTLY directory_entries_geospatial_cache;

-- 12. SAMPLE LARAVEL ELOQUENT EQUIVALENTS
-- These SQL queries correspond to Laravel Eloquent methods:

/*
// Basic radius search
$places = Place::published()
    ->withinRadius($lat, $lng, $radiusMiles)
    ->orderByDistance($lat, $lng)
    ->limit(20)
    ->get();

// Bounding box search
$places = Place::published()
    ->withinBoundingBox($north, $south, $east, $west)
    ->limit(100)
    ->get();

// Category + distance search
$places = Place::published()
    ->where('category_id', $categoryId)
    ->withinRadius($lat, $lng, $radiusMiles)
    ->with(['category', 'stateRegion', 'cityRegion'])
    ->orderByDistance($lat, $lng)
    ->get();

// Nearby places for a specific place
$nearbyPlaces = $place->getNearbyPlaces($radiusMiles, $limit);

// Places needing geocoding
$places = Place::needsGeocoding()->limit(50)->get();
*/