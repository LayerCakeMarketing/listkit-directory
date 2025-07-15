<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, add new columns to the existing regions table
        Schema::table('regions', function (Blueprint $table) {
            $table->integer('level')->default(0)->after('type'); // 0=country, 1=state, 2=city, 3=neighborhood
            $table->string('slug')->nullable()->after('name');
            $table->jsonb('metadata')->nullable(); // Store additional region data
            $table->integer('cached_place_count')->default(0);
            $table->index('slug');
            $table->index('level');
            $table->index(['parent_id', 'level']);
        });
        
        // Add PostGIS geometry columns if using PostgreSQL
        // TODO: Enable this after installing PostGIS extension
        // if (config('database.default') === 'pgsql') {
        //     // Enable PostGIS extension if not already enabled
        //     DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');
        //     
        //     // Add geometry columns for region boundaries
        //     DB::statement('ALTER TABLE regions ADD COLUMN boundaries geometry(Polygon, 4326)');
        //     DB::statement('ALTER TABLE regions ADD COLUMN boundaries_simplified geometry(Polygon, 4326)');
        //     
        //     // Create spatial indexes
        //     DB::statement('CREATE INDEX regions_boundaries_gist ON regions USING GIST(boundaries)');
        //     DB::statement('CREATE INDEX regions_boundaries_simplified_gist ON regions USING GIST(boundaries_simplified)');
        //     
        //     // Add centroid point for quick distance calculations
        //     DB::statement('ALTER TABLE regions ADD COLUMN centroid geography(Point, 4326)');
        //     DB::statement('CREATE INDEX regions_centroid_gist ON regions USING GIST(centroid)');
        //     
        //     // Create function to automatically calculate centroid
        //     DB::statement('
        //         CREATE OR REPLACE FUNCTION update_region_centroid() RETURNS trigger AS $$
        //         BEGIN
        //             IF NEW.boundaries IS NOT NULL THEN
        //                 NEW.centroid = ST_Centroid(NEW.boundaries)::geography;
        //             END IF;
        //             RETURN NEW;
        //         END;
        //         $$ LANGUAGE plpgsql;
        //     ');
        //     
        //     // Create trigger to update centroid when boundaries change
        //     DB::statement('
        //         CREATE TRIGGER update_region_centroid_trigger
        //         BEFORE INSERT OR UPDATE OF boundaries ON regions
        //         FOR EACH ROW
        //         EXECUTE FUNCTION update_region_centroid();
        //     ');
        // }
        
        // Update existing regions to set appropriate levels based on type
        DB::table('regions')->where('type', 'state')->update(['level' => 1]);
        DB::table('regions')->where('type', 'city')->update(['level' => 2]);
        DB::table('regions')->where('type', 'county')->update(['level' => 2]);
        DB::table('regions')->where('type', 'custom')->update(['level' => 3]);
        
        // Generate slugs for existing regions
        $regions = DB::table('regions')->get();
        foreach ($regions as $region) {
            $slug = Str::slug($region->name);
            // Handle duplicates
            $count = DB::table('regions')
                ->where('slug', 'like', $slug . '%')
                ->where('id', '!=', $region->id)
                ->count();
            if ($count > 0) {
                $slug = $slug . '-' . ($count + 1);
            }
            DB::table('regions')->where('id', $region->id)->update(['slug' => $slug]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // TODO: Enable this after installing PostGIS extension
        // if (config('database.default') === 'pgsql') {
        //     DB::statement('DROP TRIGGER IF EXISTS update_region_centroid_trigger ON regions');
        //     DB::statement('DROP FUNCTION IF EXISTS update_region_centroid()');
        //     DB::statement('ALTER TABLE regions DROP COLUMN IF EXISTS centroid');
        //     DB::statement('ALTER TABLE regions DROP COLUMN IF EXISTS boundaries_simplified');
        //     DB::statement('ALTER TABLE regions DROP COLUMN IF EXISTS boundaries');
        // }
        
        Schema::table('regions', function (Blueprint $table) {
            $table->dropColumn(['level', 'slug', 'metadata', 'cached_place_count']);
        });
    }
};