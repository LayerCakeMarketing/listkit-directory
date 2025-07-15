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
        Schema::table('regions', function (Blueprint $table) {
            // Media and content fields
            $table->string('cover_image')->nullable()->after('cached_place_count');
            $table->text('intro_text')->nullable()->after('cover_image');
            $table->jsonb('data_points')->nullable()->after('intro_text');
            $table->boolean('is_featured')->default(false)->after('data_points');
            
            // SEO and meta fields
            $table->string('meta_title')->nullable()->after('is_featured');
            $table->text('meta_description')->nullable()->after('meta_title');
            
            // Additional metadata
            $table->jsonb('custom_fields')->nullable()->after('meta_description');
            $table->integer('display_priority')->default(0)->after('custom_fields');
            
            // Indexes
            $table->index('is_featured');
            $table->index('display_priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('regions', function (Blueprint $table) {
            $table->dropColumn([
                'cover_image',
                'intro_text',
                'data_points',
                'is_featured',
                'meta_title',
                'meta_description',
                'custom_fields',
                'display_priority'
            ]);
        });
    }
};