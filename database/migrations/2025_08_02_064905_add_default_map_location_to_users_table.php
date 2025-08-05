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
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('default_map_latitude', 10, 7)->nullable()->after('default_region_id');
            $table->decimal('default_map_longitude', 10, 7)->nullable()->after('default_map_latitude');
            $table->integer('default_map_zoom')->default(12)->after('default_map_longitude');
            $table->string('default_map_location_name')->nullable()->after('default_map_zoom');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'default_map_latitude',
                'default_map_longitude', 
                'default_map_zoom',
                'default_map_location_name'
            ]);
        });
    }
};
