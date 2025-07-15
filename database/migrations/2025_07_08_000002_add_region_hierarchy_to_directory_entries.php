<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('directory_entries', function (Blueprint $table) {
            // Add hierarchical region IDs for denormalized access
            $table->unsignedBigInteger('state_region_id')->nullable()->after('region_id');
            $table->unsignedBigInteger('city_region_id')->nullable()->after('state_region_id');
            $table->unsignedBigInteger('neighborhood_region_id')->nullable()->after('city_region_id');
            
            // Track when regions were last updated
            $table->timestamp('regions_updated_at')->nullable();
            
            // Add foreign key constraints
            $table->foreign('state_region_id')->references('id')->on('regions')->onDelete('set null');
            $table->foreign('city_region_id')->references('id')->on('regions')->onDelete('set null');
            $table->foreign('neighborhood_region_id')->references('id')->on('regions')->onDelete('set null');
            
            // Add indexes for efficient querying
            $table->index('state_region_id');
            $table->index('city_region_id');
            $table->index('neighborhood_region_id');
            $table->index(['state_region_id', 'city_region_id']);
            $table->index(['city_region_id', 'neighborhood_region_id']);
        });
        
        // Migrate existing region_id to appropriate level if regions already exist
        DB::statement('
            UPDATE directory_entries de
            SET state_region_id = CASE 
                WHEN r.level = 1 THEN de.region_id
                WHEN r.level = 2 AND pr.level = 1 THEN pr.id
                WHEN r.level = 3 AND pr.level = 2 AND ppr.level = 1 THEN ppr.id
                ELSE NULL
            END,
            city_region_id = CASE
                WHEN r.level = 2 THEN de.region_id
                WHEN r.level = 3 AND pr.level = 2 THEN pr.id
                ELSE NULL
            END,
            neighborhood_region_id = CASE
                WHEN r.level = 3 THEN de.region_id
                ELSE NULL
            END,
            regions_updated_at = NOW()
            FROM regions r
            LEFT JOIN regions pr ON r.parent_id = pr.id
            LEFT JOIN regions ppr ON pr.parent_id = ppr.id
            WHERE de.region_id = r.id
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('directory_entries', function (Blueprint $table) {
            $table->dropForeign(['state_region_id']);
            $table->dropForeign(['city_region_id']);
            $table->dropForeign(['neighborhood_region_id']);
            $table->dropColumn([
                'state_region_id',
                'city_region_id', 
                'neighborhood_region_id',
                'regions_updated_at'
            ]);
        });
    }
};