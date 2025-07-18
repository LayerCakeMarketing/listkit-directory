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
        // Check if we're using PostgreSQL
        if (config('database.default') === 'pgsql') {
            // Enable PostGIS extension if not already enabled
            DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');
            
            // Add spatial columns
            DB::statement('ALTER TABLE directory_entries ADD COLUMN IF NOT EXISTS coordinates geometry(Point, 4326)');
            
            // Create spatial index
            DB::statement('CREATE INDEX IF NOT EXISTS idx_directory_entries_coordinates ON directory_entries USING GIST(coordinates)');
            
            // Create function to update coordinates from location data
            DB::statement('
                CREATE OR REPLACE FUNCTION update_place_coordinates() RETURNS trigger AS $$
                BEGIN
                    IF NEW.latitude IS NOT NULL AND NEW.longitude IS NOT NULL THEN
                        UPDATE directory_entries 
                        SET coordinates = ST_SetSRID(ST_MakePoint(NEW.longitude, NEW.latitude), 4326)
                        WHERE id = NEW.directory_entry_id;
                    END IF;
                    RETURN NEW;
                END;
                $$ LANGUAGE plpgsql;
            ');
            
            // Create trigger to auto-update coordinates when location changes
            DB::statement('
                CREATE TRIGGER trigger_update_place_coordinates
                AFTER INSERT OR UPDATE OF latitude, longitude ON locations
                FOR EACH ROW
                EXECUTE FUNCTION update_place_coordinates();
            ');
            
            // Populate coordinates for existing entries
            DB::statement('
                UPDATE directory_entries d
                SET coordinates = ST_SetSRID(ST_MakePoint(l.longitude, l.latitude), 4326)
                FROM locations l
                WHERE d.id = l.directory_entry_id
                AND l.latitude IS NOT NULL 
                AND l.longitude IS NOT NULL
            ');
        }
        
        // Add non-spatial columns for all databases
        Schema::table('directory_entries', function (Blueprint $table) {
            if (!Schema::hasColumn('directory_entries', 'state_name')) {
                $table->string('state_name')->nullable()->index();
            }
            if (!Schema::hasColumn('directory_entries', 'city_name')) {
                $table->string('city_name')->nullable()->index();
            }
            if (!Schema::hasColumn('directory_entries', 'neighborhood_name')) {
                $table->string('neighborhood_name')->nullable();
            }
            if (!Schema::hasColumn('directory_entries', 'popularity_score')) {
                $table->integer('popularity_score')->default(0)->index();
            }
        });
        
        // Populate denormalized location names
        DB::statement('
            UPDATE directory_entries de
            SET 
                state_name = (SELECT full_name FROM regions WHERE id = de.state_region_id),
                city_name = (SELECT name FROM regions WHERE id = de.city_region_id),
                neighborhood_name = (SELECT name FROM regions WHERE id = de.neighborhood_region_id)
            WHERE de.state_region_id IS NOT NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        if (config('database.default') === 'pgsql') {
            // Drop trigger and function
            DB::statement('DROP TRIGGER IF EXISTS trigger_update_place_coordinates ON locations');
            DB::statement('DROP FUNCTION IF EXISTS update_place_coordinates()');
            
            // Drop spatial index
            DB::statement('DROP INDEX IF EXISTS idx_directory_entries_coordinates');
            
            // Drop spatial column
            DB::statement('ALTER TABLE directory_entries DROP COLUMN IF EXISTS coordinates');
        }
        
        Schema::table('directory_entries', function (Blueprint $table) {
            $table->dropColumn(['state_name', 'city_name', 'neighborhood_name', 'popularity_score']);
        });
    }
};