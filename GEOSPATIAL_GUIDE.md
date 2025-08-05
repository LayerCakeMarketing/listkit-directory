# PostgreSQL Geospatial System Guide

This document provides a comprehensive overview of the enhanced geospatial capabilities added to the Listerino directory application, including implementation details, performance optimization, and usage examples.

## Table of Contents

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Migration Strategy](#migration-strategy)
4. [Performance Optimization](#performance-optimization)
5. [API Endpoints](#api-endpoints)
6. [Model Methods](#model-methods)
7. [Query Examples](#query-examples)
8. [Geocoding Service](#geocoding-service)
9. [Maintenance Commands](#maintenance-commands)
10. [Best Practices](#best-practices)
11. [Troubleshooting](#troubleshooting)

## Overview

The geospatial system enhances the existing Places/Business directory with comprehensive location-based capabilities using PostGIS, PostgreSQL's spatial extension. The system provides:

- **High-Performance Spatial Queries**: Optimized for millions of places
- **Multiple Geocoding Providers**: Google Maps, Mapbox, OpenStreetMap
- **Intelligent Caching**: Prevents redundant API calls
- **Distance-Based Search**: Radius and bounding box queries
- **Spatial Indexing**: GiST indexes for sub-second query performance
- **Data Quality Management**: Automatic validation and cleanup

### Key Features

✅ **Fast Radius Searches**: Find places within X miles of coordinates  
✅ **Bounding Box Queries**: Load places visible in map viewports  
✅ **Distance Calculations**: Accurate distance between places/coordinates  
✅ **Geocoding Cache**: Prevents repeated API calls for same addresses  
✅ **Batch Processing**: Efficient bulk geocoding operations  
✅ **Performance Monitoring**: Index usage and query optimization tools  
✅ **Data Integrity**: Automatic validation and cleanup of coordinates  

## Architecture

### Database Schema Design

The system uses a **dual-table approach** for optimal performance:

1. **`directory_entries` table**: Contains denormalized lat/lng for fast queries
2. **`locations` table**: Contains detailed location metadata and geocoding history

This design provides:
- **Fast queries** via denormalized coordinates in main table
- **Rich metadata** in separate locations table
- **Backward compatibility** with existing architecture
- **Efficient joins** when detailed location data is needed

### PostGIS Integration

```sql
-- Core spatial columns
ALTER TABLE directory_entries ADD COLUMN geom geometry(Point, 4326);
ALTER TABLE locations ADD COLUMN geom geography(Point, 4326);

-- Spatial indexes
CREATE INDEX directory_entries_geom_gist ON directory_entries USING GIST(geom);
CREATE INDEX locations_geom_gist ON locations USING GIST(geom);
```

### Automatic Geometry Updates

Database triggers automatically maintain geometry columns:

```sql
CREATE OR REPLACE FUNCTION update_directory_entry_geometry() 
RETURNS trigger AS $$
BEGIN
    IF NEW.latitude IS NOT NULL AND NEW.longitude IS NOT NULL 
       AND NEW.latitude BETWEEN -90 AND 90 
       AND NEW.longitude BETWEEN -180 AND 180 THEN
        NEW.geom = ST_SetSRID(ST_MakePoint(NEW.longitude, NEW.latitude), 4326);
        NEW.location_updated_at = NOW();
    ELSE
        NEW.geom = NULL;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;
```

## Migration Strategy

### Step 1: Enable PostGIS Extension

```bash
php artisan migrate --path=database/migrations/2025_08_01_000001_add_postgis_extension.php
```

This migration:
- Installs PostgreSQL PostGIS extension
- Verifies installation
- Safe to run multiple times

### Step 2: Enhance Locations Table

```bash
php artisan migrate --path=database/migrations/2025_08_01_000002_enhance_locations_with_postgis.php
```

This migration:
- Adds PostGIS geometry column to locations table
- Creates spatial indexes
- Adds geocoding cache fields
- Populates existing records with geometry data

### Step 3: Add Geospatial Fields to Places

```bash
php artisan migrate --path=database/migrations/2025_08_01_000003_add_geospatial_fields_to_directory_entries.php
```

This migration:
- Adds lat/lng columns directly to directory_entries
- Adds address fields for denormalized access
- Migrates data from locations table
- Creates spatial indexes on main table

### Step 4: Create Performance Indexes

```bash
php artisan migrate --path=database/migrations/2025_08_01_000004_create_geospatial_performance_indexes.php
```

This migration:
- Creates specialized indexes for common query patterns
- Sets up materialized view for hot data caching
- Optimizes for category + location queries
- Creates full-text search + location indexes

### Zero-Downtime Migration

The migrations are designed for zero-downtime deployment:

1. **Backward Compatible**: Existing queries continue to work
2. **Additive Changes**: Only adds columns and indexes
3. **Concurrent Indexing**: Uses `CREATE INDEX CONCURRENTLY`
4. **Data Preservation**: Migrates all existing location data

## Performance Optimization

### Indexing Strategy

The system creates specialized indexes for different query patterns:

```sql
-- Primary radius search index (most common)
CREATE INDEX directory_entries_published_geom_idx 
ON directory_entries USING GIST(geom) 
WHERE status = 'published' AND geom IS NOT NULL;

-- Category + location searches
CREATE INDEX directory_entries_category_geom_idx 
ON directory_entries USING GIST(category_id, geom) 
WHERE status = 'published' AND category_id IS NOT NULL AND geom IS NOT NULL;

-- Hierarchical region + location
CREATE INDEX directory_entries_city_region_geom_idx 
ON directory_entries USING GIST(city_region_id, geom) 
WHERE status = 'published' AND city_region_id IS NOT NULL AND geom IS NOT NULL;
```

### Materialized View Cache

For frequently accessed data, the system maintains a materialized view:

```sql
CREATE MATERIALIZED VIEW directory_entries_geospatial_cache AS
SELECT 
    id, title, slug, category_id, state_region_id, city_region_id,
    latitude, longitude, geom, status, is_featured, is_verified,
    published_at, location_updated_at
FROM directory_entries 
WHERE status = 'published' AND geom IS NOT NULL
WITH DATA;
```

Refresh the cache periodically:
```bash
php artisan geospatial:maintain --refresh-cache
```

### Query Performance Monitoring

Monitor index usage and performance:

```sql
-- Check index usage
SELECT indexname, idx_scan, idx_tup_read, idx_tup_fetch
FROM pg_stat_user_indexes 
WHERE tablename = 'directory_entries'
ORDER BY idx_scan DESC;

-- Analyze query performance
EXPLAIN ANALYZE
SELECT * FROM directory_entries 
WHERE ST_DWithin(geom, ST_SetSRID(ST_MakePoint(-74.0060, 40.7128), 4326)::geography, 8047)
ORDER BY geom <-> ST_SetSRID(ST_MakePoint(-74.0060, 40.7128), 4326)
LIMIT 20;
```

## API Endpoints

### Find Nearby Places

```http
GET /api/geospatial/nearby?lat=40.7128&lng=-74.0060&radius=5&limit=20
```

Parameters:
- `lat` (required): Latitude (-90 to 90)
- `lng` (required): Longitude (-180 to 180)
- `radius` (optional): Search radius in miles (default: 25, max: 100)
- `limit` (optional): Maximum results (default: 20, max: 100)
- `category_id` (optional): Filter by category
- `featured_only` (optional): Only featured places

Response:
```json
{
  "places": [...],
  "search_center": {"lat": 40.7128, "lng": -74.0060},
  "radius_miles": 5,
  "total_found": 15
}
```

### Bounding Box Search

```http
GET /api/geospatial/in-bounds?north=40.8&south=40.6&east=-73.8&west=-74.2
```

Optimized for map viewport loading. Returns places within rectangular bounds.

### Calculate Distance

```http
GET /api/geospatial/distance?from_id=1&to_id=2&unit=miles
GET /api/geospatial/distance?from_id=1&to_lat=40.7128&to_lng=-74.0060
```

### Geocode Address

```http
POST /api/geospatial/geocode
Content-Type: application/json

{
  "address": "123 Main St, New York, NY 10001"
}
```

### Reverse Geocode

```http
POST /api/geospatial/reverse-geocode
Content-Type: application/json

{
  "lat": 40.7128,
  "lng": -74.0060
}
```

## Model Methods

### Place Model Geospatial Scopes

```php
// Find places within radius
$places = Place::published()
    ->withinRadius($lat, $lng, $radiusMiles)
    ->orderByDistance($lat, $lng)
    ->get();

// Fast bounding box search
$places = Place::published()
    ->withinBoundingBox($north, $south, $east, $west)
    ->get();

// Places with valid coordinates
$places = Place::withCoordinates()->get();

// Places needing geocoding
$places = Place::needsGeocoding()->get();

// Fast nearby search (bounding box approximation)
$places = Place::nearbyFast($lat, $lng, $radiusMiles)->get();
```

### Instance Methods

```php
$place = Place::find(1);

// Check if place has valid coordinates
if ($place->hasValidCoordinates()) {
    // Get coordinates as array
    $coords = $place->coordinates; // ['lat' => 40.7128, 'lng' => -74.0060]
    
    // Calculate distance to another place
    $distance = $place->distanceTo($otherPlace, 'miles');
    
    // Calculate distance to coordinates
    $distance = $place->distanceTo(['lat' => 40.7128, 'lng' => -74.0060]);
    
    // Get nearby places
    $nearby = $place->getNearbyPlaces($radiusMiles = 5, $limit = 10);
    
    // Update coordinates
    $place->updateCoordinates($lat, $lng, 'manual_correction');
}

// Check if place needs geocoding
if ($place->needsGeocoding()) {
    $address = $place->getGeocodingAddress();
    // Geocode the place...
}

// Get full address
$address = $place->full_address;
```

## Query Examples

### 1. Basic Radius Search

```php
// Eloquent
$places = Place::published()
    ->withinRadius(40.7128, -74.0060, 5) // 5 miles from NYC
    ->orderByDistance(40.7128, -74.0060)
    ->limit(20)
    ->get();

// Raw SQL
$places = DB::select("
    SELECT *, ST_Distance(geom, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography) / 1609.34 as distance_miles
    FROM directory_entries 
    WHERE status = 'published'
        AND ST_DWithin(geom, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)
    ORDER BY distance_miles
    LIMIT 20
", [-74.0060, 40.7128, -74.0060, 40.7128, 8047]);
```

### 2. Category + Location Search

```php
$restaurants = Place::published()
    ->where('category_id', $restaurantCategoryId)
    ->withinRadius($lat, $lng, 10)
    ->with(['category'])
    ->orderByDistance($lat, $lng)
    ->get();
```

### 3. Map Viewport Loading

```php
// Load places visible in map bounds
$places = Place::published()
    ->withinBoundingBox($north, $south, $east, $west)
    ->select(['id', 'title', 'latitude', 'longitude', 'category_id', 'is_featured'])
    ->limit(100)
    ->get();
```

### 4. Nearest Neighbor Search

```php
// Find 10 closest places regardless of distance
$nearest = Place::published()
    ->withCoordinates()
    ->orderByDistance($lat, $lng)
    ->limit(10)
    ->get();
```

## Geocoding Service

### Configuration

Add to your `.env` file:

```env
# Geocoding provider: openstreetmap, google_maps, mapbox
GEOCODING_PROVIDER=openstreetmap

# Google Maps (requires API key)
GOOGLE_MAPS_API_KEY=your_api_key_here
GOOGLE_MAPS_REGION=us

# Mapbox (requires access token)
MAPBOX_ACCESS_TOKEN=your_access_token_here
MAPBOX_COUNTRY_CODE=us

# Rate limiting
GEOCODING_RATE_LIMIT_MS=100
GEOCODING_CACHE_DAYS=30
```

### Usage

```php
use App\Services\GeocodingService;

$geocoder = app(GeocodingService::class);

// Geocode an address
$result = $geocoder->geocode('123 Main St, New York, NY');
if ($result) {
    $lat = $result['coordinates']['lat'];
    $lng = $result['coordinates']['lng'];
    $formatted = $result['formatted_address'];
}

// Reverse geocode coordinates
$result = $geocoder->reverseGeocode(40.7128, -74.0060);

// Geocode a place model
$success = $geocoder->geocodePlace($place);

// Batch geocode multiple places
$successCount = $geocoder->batchGeocodePlaces($limit = 50);

// Get geocoding statistics
$stats = $geocoder->getGeocodingStats();
```

### Geocoding Providers

#### OpenStreetMap Nominatim (Free)
- **Pros**: Free, no API key required, good coverage
- **Cons**: Rate limited (1 request/second), less accurate for business addresses
- **Best for**: Development, low-volume applications

#### Google Maps Geocoding API (Paid)
- **Pros**: Highest accuracy, excellent business data, place IDs
- **Cons**: Requires API key, costs money after free tier
- **Best for**: Production with budget, highest accuracy needed

#### Mapbox Geocoding API (Paid)
- **Pros**: Good accuracy, developer-friendly, custom data integration
- **Cons**: Requires access token, costs money
- **Best for**: Applications already using Mapbox for maps

## Maintenance Commands

### Geospatial Maintenance Command

```bash
# Show statistics
php artisan geospatial:maintain --stats

# Run geocoding for places that need it
php artisan geospatial:maintain --geocode --limit=100

# Refresh materialized view cache
php artisan geospatial:maintain --refresh-cache

# Clean up invalid data
php artisan geospatial:maintain --cleanup

# Analyze table statistics for query optimization
php artisan geospatial:maintain --analyze

# Run all maintenance tasks
php artisan geospatial:maintain
```

### Scheduled Maintenance

Add to your `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Daily geocoding of new places
    $schedule->command('geospatial:maintain --geocode --limit=200')
        ->daily()
        ->at('02:00');
    
    // Weekly cache refresh
    $schedule->command('geospatial:maintain --refresh-cache')
        ->weekly()
        ->sundays()
        ->at('03:00');
    
    // Monthly cleanup and analysis
    $schedule->command('geospatial:maintain --cleanup --analyze')
        ->monthly()
        ->at('04:00');
}
```

## Best Practices

### Query Optimization

1. **Use Appropriate Indexes**: The system creates specialized indexes for common patterns
2. **Limit Result Sets**: Always use `LIMIT` for large datasets
3. **Prefer Bounding Box for Large Areas**: For map viewports > 50 miles
4. **Use Fast Approximation First**: `nearbyFast()` for initial filtering

### Data Quality

1. **Validate Coordinates**: System automatically validates lat/lng ranges
2. **Cache Geocoding Results**: Prevents redundant API calls
3. **Monitor Geocoding Coverage**: Aim for >90% of places geocoded
4. **Regular Cleanup**: Run maintenance commands regularly

### Performance Monitoring

1. **Monitor Index Usage**: Check `pg_stat_user_indexes` regularly
2. **Analyze Query Performance**: Use `EXPLAIN ANALYZE` for slow queries
3. **Refresh Materialized Views**: Keep cache up-to-date
4. **Monitor API Limits**: Track geocoding provider usage

### Error Handling

```php
// Always check for valid coordinates
if (!$place->hasValidCoordinates()) {
    // Handle missing coordinates
    return response()->json(['error' => 'Place location not available'], 404);
}

// Handle geocoding failures gracefully
try {
    $result = $geocoder->geocode($address);
    if (!$result) {
        Log::warning('Geocoding failed', ['address' => $address]);
        // Continue without coordinates or use fallback
    }
} catch (\Exception $e) {
    Log::error('Geocoding error', ['error' => $e->getMessage()]);
}
```

## Troubleshooting

### Common Issues

#### 1. PostGIS Extension Not Installed

**Error**: `ERROR: type "geometry" does not exist`

**Solution**:
```bash
# On Ubuntu/Debian
sudo apt-get install postgresql-15-postgis-3

# On macOS with Homebrew
brew install postgis

# Connect to database and enable extension
psql -d your_database -c "CREATE EXTENSION IF NOT EXISTS postgis;"
```

#### 2. Slow Geospatial Queries

**Symptoms**: Queries taking >1 second for radius searches

**Solutions**:
1. Check if spatial indexes exist:
   ```sql
   \d directory_entries_geom_gist
   ```
2. Analyze table statistics:
   ```bash
   php artisan geospatial:maintain --analyze
   ```
3. Consider using materialized view for hot data

#### 3. Geocoding API Limits

**Error**: Rate limit exceeded or quota exceeded

**Solutions**:
1. Increase rate limiting delay:
   ```env
   GEOCODING_RATE_LIMIT_MS=500
   ```
2. Switch to different provider:
   ```env
   GEOCODING_PROVIDER=openstreetmap
   ```
3. Implement batching with longer delays

#### 4. Invalid Coordinates

**Symptoms**: Places showing in wrong locations

**Solution**:
```bash
# Run cleanup to find and fix invalid coordinates
php artisan geospatial:maintain --cleanup --stats
```

### Performance Debugging

#### Slow Distance Queries

```sql
-- Check if using spatial index
EXPLAIN ANALYZE
SELECT * FROM directory_entries 
WHERE ST_DWithin(geom, ST_SetSRID(ST_MakePoint(-74.0060, 40.7128), 4326)::geography, 8047)
ORDER BY geom <-> ST_SetSRID(ST_MakePoint(-74.0060, 40.7128), 4326)
LIMIT 20;

-- Should show "Index Scan using directory_entries_geom_gist"
```

#### Index Usage Statistics

```sql
-- Check which indexes are being used
SELECT 
    indexname,
    idx_scan as scans,
    idx_tup_read as tuples_read,
    ROUND(idx_tup_read::numeric / NULLIF(idx_scan::numeric, 0), 2) as avg_tuples_per_scan
FROM pg_stat_user_indexes 
WHERE tablename = 'directory_entries'
    AND idx_scan > 0
ORDER BY idx_scan DESC;
```

### Database Health Checks

```bash
# Check geospatial system health
php artisan geospatial:maintain --stats

# Should show:
# ✅ High geocoding coverage (>80%)
# ✅ Low invalid coordinates count (<1%)
# ✅ Recent geocoding activity
# ✅ Materialized view up-to-date
```

## Migration Rollback

If you need to rollback the geospatial enhancements:

```bash
# Rollback in reverse order
php artisan migrate:rollback --path=database/migrations/2025_08_01_000004_create_geospatial_performance_indexes.php
php artisan migrate:rollback --path=database/migrations/2025_08_01_000003_add_geospatial_fields_to_directory_entries.php
php artisan migrate:rollback --path=database/migrations/2025_08_01_000002_enhance_locations_with_postgis.php
# NOTE: PostGIS extension rollback requires manual intervention
```

**Warning**: Rollback will remove all geospatial indexes and columns. Backup your data first!

---

## Summary

This geospatial system provides a production-ready, high-performance solution for location-based queries in the Listerino directory application. Key benefits:

- **Performance**: Sub-second queries on millions of places
- **Scalability**: Optimized indexes and caching strategies  
- **Reliability**: Multiple geocoding providers with fallbacks
- **Maintainability**: Comprehensive tooling and monitoring
- **Flexibility**: Support for various query patterns and use cases

The system is designed to handle real-world scale while maintaining data quality and query performance. Regular maintenance and monitoring will ensure optimal operation as your dataset grows.