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
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('cloudflare_id')->unique();
            $table->string('url');
            $table->string('filename');
            $table->string('mime_type')->nullable();
            $table->integer('file_size')->nullable();
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            
            // Polymorphic relationship for entity association
            $table->string('entity_type')->nullable()->index();
            $table->unsignedBigInteger('entity_id')->nullable()->index();
            
            // User who uploaded
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Context for the upload
            $table->enum('context', [
                'avatar',
                'cover',
                'gallery',
                'logo',
                'item',
                'post',
                'region_cover',
                'avatar_temp',
                'banner_temp',
                'banner',
                'marketing',
                'place_image',
                'list_cover',
                'channel_avatar',
                'channel_cover'
            ])->nullable()->index();
            
            // Additional metadata
            $table->json('metadata')->nullable();
            
            // Status for moderation
            $table->enum('status', ['pending', 'approved', 'rejected', 'deleted'])->default('approved');
            
            // Soft deletes
            $table->softDeletes();
            
            $table->timestamps();
            
            // Composite index for polymorphic queries
            $table->index(['entity_type', 'entity_id']);
            $table->index(['user_id', 'context']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};