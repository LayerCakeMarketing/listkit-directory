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
        Schema::table('tags', function (Blueprint $table) {
            // Add new columns if they don't exist
            if (!Schema::hasColumn('tags', 'type')) {
                $table->string('type')->default('general')->after('slug');
            }
            if (!Schema::hasColumn('tags', 'usage_count')) {
                $table->unsignedInteger('usage_count')->default(0)->after('description');
            }
            if (!Schema::hasColumn('tags', 'places_count')) {
                $table->unsignedInteger('places_count')->default(0)->after('usage_count');
            }
            if (!Schema::hasColumn('tags', 'lists_count')) {
                $table->unsignedInteger('lists_count')->default(0)->after('places_count');
            }
            if (!Schema::hasColumn('tags', 'posts_count')) {
                $table->unsignedInteger('posts_count')->default(0)->after('lists_count');
            }
            if (!Schema::hasColumn('tags', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('posts_count');
            }
            if (!Schema::hasColumn('tags', 'is_system')) {
                $table->boolean('is_system')->default(false)->after('is_featured');
            }
            if (!Schema::hasColumn('tags', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('is_system')->constrained('users')->nullOnDelete();
            }
            
            // Remove is_active if it exists (replaced by is_featured)
            if (Schema::hasColumn('tags', 'is_active')) {
                $table->dropColumn('is_active');
            }
            
            // Add indexes
            $table->index('type');
            $table->index('usage_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            if (Schema::hasColumn('tags', 'type')) {
                $table->dropColumn('type');
            }
            if (Schema::hasColumn('tags', 'usage_count')) {
                $table->dropColumn('usage_count');
            }
            if (Schema::hasColumn('tags', 'places_count')) {
                $table->dropColumn('places_count');
            }
            if (Schema::hasColumn('tags', 'lists_count')) {
                $table->dropColumn('lists_count');
            }
            if (Schema::hasColumn('tags', 'posts_count')) {
                $table->dropColumn('posts_count');
            }
            if (Schema::hasColumn('tags', 'is_featured')) {
                $table->dropColumn('is_featured');
            }
            if (Schema::hasColumn('tags', 'is_system')) {
                $table->dropColumn('is_system');
            }
            if (Schema::hasColumn('tags', 'created_by')) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            }
            
            // Re-add is_active if it was removed
            if (!Schema::hasColumn('tags', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
        });
    }
};