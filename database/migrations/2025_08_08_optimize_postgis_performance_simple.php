<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Simplified PostGIS optimizations for existing schema
     * Works with existing latitude/longitude columns
     */
    public function up(): void
    {
        // Create spatial index on regions boundary column if it exists
        $boundaryColumnExists = DB::select("
            SELECT column_name 
            FROM information_schema.columns 
            WHERE table_name = 'regions' 
            AND column_name = 'boundary'
        ");

        if ($boundaryColumnExists) {
            DB::statement('
                CREATE INDEX IF NOT EXISTS regions_boundary_spatial_idx 
                ON regions USING GIST(boundary) 
                WHERE boundary IS NOT NULL
            ');
        }

        // Create spatial index for directory_entries using lat/lng
        DB::statement('
            CREATE INDEX IF NOT EXISTS directory_entries_spatial_idx 
            ON directory_entries (latitude, longitude)
            WHERE latitude IS NOT NULL 
              AND longitude IS NOT NULL
              AND status = \'published\'
        ');

        // Create compound index for region + spatial queries
        DB::statement('
            CREATE INDEX IF NOT EXISTS directory_entries_region_spatial_idx 
            ON directory_entries (region_id, latitude, longitude)
            WHERE latitude IS NOT NULL 
              AND longitude IS NOT NULL
              AND status = \'published\'
        ');

        // Create index for park regions
        DB::statement('
            CREATE INDEX IF NOT EXISTS regions_park_type_idx 
            ON regions (type, id)
            WHERE type IN (\'national_park\', \'state_park\', \'regional_park\', \'local_park\')
        ');

        echo "Added simplified PostGIS performance optimizations." . PHP_EOL;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $indexes = [
            'regions_boundary_spatial_idx',
            'directory_entries_spatial_idx',
            'directory_entries_region_spatial_idx',
            'regions_park_type_idx'
        ];

        foreach ($indexes as $index) {
            DB::statement("DROP INDEX IF EXISTS {$index}");
        }

        echo "Removed simplified PostGIS performance optimizations." . PHP_EOL;
    }
};