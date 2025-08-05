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
        // Drop the existing constraint
        DB::statement('ALTER TABLE list_items DROP CONSTRAINT IF EXISTS list_items_type_check');
        
        // Add the new constraint with additional types
        DB::statement("ALTER TABLE list_items ADD CONSTRAINT list_items_type_check CHECK (type IN ('directory_entry', 'text', 'location', 'event', 'section', 'region', 'list'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the new constraint
        DB::statement('ALTER TABLE list_items DROP CONSTRAINT IF EXISTS list_items_type_check');
        
        // Restore the original constraint
        DB::statement("ALTER TABLE list_items ADD CONSTRAINT list_items_type_check CHECK (type IN ('directory_entry', 'text', 'location', 'event'))");
    }
};