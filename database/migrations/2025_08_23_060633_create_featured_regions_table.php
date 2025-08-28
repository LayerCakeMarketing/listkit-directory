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
        Schema::create('featured_regions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained('regions')->onDelete('cascade');
            $table->foreignId('featured_region_id')->constrained('regions')->onDelete('cascade');
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('label')->nullable(); // Optional label like "Must Visit", "Popular Destination"
            $table->text('description')->nullable(); // Optional short description for why it's featured
            $table->json('custom_data')->nullable(); // For any additional data
            $table->timestamp('featured_until')->nullable(); // Optional expiration date
            $table->timestamps();
            
            // Composite unique index to prevent duplicate featured regions
            $table->unique(['region_id', 'featured_region_id']);
            
            // Indexes for performance
            $table->index(['region_id', 'is_active', 'display_order']);
            $table->index('featured_until');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('featured_regions');
    }
};