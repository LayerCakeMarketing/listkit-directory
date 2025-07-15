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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('content');
            $table->json('media')->nullable(); // Store image/video URLs and metadata
            $table->string('media_type')->nullable(); // 'image', 'video', null
            $table->string('cloudflare_image_id')->nullable();
            $table->string('cloudflare_video_id')->nullable();
            $table->boolean('is_tacked')->default(false);
            $table->timestamp('tacked_at')->nullable();
            $table->integer('expires_in_days')->nullable(); // Days until expiration (null = never expires)
            $table->timestamp('expires_at')->nullable();
            $table->integer('likes_count')->default(0);
            $table->integer('replies_count')->default(0);
            $table->integer('shares_count')->default(0);
            $table->integer('views_count')->default(0);
            $table->json('metadata')->nullable(); // For future extensibility
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'is_tacked']);
            $table->index('expires_at');
            $table->index(['deleted_at', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};