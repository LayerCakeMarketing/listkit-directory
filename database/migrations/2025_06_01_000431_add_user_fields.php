<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->nullable()->after('name');
            $table->enum('role', ['admin', 'manager', 'editor', 'business_owner', 'user'])->default('user')->after('email');
            $table->text('bio')->nullable();
            $table->string('avatar')->nullable();
            $table->string('cover_image')->nullable();
            $table->json('social_links')->nullable();
            $table->json('preferences')->nullable();
            $table->json('permissions')->nullable(); // For granular permissions
            $table->boolean('is_public')->default(true);
            $table->timestamp('last_active_at')->nullable();
            
            $table->index('username');
            $table->index('role');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'username', 'role', 'bio', 'avatar', 'cover_image',
                'social_links', 'preferences', 'permissions', 'is_public', 'last_active_at'
            ]);
        });
    }
};