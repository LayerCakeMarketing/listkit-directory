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
     * This migration adds support for park region types:
     * - national_park
     * - state_park
     * - regional_park
     * - local_park
     * 
     * Parks are treated as special regions that can exist at various hierarchical levels
     * and may not follow the traditional state > city > neighborhood hierarchy.
     */
    public function up(): void
    {
        DB::transaction(function () {
            $this->logMessage("Adding park region type support...");

            // First drop the existing type constraint
            DB::statement('ALTER TABLE regions DROP CONSTRAINT IF EXISTS regions_type_check');
            
            // Add the new constraint with park types included
            DB::statement("ALTER TABLE regions ADD CONSTRAINT regions_type_check CHECK (type IN ('state', 'city', 'county', 'neighborhood', 'district', 'custom', 'national_park', 'state_park', 'regional_park', 'local_park'))");

            // Add new columns specific to parks
            Schema::table('regions', function (Blueprint $table) {
                // Park-specific fields
                $table->string('park_system')->nullable()->after('type')->comment('Park system (e.g., National Park Service, California State Parks)');
                $table->string('park_designation')->nullable()->after('park_system')->comment('Official designation (e.g., National Park, State Recreation Area)');
                $table->decimal('area_acres', 12, 2)->nullable()->after('area_sq_km')->comment('Park area in acres');
                $table->date('established_date')->nullable()->after('area_acres')->comment('Date park was established');
                $table->json('park_features')->nullable()->after('established_date')->comment('Park features (trails, camping, etc.)');
                $table->json('park_activities')->nullable()->after('park_features')->comment('Available activities');
                $table->string('park_website')->nullable()->after('park_activities')->comment('Official park website');
                $table->string('park_phone')->nullable()->after('park_website')->comment('Park contact phone');
                $table->json('operating_hours')->nullable()->after('park_phone')->comment('Park operating hours by season');
                $table->json('entrance_fees')->nullable()->after('operating_hours')->comment('Entrance fees and passes');
                $table->boolean('reservations_required')->default(false)->after('entrance_fees')->comment('Whether reservations are required');
                $table->string('difficulty_level')->nullable()->after('reservations_required')->comment('General difficulty level (easy, moderate, difficult)');
                $table->json('accessibility_features')->nullable()->after('difficulty_level')->comment('Accessibility accommodations');
            });

            // Add indexes for park-related queries
            Schema::table('regions', function (Blueprint $table) {
                $table->index(['type', 'park_system'], 'regions_type_park_system_index');
                $table->index(['park_designation'], 'regions_park_designation_index');
                $table->index(['established_date'], 'regions_established_date_index');
                $table->index(['reservations_required'], 'regions_reservations_required_index');
            });

            $this->logMessage("Park region type support added successfully!");
            
            // Create sample park regions for demonstration
            $this->createSampleParkRegions();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->logMessage("Removing park region type support...");

        Schema::table('regions', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('regions_type_park_system_index');
            $table->dropIndex('regions_park_designation_index');
            $table->dropIndex('regions_established_date_index');
            $table->dropIndex('regions_reservations_required_index');

            // Drop park-specific columns
            $table->dropColumn([
                'park_system',
                'park_designation',
                'area_acres',
                'established_date',
                'park_features',
                'park_activities',
                'park_website',
                'park_phone',
                'operating_hours',
                'entrance_fees',
                'reservations_required',
                'difficulty_level',
                'accessibility_features'
            ]);
        });

        $this->logMessage("Park region type support removed successfully!");
    }

    /**
     * Create sample park regions for demonstration
     */
    private function createSampleParkRegions(): void
    {
        $this->logMessage("Creating sample park regions...");

        // Find California region
        $california = DB::table('regions')
            ->where('name', 'California')
            ->where('type', 'state')
            ->first();

        if (!$california) {
            $this->logMessage("California region not found, skipping sample creation");
            return;
        }

        $sampleParks = [
            [
                'name' => 'Yosemite National Park',
                'slug' => 'yosemite-national-park',
                'type' => 'national_park',
                'parent_id' => $california->id,
                'level' => 2, // Same level as cities
                'park_system' => 'National Park Service',
                'park_designation' => 'National Park',
                'area_acres' => 748436.00,
                'established_date' => '1890-10-01',
                'park_features' => json_encode([
                    'waterfalls', 'granite_cliffs', 'giant_sequoias', 'wilderness_areas'
                ]),
                'park_activities' => json_encode([
                    'hiking', 'rock_climbing', 'photography', 'camping', 'wildlife_viewing'
                ]),
                'park_website' => 'https://www.nps.gov/yose/',
                'park_phone' => '(209) 372-0200',
                'operating_hours' => json_encode([
                    'year_round' => '24 hours',
                    'visitor_centers' => 'Seasonal hours vary'
                ]),
                'entrance_fees' => json_encode([
                    'vehicle_7_day' => 35.00,
                    'individual_7_day' => 20.00,
                    'annual_pass' => 70.00
                ]),
                'reservations_required' => true,
                'difficulty_level' => 'varies',
                'accessibility_features' => json_encode([
                    'accessible_trails', 'accessible_restrooms', 'accessible_parking'
                ])
            ],
            [
                'name' => 'Big Sur State Park',
                'slug' => 'big-sur-state-park',
                'type' => 'state_park',
                'parent_id' => $california->id,
                'level' => 2,
                'park_system' => 'California State Parks',
                'park_designation' => 'State Park',
                'area_acres' => 4800.00,
                'established_date' => '1934-01-01',
                'park_features' => json_encode([
                    'redwood_groves', 'hiking_trails', 'river_access', 'camping'
                ]),
                'park_activities' => json_encode([
                    'hiking', 'camping', 'fishing', 'wildlife_viewing', 'photography'
                ]),
                'park_website' => 'https://www.parks.ca.gov/',
                'park_phone' => '(831) 667-2315',
                'operating_hours' => json_encode([
                    'daily' => '8:00 AM - sunset'
                ]),
                'entrance_fees' => json_encode([
                    'day_use' => 10.00,
                    'camping' => 35.00
                ]),
                'reservations_required' => false,
                'difficulty_level' => 'moderate',
                'accessibility_features' => json_encode([
                    'accessible_campsites', 'accessible_restrooms'
                ])
            ]
        ];

        foreach ($sampleParks as $park) {
            $park['created_at'] = now();
            $park['updated_at'] = now();
            $park['cached_place_count'] = 0;
            $park['is_featured'] = false;
            $park['display_priority'] = 0;
            $park['is_user_defined'] = false;

            DB::table('regions')->insert($park);
            $this->logMessage("Created sample park: {$park['name']}");
        }
    }

    /**
     * Log migration messages
     */
    private function logMessage(string $message): void
    {
        echo "[" . date('Y-m-d H:i:s') . "] " . $message . "\n";
    }
};