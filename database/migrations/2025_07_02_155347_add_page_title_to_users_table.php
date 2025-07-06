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
        Schema::table('users', function (Blueprint $table) {
            $table->string('page_title')->nullable()->after('display_title');
            $table->text('custom_css')->nullable()->after('profile_description');
            $table->json('theme_settings')->nullable()->after('custom_css');
            $table->string('profile_color')->nullable()->after('theme_settings');
            $table->boolean('show_join_date')->default(true)->after('show_following');
            $table->boolean('show_location')->default(true)->after('show_join_date');
            $table->boolean('show_website')->default(true)->after('show_location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'page_title',
                'custom_css',
                'theme_settings',
                'profile_color',
                'show_join_date',
                'show_location',
                'show_website'
            ]);
        });
    }
};
