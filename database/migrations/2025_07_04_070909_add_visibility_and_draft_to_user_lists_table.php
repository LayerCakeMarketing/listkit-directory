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
        Schema::table('lists', function (Blueprint $table) {
            // Change visibility from string to enum
            $table->dropColumn('visibility');
        });
        
        Schema::table('lists', function (Blueprint $table) {
            // Add visibility as enum
            $table->enum('visibility', ['public', 'unlisted', 'private'])
                  ->default('public')
                  ->after('description');
            
            // Add draft and publishing fields
            $table->boolean('is_draft')->default(false)->after('visibility');
            $table->timestamp('published_at')->nullable()->after('is_draft');
            $table->timestamp('scheduled_for')->nullable()->after('published_at');
            
            // Index for visibility queries
            $table->index(['visibility', 'is_draft', 'published_at']);
        });
        
        // Drop the old is_public column if it still exists
        if (Schema::hasColumn('lists', 'is_public')) {
            Schema::table('lists', function (Blueprint $table) {
                $table->dropColumn('is_public');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lists', function (Blueprint $table) {
            // Drop new columns
            $table->dropIndex(['visibility', 'is_draft', 'published_at']);
            $table->dropColumn(['is_draft', 'published_at', 'scheduled_for']);
            
            // Change visibility back to string
            $table->dropColumn('visibility');
        });
        
        Schema::table('lists', function (Blueprint $table) {
            // Add back visibility as string
            $table->string('visibility')->default('public')->after('description');
            
            // Add back is_public if needed
            if (!Schema::hasColumn('lists', 'is_public')) {
                $table->boolean('is_public')->default(true)->after('description');
            }
        });
    }
};