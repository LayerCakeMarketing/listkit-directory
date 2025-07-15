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
        // Helper function to check if index exists
        $indexExists = function($table, $indexName) {
            if (config('database.default') === 'pgsql') {
                $result = DB::select("SELECT 1 FROM pg_indexes WHERE tablename = ? AND indexname = ?", [$table, $indexName]);
                return !empty($result);
            }
            // For MySQL
            $result = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
            return !empty($result);
        };

        // Helper function to check if table exists
        $tableExists = function($table) {
            return Schema::hasTable($table);
        };

        // Add indexes for regions table
        if ($tableExists('regions')) {
            Schema::table('regions', function (Blueprint $table) use ($indexExists) {
                if (!$indexExists('regions', 'regions_slug_parent_id_index')) {
                    $table->index(['slug', 'parent_id']);
                }
                if (!$indexExists('regions', 'regions_slug_level_index')) {
                    $table->index(['slug', 'level']);
                }
                if (!$indexExists('regions', 'regions_type_level_index')) {
                    $table->index(['type', 'level']);
                }
                // Skip is_featured as it already exists
            });
        }

        // Add indexes for users table
        if ($tableExists('users')) {
            Schema::table('users', function (Blueprint $table) use ($indexExists) {
                if (!$indexExists('users', 'users_custom_url_index')) {
                    $table->index('custom_url');
                }
                if (!$indexExists('users', 'users_username_index')) {
                    $table->index('username');
                }
            });
        }

        // Add indexes for user_lists table (if it exists)
        if ($tableExists('user_lists')) {
            Schema::table('user_lists', function (Blueprint $table) use ($indexExists) {
                if (!$indexExists('user_lists', 'user_lists_user_id_slug_index')) {
                    $table->index(['user_id', 'slug']);
                }
                if (!$indexExists('user_lists', 'user_lists_visibility_is_draft_published_at_index')) {
                    $table->index(['visibility', 'is_draft', 'published_at']);
                }
                if (!$indexExists('user_lists', 'user_lists_is_featured_index')) {
                    $table->index('is_featured');
                }
            });
        }

        // Add indexes for categories table
        if ($tableExists('categories')) {
            Schema::table('categories', function (Blueprint $table) use ($indexExists) {
                if (!$indexExists('categories', 'categories_slug_parent_id_index')) {
                    $table->index(['slug', 'parent_id']);
                }
            });
        }

        // Add indexes for directory_entries table
        if ($tableExists('directory_entries')) {
            Schema::table('directory_entries', function (Blueprint $table) use ($indexExists) {
                if (!$indexExists('directory_entries', 'directory_entries_slug_category_id_status_index')) {
                    $table->index(['slug', 'category_id', 'status']);
                }
                if (!$indexExists('directory_entries', 'directory_entries_state_region_id_status_index')) {
                    $table->index(['state_region_id', 'status']);
                }
                if (!$indexExists('directory_entries', 'directory_entries_city_region_id_status_index')) {
                    $table->index(['city_region_id', 'status']);
                }
                if (!$indexExists('directory_entries', 'directory_entries_neighborhood_region_id_status_index')) {
                    $table->index(['neighborhood_region_id', 'status']);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Helper function to check if index exists
        $indexExists = function($table, $indexName) {
            if (config('database.default') === 'pgsql') {
                $result = DB::select("SELECT 1 FROM pg_indexes WHERE tablename = ? AND indexname = ?", [$table, $indexName]);
                return !empty($result);
            }
            // For MySQL
            $result = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
            return !empty($result);
        };

        // Remove indexes from regions table
        Schema::table('regions', function (Blueprint $table) use ($indexExists) {
            if ($indexExists('regions', 'regions_slug_parent_id_index')) {
                $table->dropIndex(['slug', 'parent_id']);
            }
            if ($indexExists('regions', 'regions_slug_level_index')) {
                $table->dropIndex(['slug', 'level']);
            }
            if ($indexExists('regions', 'regions_type_level_index')) {
                $table->dropIndex(['type', 'level']);
            }
        });

        // Remove indexes from users table
        Schema::table('users', function (Blueprint $table) use ($indexExists) {
            if ($indexExists('users', 'users_custom_url_index')) {
                $table->dropIndex(['custom_url']);
            }
            if ($indexExists('users', 'users_username_index')) {
                $table->dropIndex(['username']);
            }
        });

        // Remove indexes from user_lists table
        Schema::table('user_lists', function (Blueprint $table) use ($indexExists) {
            if ($indexExists('user_lists', 'user_lists_user_id_slug_index')) {
                $table->dropIndex(['user_id', 'slug']);
            }
            if ($indexExists('user_lists', 'user_lists_visibility_is_draft_published_at_index')) {
                $table->dropIndex(['visibility', 'is_draft', 'published_at']);
            }
            if ($indexExists('user_lists', 'user_lists_is_featured_index')) {
                $table->dropIndex(['is_featured']);
            }
        });

        // Remove indexes from categories table
        Schema::table('categories', function (Blueprint $table) use ($indexExists) {
            if ($indexExists('categories', 'categories_slug_parent_id_index')) {
                $table->dropIndex(['slug', 'parent_id']);
            }
        });

        // Remove indexes from directory_entries table
        Schema::table('directory_entries', function (Blueprint $table) use ($indexExists) {
            if ($indexExists('directory_entries', 'directory_entries_slug_category_id_status_index')) {
                $table->dropIndex(['slug', 'category_id', 'status']);
            }
            if ($indexExists('directory_entries', 'directory_entries_state_region_id_status_index')) {
                $table->dropIndex(['state_region_id', 'status']);
            }
            if ($indexExists('directory_entries', 'directory_entries_city_region_id_status_index')) {
                $table->dropIndex(['city_region_id', 'status']);
            }
            if ($indexExists('directory_entries', 'directory_entries_neighborhood_region_id_status_index')) {
                $table->dropIndex(['neighborhood_region_id', 'status']);
            }
        });
    }
};
