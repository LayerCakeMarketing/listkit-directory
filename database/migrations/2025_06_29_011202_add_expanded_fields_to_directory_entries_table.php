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
        Schema::table('directory_entries', function (Blueprint $table) {
            // Social Media Fields
            $table->string('facebook_url')->nullable();
            $table->string('instagram_handle')->nullable();
            $table->string('twitter_handle')->nullable();
            $table->string('youtube_channel')->nullable();
            $table->string('messenger_contact')->nullable();
            
            // Business Metadata
            $table->enum('price_range', ['$', '$$', '$$$', '$$$$'])->nullable();
            $table->boolean('takes_reservations')->nullable();
            $table->boolean('accepts_credit_cards')->nullable();
            $table->boolean('wifi_available')->nullable();
            $table->boolean('pet_friendly')->nullable();
            $table->enum('parking_options', ['street', 'lot', 'valet', 'none'])->nullable();
            $table->boolean('wheelchair_accessible')->nullable();
            $table->boolean('outdoor_seating')->nullable();
            $table->boolean('kid_friendly')->nullable();
            
            // Media Fields
            $table->json('video_urls')->nullable(); // Array of video URLs
            $table->json('pdf_files')->nullable(); // Array of PDF file paths
            
            // Operational Info
            $table->json('hours_of_operation')->nullable(); // JSON structure for weekly hours
            $table->json('special_hours')->nullable(); // JSON for holiday/special hours
            $table->boolean('temporarily_closed')->default(false);
            $table->boolean('open_24_7')->default(false);
            
            // Enhanced Location Fields (stored in locations table, these are for entry-level location metadata)
            $table->string('cross_streets')->nullable();
            $table->string('neighborhood')->nullable();
        });
        
        // Update locations table for enhanced address fields (only new fields)
        Schema::table('locations', function (Blueprint $table) {
            $table->string('cross_streets')->nullable();
            $table->string('neighborhood')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('directory_entries', function (Blueprint $table) {
            $table->dropColumn([
                'facebook_url', 'instagram_handle', 'twitter_handle', 
                'youtube_channel', 'messenger_contact',
                'price_range', 'takes_reservations', 'accepts_credit_cards', 
                'wifi_available', 'pet_friendly', 'parking_options',
                'wheelchair_accessible', 'outdoor_seating', 'kid_friendly',
                'video_urls', 'pdf_files',
                'hours_of_operation', 'special_hours', 'temporarily_closed', 'open_24_7',
                'cross_streets', 'neighborhood'
            ]);
        });
        
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['cross_streets', 'neighborhood']);
        });
    }
};