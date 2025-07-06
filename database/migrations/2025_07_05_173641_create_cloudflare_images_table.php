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
        Schema::create('cloudflare_images', function (Blueprint $table) {
            $table->id();
            $table->string('cloudflare_id')->unique(); // Cloudflare image ID
            $table->string('filename');
            $table->unsignedBigInteger('user_id')->nullable(); // User who uploaded
            $table->string('context')->nullable(); // avatar, cover, gallery, logo, etc.
            $table->string('entity_type')->nullable(); // User, DirectoryEntry, UserList, etc.
            $table->unsignedBigInteger('entity_id')->nullable(); // ID of related entity
            $table->json('metadata')->nullable(); // Original metadata from Cloudflare
            $table->unsignedBigInteger('file_size')->nullable(); // File size in bytes
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->string('mime_type')->nullable();
            $table->timestamp('uploaded_at');
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['user_id']);
            $table->index(['context']);
            $table->index(['entity_type', 'entity_id']);
            $table->index(['uploaded_at']);
            
            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cloudflare_images');
    }
};
