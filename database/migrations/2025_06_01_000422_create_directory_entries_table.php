<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('directory_entries', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['physical_location', 'online_business', 'service', 'event', 'resource'])->default('physical_location');
            
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('region_id')->nullable();
            $table->json('tags')->nullable();
            
            $table->unsignedBigInteger('owner_user_id')->nullable();
            $table->unsignedBigInteger('created_by_user_id');
            $table->unsignedBigInteger('updated_by_user_id')->nullable();
            
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website_url')->nullable();
            $table->json('social_links')->nullable();
            
            $table->string('featured_image')->nullable();
            $table->json('gallery_images')->nullable();
            
            $table->enum('status', ['draft', 'pending_review', 'published', 'archived'])->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_claimed')->default(false);
            
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('structured_data')->nullable();
            
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('list_count')->default(0);
            
            $table->timestamps();
            $table->timestamp('published_at')->nullable();
            
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            $table->foreign('region_id')->references('id')->on('regions')->onDelete('set null');
            $table->foreign('owner_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by_user_id')->references('id')->on('users');
            $table->foreign('updated_by_user_id')->references('id')->on('users')->onDelete('set null');
            
            $table->index(['status', 'is_featured']);
            $table->index('type');
        });
    }

    public function down()
    {
        Schema::dropIfExists('directory_entries');
    }
};