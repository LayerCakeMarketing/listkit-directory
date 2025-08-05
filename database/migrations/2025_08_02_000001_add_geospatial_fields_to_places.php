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
            if (!Schema::hasColumn('directory_entries', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('zip_code');
            }
            if (!Schema::hasColumn('directory_entries', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
            if (!Schema::hasColumn('directory_entries', 'address')) {
                $table->string('address')->nullable()->after('description');
            }
            
            // Add indexes for performance
            $table->index(['latitude', 'longitude'], 'idx_place_coordinates');
            $table->index(['status', 'latitude', 'longitude'], 'idx_place_status_coordinates');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('directory_entries', function (Blueprint $table) {
            $table->dropIndex('idx_place_coordinates');
            $table->dropIndex('idx_place_status_coordinates');
            
            $table->dropColumn(['latitude', 'longitude', 'address']);
        });
    }
};