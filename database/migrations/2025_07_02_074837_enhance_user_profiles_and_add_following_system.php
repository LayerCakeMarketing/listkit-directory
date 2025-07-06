<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Enhance users table with additional profile fields
        Schema::table('users', function (Blueprint $table) {
            $table->string('display_title')->nullable()->after('name');
            $table->text('profile_description')->nullable()->after('bio');
            $table->string('location')->nullable()->after('profile_description');
            $table->string('website')->nullable()->after('location');
            $table->date('birth_date')->nullable()->after('website');
            $table->json('profile_settings')->nullable()->after('preferences');
            $table->boolean('show_activity')->default(true)->after('profile_settings');
            $table->boolean('show_followers')->default(true)->after('show_activity');
            $table->boolean('show_following')->default(true)->after('show_followers');
            $table->integer('profile_views')->default(0)->after('show_following');
        });

        // Create user_follows table for following other users
        Schema::create('user_follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('follower_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('following_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['follower_id', 'following_id']);
            $table->index(['follower_id']);
            $table->index(['following_id']);
        });

        // Create directory_entry_follows table for following directory entries
        Schema::create('directory_entry_follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('directory_entry_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['user_id', 'directory_entry_id']);
            $table->index(['user_id']);
            $table->index(['directory_entry_id']);
        });

        // Create pinned_lists table for pinning user lists to profile
        Schema::create('pinned_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('list_id')->constrained('lists')->onDelete('cascade');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->unique(['user_id', 'list_id']);
            $table->index(['user_id', 'sort_order']);
        });

        // Create user_activities table for activity feed
        Schema::create('user_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('activity_type'); // 'created_list', 'followed_user', 'followed_entry', etc.
            $table->morphs('subject'); // The thing being acted upon
            $table->json('metadata')->nullable(); // Additional activity data
            $table->boolean('is_public')->default(true);
            $table->timestamps();
            
            $table->index(['user_id', 'activity_type']);
            $table->index(['user_id', 'is_public', 'created_at']);
        });

        // Add featured field to lists table
        Schema::table('lists', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false)->after('view_count');
        });
    }

    public function down()
    {
        Schema::table('lists', function (Blueprint $table) {
            $table->dropColumn('is_featured');
        });

        Schema::dropIfExists('user_activities');
        Schema::dropIfExists('pinned_lists');
        Schema::dropIfExists('directory_entry_follows');
        Schema::dropIfExists('user_follows');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'display_title',
                'profile_description',
                'location',
                'website',
                'birth_date',
                'profile_settings',
                'show_activity',
                'show_followers',
                'show_following',
                'profile_views'
            ]);
        });
    }
};