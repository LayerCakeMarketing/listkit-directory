<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Add composite indexes for the new URL routing patterns (without CONCURRENTLY for migrations)
        DB::statement('
            CREATE INDEX IF NOT EXISTS categories_url_routing_idx 
            ON categories (parent_id, slug, order_index) 
            WHERE parent_id IS NULL
        ');

        DB::statement('
            CREATE INDEX IF NOT EXISTS categories_hierarchy_lookup_idx 
            ON categories (path, depth) 
        ');

        DB::statement('
            CREATE INDEX IF NOT EXISTS categories_parent_child_lookup_idx 
            ON categories (parent_id, order_index, slug) 
            WHERE parent_id IS NOT NULL
        ');

        // Add covering index for place category lookups
        DB::statement('
            CREATE INDEX IF NOT EXISTS directory_entries_category_region_status_idx 
            ON directory_entries (category_id, city_region_id, status)
        ');

        // Add index for places by multiple region types
        DB::statement('
            CREATE INDEX IF NOT EXISTS directory_entries_multi_region_idx 
            ON directory_entries (region_id, city_region_id, state_region_id, status)
        ');

        // Statistics for query optimization
        DB::statement('ANALYZE categories');
        DB::statement('ANALYZE directory_entries');
    }

    public function down()
    {
        DB::statement('DROP INDEX IF EXISTS categories_url_routing_idx');
        DB::statement('DROP INDEX IF EXISTS categories_hierarchy_lookup_idx');
        DB::statement('DROP INDEX IF EXISTS categories_parent_child_lookup_idx');
        DB::statement('DROP INDEX IF EXISTS directory_entries_category_region_status_idx');
        DB::statement('DROP INDEX IF EXISTS directory_entries_multi_region_idx');
    }
};