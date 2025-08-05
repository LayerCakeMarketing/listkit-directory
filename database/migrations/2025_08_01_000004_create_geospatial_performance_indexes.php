<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates specialized indexes optimized for different geospatial query patterns
     * that will be common in the application.
     */
    public function up(): void
    {
        // 1. Indexes for radius-based searches (most common use case)
        
        // Partial index for published places with coordinates - primary use case
        DB::statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS directory_entries_published_geom_idx 
            ON directory_entries USING GIST(geom) 
            WHERE status = \'published\' AND geom IS NOT NULL
        ');
        
        // Composite index for category + location searches  
        DB::statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS directory_entries_category_geom_idx 
            ON directory_entries USING GIST(category_id, geom) 
            WHERE status = \'published\' AND category_id IS NOT NULL AND geom IS NOT NULL
        ');
        
        // 2. Indexes for region-based queries
        
        // Hierarchical region searches with location
        DB::statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS directory_entries_region_hierarchy_geom_idx 
            ON directory_entries (state_region_id, city_region_id, neighborhood_region_id, latitude, longitude) 
            WHERE status = \'published\' AND latitude IS NOT NULL
        ');
        
        // City-level location searches (very common)
        DB::statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS directory_entries_city_region_geom_idx 
            ON directory_entries USING GIST(city_region_id, geom) 
            WHERE status = \'published\' AND city_region_id IS NOT NULL AND geom IS NOT NULL
        ');
        
        // 3. Indexes for business/place specific queries
        
        // Featured places with location (for map highlights)
        DB::statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS directory_entries_featured_geom_idx 
            ON directory_entries USING GIST(geom) 
            WHERE status = \'published\' AND is_featured = true AND geom IS NOT NULL
        ');
        
        // Claimed/verified businesses with location
        DB::statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS directory_entries_verified_geom_idx 
            ON directory_entries USING GIST(geom) 
            WHERE status = \'published\' AND (is_verified = true OR is_claimed = true) AND geom IS NOT NULL
        ');
        
        // 4. Performance indexes for location data quality
        
        // Places needing geocoding/location updates
        DB::statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS directory_entries_needs_location_idx 
            ON directory_entries (location_updated_at, created_at) 
            WHERE status IN (\'published\', \'pending_review\') 
              AND (latitude IS NULL OR location_updated_at IS NULL OR location_updated_at < created_at)
        ');
        
        // 5. Bounding box queries (for map viewport searches)
        
        // Create a functional index for bounding box queries
        DB::statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS directory_entries_bbox_idx 
            ON directory_entries (latitude, longitude) 
            WHERE status = \'published\' AND latitude IS NOT NULL AND longitude IS NOT NULL
        ');
        
        // 6. Full-text search combined with location
        
        // Update existing full-text search to include location context
        DB::statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS directory_entries_search_with_location_idx 
            ON directory_entries USING GIN(
                to_tsvector(\'english\', 
                    COALESCE(title, \'\') || \' \' || 
                    COALESCE(description, \'\') || \' \' ||
                    COALESCE(city, \'\') || \' \' ||
                    COALESCE(state, \'\')
                )
            ) 
            WHERE status = \'published\' AND geom IS NOT NULL
        ');
        
        // 7. Indexes for the locations table performance
        
        // Enhanced locations table indexes
        DB::statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS locations_enhanced_geom_idx 
            ON locations USING GIST(geom) 
            WHERE geom IS NOT NULL
        ');
        
        // Geocoding status index for batch processing
        DB::statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS locations_geocoding_status_idx 
            ON locations (geocoded_at, location_source) 
            WHERE latitude IS NOT NULL
        ');
        
        // 8. Create materialized view for hot geospatial data
        
        DB::statement('
            CREATE MATERIALIZED VIEW IF NOT EXISTS directory_entries_geospatial_cache AS
            SELECT 
                id,
                title,
                slug,
                category_id,
                state_region_id,
                city_region_id,
                latitude,
                longitude,
                geom,
                status,
                is_featured,
                is_verified,
                is_claimed,
                published_at,
                location_updated_at
            FROM directory_entries 
            WHERE status = \'published\' 
              AND geom IS NOT NULL
            WITH DATA;
        ');
        
        // Index the materialized view
        DB::statement('
            CREATE UNIQUE INDEX directory_entries_geospatial_cache_pkey 
            ON directory_entries_geospatial_cache (id)
        ');
        
        DB::statement('
            CREATE INDEX directory_entries_geospatial_cache_geom_idx 
            ON directory_entries_geospatial_cache USING GIST(geom)
        ');
        
        DB::statement('
            CREATE INDEX directory_entries_geospatial_cache_category_geom_idx 
            ON directory_entries_geospatial_cache USING GIST(category_id, geom)
        ');
        
        // Create function to refresh the materialized view
        DB::statement('
            CREATE OR REPLACE FUNCTION refresh_geospatial_cache() 
            RETURNS void AS $$
            BEGIN
                REFRESH MATERIALIZED VIEW CONCURRENTLY directory_entries_geospatial_cache;
            END;
            $$ LANGUAGE plpgsql;
        ');
        
        // Analyze tables for optimal query planning
        DB::statement('ANALYZE directory_entries');
        DB::statement('ANALYZE locations');
        
        echo "Created comprehensive geospatial indexes and materialized view cache." . PHP_EOL;
        echo "Run 'SELECT refresh_geospatial_cache();' periodically to update cache." . PHP_EOL;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop materialized view and function
        DB::statement('DROP FUNCTION IF EXISTS refresh_geospatial_cache()');
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS directory_entries_geospatial_cache');
        
        // Drop all performance indexes
        $indexes = [
            'directory_entries_published_geom_idx',
            'directory_entries_category_geom_idx', 
            'directory_entries_region_hierarchy_geom_idx',
            'directory_entries_city_region_geom_idx',
            'directory_entries_featured_geom_idx',
            'directory_entries_verified_geom_idx',
            'directory_entries_needs_location_idx',
            'directory_entries_bbox_idx',
            'directory_entries_search_with_location_idx',
            'locations_enhanced_geom_idx',
            'locations_geocoding_status_idx'
        ];
        
        foreach ($indexes as $index) {
            DB::statement("DROP INDEX CONCURRENTLY IF EXISTS {$index}");
        }
        
        echo "Dropped all geospatial performance indexes." . PHP_EOL;
    }
};