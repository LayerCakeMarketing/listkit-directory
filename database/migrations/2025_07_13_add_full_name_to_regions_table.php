<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Add columns only if they don't exist
        if (!Schema::hasColumn('regions', 'full_name')) {
            Schema::table('regions', function (Blueprint $table) {
                $table->string('full_name')->nullable()->after('name');
                $table->index('full_name');
            });
        }
        
        if (!Schema::hasColumn('regions', 'abbreviation')) {
            Schema::table('regions', function (Blueprint $table) {
                $table->string('abbreviation', 10)->nullable()->after('slug');
            });
        }
        
        if (!Schema::hasColumn('regions', 'alternate_names')) {
            Schema::table('regions', function (Blueprint $table) {
                $table->jsonb('alternate_names')->nullable()->comment('Array of alternate names/spellings');
            });
        }
        
        // Add spatial fields only if they don't exist
        if (!Schema::hasColumn('regions', 'boundary')) {
            Schema::table('regions', function (Blueprint $table) {
                $table->geometry('boundary', 'polygon', 4326)->nullable();
                $table->geometry('center_point', 'point', 4326)->nullable();
                $table->decimal('area_sq_km', 10, 2)->nullable();
            });
        }
        
        // Add user-defined fields
        if (!Schema::hasColumn('regions', 'is_user_defined')) {
            Schema::table('regions', function (Blueprint $table) {
                $table->boolean('is_user_defined')->default(false);
                $table->unsignedBigInteger('created_by_user_id')->nullable();
                $table->foreign('created_by_user_id')->references('id')->on('users')->onDelete('set null');
            });
        }
        
        // Add performance field
        if (!Schema::hasColumn('regions', 'cache_updated_at')) {
            Schema::table('regions', function (Blueprint $table) {
                $table->timestamp('cache_updated_at')->nullable();
            });
        }
        
        // Add spatial index for boundary searches
        DB::statement('CREATE INDEX IF NOT EXISTS idx_regions_boundary ON regions USING GIST(boundary)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_regions_center_point ON regions USING GIST(center_point)');
        
        // Update existing state regions with full names
        $stateNames = [
            'al' => 'Alabama',
            'ak' => 'Alaska',
            'az' => 'Arizona',
            'ar' => 'Arkansas',
            'ca' => 'California',
            'co' => 'Colorado',
            'ct' => 'Connecticut',
            'de' => 'Delaware',
            'fl' => 'Florida',
            'ga' => 'Georgia',
            'hi' => 'Hawaii',
            'id' => 'Idaho',
            'il' => 'Illinois',
            'in' => 'Indiana',
            'ia' => 'Iowa',
            'ks' => 'Kansas',
            'ky' => 'Kentucky',
            'la' => 'Louisiana',
            'me' => 'Maine',
            'md' => 'Maryland',
            'ma' => 'Massachusetts',
            'mi' => 'Michigan',
            'mn' => 'Minnesota',
            'ms' => 'Mississippi',
            'mo' => 'Missouri',
            'mt' => 'Montana',
            'ne' => 'Nebraska',
            'nv' => 'Nevada',
            'nh' => 'New Hampshire',
            'nj' => 'New Jersey',
            'nm' => 'New Mexico',
            'ny' => 'New York',
            'nc' => 'North Carolina',
            'nd' => 'North Dakota',
            'oh' => 'Ohio',
            'ok' => 'Oklahoma',
            'or' => 'Oregon',
            'pa' => 'Pennsylvania',
            'ri' => 'Rhode Island',
            'sc' => 'South Carolina',
            'sd' => 'South Dakota',
            'tn' => 'Tennessee',
            'tx' => 'Texas',
            'ut' => 'Utah',
            'vt' => 'Vermont',
            'va' => 'Virginia',
            'wa' => 'Washington',
            'wv' => 'West Virginia',
            'wi' => 'Wisconsin',
            'wy' => 'Wyoming',
            'dc' => 'District of Columbia'
        ];
        
        foreach ($stateNames as $abbrev => $fullName) {
            DB::table('regions')
                ->where('slug', $abbrev)
                ->where('type', 'state')
                ->update([
                    'full_name' => $fullName,
                    'abbreviation' => strtoupper($abbrev)
                ]);
        }
        
        // For cities and neighborhoods, copy name to full_name initially
        DB::statement("UPDATE regions SET full_name = name WHERE full_name IS NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // Remove spatial indexes first
        DB::statement('DROP INDEX IF EXISTS idx_regions_boundary');
        DB::statement('DROP INDEX IF EXISTS idx_regions_center_point');
        
        // Drop columns if they exist
        $columnsToDrop = [];
        
        if (Schema::hasColumn('regions', 'full_name')) {
            $columnsToDrop[] = 'full_name';
        }
        if (Schema::hasColumn('regions', 'abbreviation')) {
            $columnsToDrop[] = 'abbreviation';
        }
        if (Schema::hasColumn('regions', 'alternate_names')) {
            $columnsToDrop[] = 'alternate_names';
        }
        if (Schema::hasColumn('regions', 'is_user_defined')) {
            $columnsToDrop[] = 'is_user_defined';
        }
        if (Schema::hasColumn('regions', 'created_by_user_id')) {
            $columnsToDrop[] = 'created_by_user_id';
        }
        if (Schema::hasColumn('regions', 'cache_updated_at')) {
            $columnsToDrop[] = 'cache_updated_at';
        }
        if (Schema::hasColumn('regions', 'boundary')) {
            $columnsToDrop[] = 'boundary';
        }
        if (Schema::hasColumn('regions', 'center_point')) {
            $columnsToDrop[] = 'center_point';
        }
        if (Schema::hasColumn('regions', 'area_sq_km')) {
            $columnsToDrop[] = 'area_sq_km';
        }
        
        if (!empty($columnsToDrop)) {
            Schema::table('regions', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }
    }
};