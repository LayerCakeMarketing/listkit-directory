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
        Schema::create('uploaded_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('cloudflare_id')->unique();
            $table->enum('type', ['avatar', 'cover', 'list_image', 'entry_logo']);
            $table->unsignedBigInteger('entity_id')->nullable(); // ID of related entity (user, list, entry)
            $table->string('original_name');
            $table->integer('file_size'); // in bytes
            $table->string('mime_type');
            $table->json('variants')->nullable(); // Cloudflare variants URLs
            $table->json('meta')->nullable(); // Additional metadata
            $table->timestamps();
            
            $table->index(['user_id', 'type']);
            $table->index(['type', 'entity_id']);
        });

        Schema::create('image_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['avatar', 'cover', 'list_image', 'entry_logo']);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('temp_path'); // Path to temporary file
            $table->string('original_name');
            $table->integer('file_size');
            $table->string('mime_type');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->string('cloudflare_id')->nullable();
            $table->unsignedBigInteger('image_record_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('image_uploads');
        Schema::dropIfExists('uploaded_images');
    }
};
