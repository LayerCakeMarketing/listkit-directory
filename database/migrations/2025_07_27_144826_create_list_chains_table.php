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
        Schema::create('list_chains', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('cover_cloudflare_id')->nullable();
            
            // Polymorphic owner (user or channel)
            $table->morphs('owner');
            
            // Visibility settings
            $table->enum('visibility', ['public', 'private', 'unlisted'])->default('private');
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            
            // Chain metadata
            $table->json('metadata')->nullable(); // For storing additional data like tags, settings
            $table->integer('views_count')->default(0);
            $table->integer('lists_count')->default(0);
            
            // Publishing
            $table->timestamp('published_at')->nullable();
            
            $table->timestamps();
            
            // Indexes with unique names
            $table->index(['owner_type', 'owner_id'], 'idx_list_chains_owner');
            $table->index(['visibility', 'status'], 'idx_list_chains_visibility_status');
            $table->index('slug', 'idx_list_chains_slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('list_chains');
    }
};
