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
        Schema::create('marketing_pages', function (Blueprint $table) {
            $table->id();
            $table->string('page_key')->unique(); // 'places', 'lists', 'register'
            $table->string('heading')->nullable();
            $table->text('paragraph')->nullable();
            $table->string('cover_image_id')->nullable(); // Cloudflare image ID
            $table->string('cover_image_url')->nullable(); // Fallback URL
            $table->json('settings')->nullable(); // Additional settings if needed
            $table->timestamps();
        });
        
        // Insert default pages
        DB::table('marketing_pages')->insert([
            [
                'page_key' => 'places',
                'heading' => 'Discover Amazing Places',
                'paragraph' => 'Explore the best restaurants, shops, and attractions in your area. Find hidden gems and local favorites curated by our community.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'page_key' => 'lists',
                'heading' => 'Create and Share Lists',
                'paragraph' => 'Organize your favorite places into beautiful, shareable lists. Collaborate with friends and discover curated collections from experts.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'page_key' => 'register',
                'heading' => 'Join Our Community',
                'paragraph' => 'Sign up to save your favorite places, create lists, and connect with other local enthusiasts. It\'s free and only takes a moment.',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketing_pages');
    }
};