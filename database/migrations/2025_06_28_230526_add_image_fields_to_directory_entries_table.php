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
        Schema::table('directory_entries', function (Blueprint $table) {
            $table->string('logo_url')->nullable()->after('website_url');
            $table->string('cover_image_url')->nullable()->after('logo_url');
            // gallery_images is already in the table, no need to add it again
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('directory_entries', function (Blueprint $table) {
            $table->dropColumn(['logo_url', 'cover_image_url']);
        });
    }
};