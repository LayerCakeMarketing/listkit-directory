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
        Schema::create('local_page_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('page_type')->default('index'); // 'index', 'state', 'city', 'neighborhood'
            $table->string('title')->nullable();
            $table->text('intro_text')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('featured_lists')->nullable(); // Array of list IDs with order
            $table->json('featured_places')->nullable(); // Array of place IDs with order
            $table->json('content_sections')->nullable(); // Custom content sections
            $table->json('settings')->nullable(); // Additional settings
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Index for quick lookups
            $table->index(['region_id', 'page_type']);
            $table->index('page_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('local_page_settings');
    }
};
