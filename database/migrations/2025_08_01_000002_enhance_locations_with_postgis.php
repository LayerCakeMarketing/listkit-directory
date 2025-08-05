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
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            // Add geocoding cache fields
            $table->string('geocoded_address')->nullable()->after('country');
            $table->timestamp('geocoded_at')->nullable()->after('geocoded_address');
            $table->string('geocoding_provider', 50)->nullable()->after('geocoded_at');
            $table->json('geocoding_metadata')->nullable()->after('geocoding_provider');
            
            // Add geospatial accuracy fields
            $table->decimal('accuracy_meters', 8, 2)->nullable()->after('geocoding_metadata');
            $table->enum('location_source', ['user_input', 'geocoded', 'gps', 'manual_correction'])
                ->default('user_input')->after('accuracy_meters');
            
            // Add bounding box for complex geometries (future use)
            $table->decimal('bbox_north', 10, 7)->nullable()->after('location_source');
            $table->decimal('bbox_south', 10, 7)->nullable()->after('bbox_north');
            $table->decimal('bbox_east', 10, 7)->nullable()->after('bbox_south');
            $table->decimal('bbox_west', 10, 7)->nullable()->after('bbox_east');
            
            // Add timezone support for accurate business hours
            $table->string('timezone', 50)->nullable()->after('bbox_west');
        });
        
        // Add PostGIS geometry column
        DB::statement('ALTER TABLE locations ADD COLUMN geom geometry(Point, 4326)');
        
        // Create spatial index using GiST
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS locations_geom_gist ON locations USING GIST(geom)');
        
        // Create composite indexes for common query patterns
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS locations_city_state_idx ON locations (city, state) WHERE city IS NOT NULL AND state IS NOT NULL');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS locations_geocoded_at_idx ON locations (geocoded_at) WHERE geocoded_at IS NOT NULL');
        
        // Create function to automatically update geometry from lat/lng
        DB::statement('
            CREATE OR REPLACE FUNCTION update_location_geometry() 
            RETURNS trigger AS $$
            BEGIN
                -- Only update geometry if we have valid coordinates
                IF NEW.latitude IS NOT NULL AND NEW.longitude IS NOT NULL 
                   AND NEW.latitude BETWEEN -90 AND 90 
                   AND NEW.longitude BETWEEN -180 AND 180 THEN
                    NEW.geom = ST_SetSRID(ST_MakePoint(NEW.longitude, NEW.latitude), 4326);
                ELSE
                    NEW.geom = NULL;
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ');
        
        // Create trigger to automatically update geometry
        DB::statement('
            CREATE TRIGGER update_location_geometry_trigger
            BEFORE INSERT OR UPDATE ON locations
            FOR EACH ROW
            EXECUTE FUNCTION update_location_geometry();
        ');
        
        // Update existing records to populate geometry column
        DB::statement('
            UPDATE locations 
            SET geom = ST_SetSRID(ST_MakePoint(longitude, latitude), 4326)
            WHERE latitude IS NOT NULL 
              AND longitude IS NOT NULL 
              AND latitude BETWEEN -90 AND 90 
              AND longitude BETWEEN -180 AND 180;
        ');
        
        // Add constraint to ensure geometry is valid
        DB::statement('
            ALTER TABLE locations 
            ADD CONSTRAINT locations_geom_valid_check 
            CHECK (geom IS NULL OR ST_IsValid(geom));
        ');
        
        echo "Enhanced locations table with PostGIS support and updated " . 
             DB::table('locations')->whereNotNull('geom')->count() . " existing records." . PHP_EOL;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop triggers and functions
        DB::statement('DROP TRIGGER IF EXISTS update_location_geometry_trigger ON locations');
        DB::statement('DROP FUNCTION IF EXISTS update_location_geometry()');
        
        // Drop indexes
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS locations_geom_gist');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS locations_city_state_idx');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS locations_geocoded_at_idx');
        
        // Drop geometry column
        DB::statement('ALTER TABLE locations DROP COLUMN IF EXISTS geom');
        
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn([
                'geocoded_address',
                'geocoded_at', 
                'geocoding_provider',
                'geocoding_metadata',
                'accuracy_meters',
                'location_source',
                'bbox_north',
                'bbox_south', 
                'bbox_east',
                'bbox_west',
                'timezone'
            ]);
        });
    }
};