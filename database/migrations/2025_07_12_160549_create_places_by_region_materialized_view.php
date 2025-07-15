<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Only create materialized view for PostgreSQL
        if (config('database.default') === 'pgsql') {
            // Create materialized view for fast region queries
            DB::statement('
                CREATE MATERIALIZED VIEW IF NOT EXISTS mv_places_by_region AS
                SELECT 
                    r.id as region_id,
                    r.slug as region_slug,
                    r.type as region_type,
                    r.full_name as region_name,
                    r.level as region_level,
                    p.id as place_id,
                    p.title,
                    p.slug as place_slug,
                    p.type as place_type,
                    p.coordinates,
                    p.popularity_score,
                    p.is_featured,
                    p.is_verified,
                    c.id as category_id,
                    c.name as category_name,
                    c.slug as category_slug,
                    pr.association_type,
                    pr.confidence_score
                FROM regions r
                INNER JOIN place_regions pr ON r.id = pr.region_id
                INNER JOIN directory_entries p ON pr.place_id = p.id
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.status = \'published\'
                ORDER BY r.id, p.popularity_score DESC, p.title
            ');
            
            // Create indexes on the materialized view
            DB::statement('CREATE INDEX idx_mv_places_region_slug ON mv_places_by_region(region_slug)');
            DB::statement('CREATE INDEX idx_mv_places_region_type ON mv_places_by_region(region_type)');
            DB::statement('CREATE INDEX idx_mv_places_region_level ON mv_places_by_region(region_level)');
            DB::statement('CREATE INDEX idx_mv_places_category ON mv_places_by_region(category_id)');
            DB::statement('CREATE INDEX idx_mv_places_featured ON mv_places_by_region(is_featured) WHERE is_featured = true');
            
            // Create function to refresh the materialized view
            DB::statement('
                CREATE OR REPLACE FUNCTION refresh_places_by_region_mv() RETURNS void AS $$
                BEGIN
                    REFRESH MATERIALIZED VIEW CONCURRENTLY mv_places_by_region;
                END;
                $$ LANGUAGE plpgsql;
            ');
            
            // Create a stored procedure for searching places by location
            DB::statement('
                CREATE OR REPLACE FUNCTION search_places_by_location(
                    search_lat FLOAT,
                    search_lng FLOAT,
                    radius_meters INT DEFAULT 5000,
                    category_filter INT DEFAULT NULL,
                    limit_results INT DEFAULT 50
                )
                RETURNS TABLE (
                    place_id BIGINT,
                    title VARCHAR,
                    distance FLOAT,
                    category_name VARCHAR,
                    state_name VARCHAR,
                    city_name VARCHAR
                ) AS $$
                BEGIN
                    RETURN QUERY
                    SELECT 
                        p.id,
                        p.title,
                        ST_Distance(
                            p.coordinates::geography,
                            ST_SetSRID(ST_MakePoint(search_lng, search_lat), 4326)::geography
                        ) as distance,
                        c.name as category_name,
                        p.state_name,
                        p.city_name
                    FROM directory_entries p
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.coordinates IS NOT NULL
                    AND p.status = \'published\'
                    AND (category_filter IS NULL OR p.category_id = category_filter)
                    AND ST_DWithin(
                        p.coordinates::geography,
                        ST_SetSRID(ST_MakePoint(search_lng, search_lat), 4326)::geography,
                        radius_meters
                    )
                    ORDER BY distance
                    LIMIT limit_results;
                END;
                $$ LANGUAGE plpgsql;
            ');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        if (config('database.default') === 'pgsql') {
            DB::statement('DROP FUNCTION IF EXISTS search_places_by_location');
            DB::statement('DROP FUNCTION IF EXISTS refresh_places_by_region_mv');
            DB::statement('DROP MATERIALIZED VIEW IF EXISTS mv_places_by_region');
        }
    }
};