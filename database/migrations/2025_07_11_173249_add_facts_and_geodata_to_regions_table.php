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
        Schema::table('regions', function (Blueprint $table) {
            // Location facts and information
            $table->jsonb('facts')->nullable()->after('custom_fields');
            
            // State-specific symbols (only for state-level regions)
            $table->jsonb('state_symbols')->nullable()->after('facts');
            
            // Geodata fields
            $table->jsonb('geojson')->nullable()->after('state_symbols');
            $table->text('polygon_coordinates')->nullable()->after('geojson');
        });
        
        // Add GIN indexes for JSONB columns (PostgreSQL specific) - must be done after columns are created
        if (config('database.default') === 'pgsql') {
            DB::statement('CREATE INDEX regions_facts_gin ON regions USING GIN (facts)');
            DB::statement('CREATE INDEX regions_state_symbols_gin ON regions USING GIN (state_symbols)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes first (PostgreSQL specific)
        if (config('database.default') === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS regions_facts_gin');
            DB::statement('DROP INDEX IF EXISTS regions_state_symbols_gin');
        }
        
        Schema::table('regions', function (Blueprint $table) {
            // Drop columns
            $table->dropColumn(['facts', 'state_symbols', 'geojson', 'polygon_coordinates']);
        });
    }
};
