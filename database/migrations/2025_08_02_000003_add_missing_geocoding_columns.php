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
            // Add missing columns for geocoding
            if (!Schema::hasColumn('directory_entries', 'address_line1')) {
                $table->string('address_line1', 255)->nullable()->after('address');
            }
            if (!Schema::hasColumn('directory_entries', 'address_line2')) {
                $table->string('address_line2', 255)->nullable()->after('address_line1');
            }
            if (!Schema::hasColumn('directory_entries', 'zip_code')) {
                $table->string('zip_code', 20)->nullable()->after('state');
            }
            if (!Schema::hasColumn('directory_entries', 'country')) {
                $table->string('country', 2)->default('US')->after('zip_code');
            }
            if (!Schema::hasColumn('directory_entries', 'location_updated_at')) {
                $table->timestamp('location_updated_at')->nullable()->after('longitude');
            }
            if (!Schema::hasColumn('directory_entries', 'location_verified')) {
                $table->boolean('location_verified')->default(false)->after('location_updated_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('directory_entries', function (Blueprint $table) {
            $table->dropColumn([
                'address_line1', 
                'address_line2', 
                'zip_code', 
                'country',
                'location_updated_at',
                'location_verified'
            ]);
        });
    }
};