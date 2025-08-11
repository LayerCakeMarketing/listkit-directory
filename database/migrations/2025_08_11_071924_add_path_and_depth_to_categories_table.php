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
        Schema::table('categories', function (Blueprint $table) {
            // Add columns if they don't exist
            if (!Schema::hasColumn('categories', 'path')) {
                $table->string('path', 500)->nullable()->index()->after('parent_id');
            }
            if (!Schema::hasColumn('categories', 'depth')) {
                $table->smallInteger('depth')->default(0)->index()->after('path');
            }
        });

        // Populate path and depth for existing categories
        $this->populateHierarchyData();

        // Add performance indexes
        DB::statement('CREATE INDEX IF NOT EXISTS idx_categories_parent_order ON categories (parent_id, order_index)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_categories_slug_parent ON categories (slug, parent_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_categories_path_pattern ON categories (path varchar_pattern_ops)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes first
        DB::statement('DROP INDEX IF EXISTS idx_categories_parent_order');
        DB::statement('DROP INDEX IF EXISTS idx_categories_slug_parent');
        DB::statement('DROP INDEX IF EXISTS idx_categories_path_pattern');

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['path', 'depth']);
        });
    }

    /**
     * Populate path and depth for existing categories
     */
    private function populateHierarchyData(): void
    {
        DB::transaction(function () {
            // Start with root categories
            $roots = DB::table('categories')->whereNull('parent_id')->get();
            
            foreach ($roots as $root) {
                $this->updateCategoryHierarchy($root->id, $root->slug, 0);
            }
        });
    }

    /**
     * Recursively update category hierarchy data
     */
    private function updateCategoryHierarchy($categoryId, $path, $depth): void
    {
        // Update current category
        DB::table('categories')
            ->where('id', $categoryId)
            ->update([
                'path' => $path,
                'depth' => $depth
            ]);

        // Update children
        $children = DB::table('categories')->where('parent_id', $categoryId)->get();
        
        foreach ($children as $child) {
            $childPath = $path . '/' . $child->slug;
            $this->updateCategoryHierarchy($child->id, $childPath, $depth + 1);
        }
    }
};