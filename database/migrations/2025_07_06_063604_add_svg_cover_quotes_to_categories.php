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
        Schema::table('categories', function (Blueprint $table) {
            // SVG icon field (store raw SVG markup)
            $table->text('svg_icon')->nullable()->after('icon');
            
            // Cover image fields (using Cloudflare)
            $table->string('cover_image_cloudflare_id')->nullable()->after('svg_icon');
            $table->string('cover_image_url')->nullable()->after('cover_image_cloudflare_id');
            
            // Quotes field (JSON array of quote strings)
            $table->json('quotes')->nullable()->after('cover_image_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn([
                'svg_icon',
                'cover_image_cloudflare_id',
                'cover_image_url',
                'quotes'
            ]);
        });
    }
};