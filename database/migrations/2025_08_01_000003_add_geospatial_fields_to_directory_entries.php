<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration adds direct lat/lng columns to directory_entries table
     * for denormalized access and caching of frequently accessed location data.
     */
    public function up(): void
    {
        Schema::table('directory_entries', function (Blueprint $table) {
            // Add direct latitude/longitude columns for denormalized access
            $table->decimal('latitude', 10, 7)->nullable()->after('neighborhood');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            
            // Add address fields directly to directory_entries for quick access
            $table->string('address_line1')->nullable()->after('longitude');
            $table->string('address_line2')->nullable()->after('address_line1');
            $table->string('city')->nullable()->after('address_line2');
            $table->string('state', 2)->nullable()->after('city');
            $table->string('zip_code', 10)->nullable()->after('state');
            $table->string('country', 2)->default('US')->after('zip_code');
            
            // Add location metadata
            $table->timestamp('location_updated_at')->nullable()->after('country');
            $table->boolean('location_verified')->default(false)->after('location_updated_at');
        });
        
        // Create indexes for geospatial queries on directory_entries
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS directory_entries_lat_lng_idx ON directory_entries (latitude, longitude) WHERE latitude IS NOT NULL AND longitude IS NOT NULL');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS directory_entries_city_state_idx ON directory_entries (city, state) WHERE city IS NOT NULL AND state IS NOT NULL');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS directory_entries_location_verified_idx ON directory_entries (location_verified)');
        
        // Create composite index for common place queries with location
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS directory_entries_status_location_idx ON directory_entries (status, latitude, longitude) WHERE status = \'published\' AND latitude IS NOT NULL');
        
        // Add PostGIS geometry column to directory_entries for direct spatial queries
        DB::statement('ALTER TABLE directory_entries ADD COLUMN geom geometry(Point, 4326)');
        
        // Create spatial index
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS directory_entries_geom_gist ON directory_entries USING GIST(geom)');
        
        // Create function to update directory_entries geometry
        DB::statement('
            CREATE OR REPLACE FUNCTION update_directory_entry_geometry() 
            RETURNS trigger AS $$
            BEGIN
                -- Update geometry if we have valid coordinates
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
        ');
        
        // Create trigger
        DB::statement('
            CREATE TRIGGER update_directory_entry_geometry_trigger
            BEFORE INSERT OR UPDATE ON directory_entries
            FOR EACH ROW
            EXECUTE FUNCTION update_directory_entry_geometry();
        ');
        
        // Populate directory_entries with location data from locations table
        DB::statement('
            UPDATE directory_entries de
            SET 
                latitude = l.latitude,
                longitude = l.longitude,
                address_line1 = l.address_line1,
                address_line2 = l.address_line2,
                city = l.city,
                state = l.state,
                zip_code = l.zip_code,
                country = l.country,
                location_updated_at = l.updated_at,
                location_verified = CASE WHEN l.latitude IS NOT NULL AND l.longitude IS NOT NULL THEN true ELSE false END
            FROM locations l
            WHERE de.id = l.directory_entry_id
        ');
        
        // Update geometry for populated records
        DB::statement('
            UPDATE directory_entries 
            SET geom = ST_SetSRID(ST_MakePoint(longitude, latitude), 4326)
            WHERE latitude IS NOT NULL 
              AND longitude IS NOT NULL 
              AND latitude BETWEEN -90 AND 90 
              AND longitude BETWEEN -180 AND 180;
        ');
        
        echo "Migrated location data for " . 
             DB::table('directory_entries')->whereNotNull('latitude')->count() . 
             " directory entries." . PHP_EOL;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop triggers and functions
        DB::statement('DROP TRIGGER IF EXISTS update_directory_entry_geometry_trigger ON directory_entries');
        DB::statement('DROP FUNCTION IF EXISTS update_directory_entry_geometry()');
        
        // Drop indexes
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS directory_entries_geom_gist');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS directory_entries_lat_lng_idx');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS directory_entries_city_state_idx');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS directory_entries_location_verified_idx');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS directory_entries_status_location_idx');
        
        // Drop geometry column
        DB::statement('ALTER TABLE directory_entries DROP COLUMN IF EXISTS geom');
        
        Schema::table('directory_entries', function (Blueprint $table) {
            $table->dropColumn([
                'latitude',
                'longitude',
                'address_line1',
                'address_line2', 
                'city',
                'state',
                'zip_code',
                'country',
                'location_updated_at',
                'location_verified'
            ]);
        });
    }
};