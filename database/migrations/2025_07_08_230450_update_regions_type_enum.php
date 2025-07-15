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
        // For PostgreSQL, we need to update the enum type
        if (config('database.default') === 'pgsql') {
            // First, remove the check constraint if it exists
            DB::statement('ALTER TABLE regions DROP CONSTRAINT IF EXISTS regions_type_check');
            
            // Add the new enum values
            DB::statement("ALTER TABLE regions ADD CONSTRAINT regions_type_check CHECK (type IN ('state', 'city', 'county', 'neighborhood', 'district', 'custom'))");
        } else {
            // For MySQL, we need to modify the column
            DB::statement("ALTER TABLE regions MODIFY COLUMN type ENUM('state', 'city', 'county', 'neighborhood', 'district', 'custom') DEFAULT 'custom'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values
        if (config('database.default') === 'pgsql') {
            DB::statement('ALTER TABLE regions DROP CONSTRAINT IF EXISTS regions_type_check');
            DB::statement("ALTER TABLE regions ADD CONSTRAINT regions_type_check CHECK (type IN ('city', 'county', 'state', 'custom'))");
        } else {
            DB::statement("ALTER TABLE regions MODIFY COLUMN type ENUM('city', 'county', 'state', 'custom') DEFAULT 'custom'");
        }
    }
};