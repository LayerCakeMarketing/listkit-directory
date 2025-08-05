<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Enable PostGIS extension
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis_topology');
        
        // Verify PostGIS is installed correctly
        $version = DB::selectOne('SELECT PostGIS_Version()');
        if (!$version) {
            throw new Exception('PostGIS extension failed to install properly');
        }
        
        echo "PostGIS version installed: " . $version->postgis_version . PHP_EOL;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: We don't drop PostGIS extension as it might be used by other applications
        // DB::statement('DROP EXTENSION IF EXISTS postgis_topology');
        // DB::statement('DROP EXTENSION IF EXISTS postgis');
        
        echo "PostGIS extension left intact for safety. Manual removal required if needed." . PHP_EOL;
    }
};