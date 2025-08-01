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
        // Add counts to lists table
        Schema::table('lists', function (Blueprint $table) {
            $table->integer('likes_count')->default(0)->after('status');
            $table->integer('comments_count')->default(0)->after('likes_count');
            $table->integer('reposts_count')->default(0)->after('comments_count');
            
            $table->index('likes_count');
            $table->index('reposts_count');
        });
        
        // Add counts to directory_entries (places) table
        Schema::table('directory_entries', function (Blueprint $table) {
            $table->integer('likes_count')->default(0)->after('rejection_notes');
            $table->integer('comments_count')->default(0)->after('likes_count');
            
            $table->index('likes_count');
        });
        
        // Update posts table - it already has these counts but let's ensure they exist
        if (!Schema::hasColumn('posts', 'comments_count')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->integer('comments_count')->default(0)->after('likes_count');
                $table->integer('reposts_count')->default(0)->after('shares_count');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lists', function (Blueprint $table) {
            $table->dropColumn(['likes_count', 'comments_count', 'reposts_count']);
        });
        
        Schema::table('directory_entries', function (Blueprint $table) {
            $table->dropColumn(['likes_count', 'comments_count']);
        });
        
        if (Schema::hasColumn('posts', 'comments_count')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->dropColumn(['comments_count', 'reposts_count']);
            });
        }
    }
};