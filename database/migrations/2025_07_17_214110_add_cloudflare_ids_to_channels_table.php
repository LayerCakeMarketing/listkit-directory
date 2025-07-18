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
        Schema::table('channels', function (Blueprint $table) {
            $table->string('avatar_cloudflare_id')->nullable()->after('avatar_image');
            $table->string('banner_cloudflare_id')->nullable()->after('banner_image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->dropColumn(['avatar_cloudflare_id', 'banner_cloudflare_id']);
        });
    }
};
