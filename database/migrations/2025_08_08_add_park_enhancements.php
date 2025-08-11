<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add minor enhancements to park support
     * Most fields already exist - this adds only missing park-specific features
     */
    public function up(): void
    {
        Schema::table('regions', function (Blueprint $table) {
            // Park capacity and visitor management
            $table->integer('max_daily_visitors')->nullable()->after('accessibility_features')
                ->comment('Maximum daily visitor capacity');
            
            // Weather and seasonal information
            $table->json('seasonal_info')->nullable()->after('max_daily_visitors')
                ->comment('Seasonal conditions, closures, best visit times');
            
            // Emergency and safety information
            $table->json('safety_info')->nullable()->after('seasonal_info')
                ->comment('Emergency contacts, safety warnings, medical facilities');
            
            // Permit and reservation systems
            $table->json('permit_requirements')->nullable()->after('safety_info')
                ->comment('Required permits, advance booking systems');
        });

        // Add index for park capacity queries (useful for crowding predictions)
        // Note: Cannot use CONCURRENTLY inside migrations
        DB::statement('CREATE INDEX IF NOT EXISTS regions_park_capacity_idx 
                      ON regions (max_daily_visitors) 
                      WHERE type IN (\'national_park\', \'state_park\', \'regional_park\', \'local_park\') 
                        AND max_daily_visitors IS NOT NULL');

        // Add spatial index specifically optimized for park boundary queries
        DB::statement('CREATE INDEX IF NOT EXISTS regions_park_boundaries_idx 
                      ON regions USING GIST(boundary) 
                      WHERE type IN (\'national_park\', \'state_park\', \'regional_park\', \'local_park\') 
                        AND boundary IS NOT NULL');

        echo "Added enhanced park support fields and optimized indexes." . PHP_EOL;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes
        DB::statement('DROP INDEX IF EXISTS regions_park_capacity_idx');
        DB::statement('DROP INDEX IF EXISTS regions_park_boundaries_idx');

        Schema::table('regions', function (Blueprint $table) {
            $table->dropColumn([
                'max_daily_visitors',
                'seasonal_info',
                'safety_info', 
                'permit_requirements'
            ]);
        });
    }
};