<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lists', function (Blueprint $table) {
            // Add fields for curated lists
            if (!Schema::hasColumn('lists', 'type')) {
                $table->string('type')->default('user')->after('user_id'); // 'user' or 'curated'
            }
            
            // Region specific
            if (!Schema::hasColumn('lists', 'is_region_specific')) {
                $table->boolean('is_region_specific')->default(false)->after('type');
            }
            if (!Schema::hasColumn('lists', 'region_id')) {
                $table->foreignId('region_id')->nullable()->after('is_region_specific')
                    ->constrained('regions')->onDelete('cascade');
            }
            
            // Category specific  
            // Note: category_id already exists from previous migration
            if (!Schema::hasColumn('lists', 'is_category_specific')) {
                $table->boolean('is_category_specific')->default(false)->after('category_id');
            }
            
            // Curated place IDs (stored as JSON array)
            if (!Schema::hasColumn('lists', 'place_ids')) {
                $table->json('place_ids')->nullable()->after('is_category_specific');
            }
            
            // Additional fields
            if (!Schema::hasColumn('lists', 'order_index')) {
                $table->integer('order_index')->default(0);
            }
            
            // Make user_id nullable for curated lists
            $table->unsignedBigInteger('user_id')->nullable()->change();
            
            // Add unique constraint on slug
            if (!Schema::hasColumn('lists', 'slug')) {
                $table->string('slug')->nullable()->after('name');
            }
        });
        
        // Only add indexes if columns exist
        Schema::table('lists', function (Blueprint $table) {
            // Check if indexes don't already exist
            $indexes = DB::select("SELECT indexname FROM pg_indexes WHERE tablename = 'lists'");
            $indexNames = array_column($indexes, 'indexname');
            
            if (!in_array('lists_type_is_region_specific_region_id_index', $indexNames)) {
                $table->index(['type', 'is_region_specific', 'region_id']);
            }
            if (!in_array('lists_type_is_category_specific_category_id_index', $indexNames)) {
                $table->index(['type', 'is_category_specific', 'category_id']);
            }
            if (Schema::hasColumn('lists', 'is_featured') && !in_array('lists_type_is_featured_index', $indexNames)) {
                $table->index(['type', 'is_featured']);
            }
            if (!in_array('lists_order_index_index', $indexNames)) {
                $table->index('order_index');
            }
        });
        
        // Update existing lists to be 'user' type if type column was just added
        if (Schema::hasColumn('lists', 'type')) {
            DB::table('lists')->whereNull('type')->update(['type' => 'user']);
        }
        
        // Make slug unique after updating existing records
        Schema::table('lists', function (Blueprint $table) {
            // Generate slugs for existing records
            $lists = DB::table('lists')->whereNull('slug')->get();
            foreach ($lists as $list) {
                $slug = Str::slug($list->name);
                $count = DB::table('lists')->where('slug', 'like', $slug . '%')->count();
                if ($count > 0) {
                    $slug = $slug . '-' . ($count + 1);
                }
                DB::table('lists')->where('id', $list->id)->update(['slug' => $slug]);
            }
            
            // Check if unique constraint doesn't already exist
            $constraints = DB::select("SELECT conname FROM pg_constraint WHERE conrelid = 'lists'::regclass AND contype = 'u'");
            $constraintNames = array_column($constraints, 'conname');
            
            if (!in_array('lists_slug_unique', $constraintNames)) {
                $table->unique('slug');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lists', function (Blueprint $table) {
            $table->dropIndex(['type', 'is_region_specific', 'region_id']);
            $table->dropIndex(['type', 'is_category_specific', 'category_id']);
            if (Schema::hasColumn('lists', 'is_featured')) {
                $table->dropIndex(['type', 'is_featured']);
            }
            $table->dropIndex(['order_index']);
            $table->dropUnique(['slug']);
            
            $table->dropForeign(['region_id']);
            
            $table->dropColumn([
                'type',
                'is_region_specific',
                'region_id',
                'is_category_specific',
                'place_ids',
                'order_index',
                'slug'
            ]);
            
            // Make user_id required again
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};