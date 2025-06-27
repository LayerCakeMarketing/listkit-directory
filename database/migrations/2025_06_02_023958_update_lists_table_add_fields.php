<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('lists', function (Blueprint $table) {
            $table->string('featured_image')->nullable()->after('description');
            $table->string('slug')->unique()->nullable()->after('name');
            $table->integer('view_count')->default(0)->after('is_public');
            $table->json('settings')->nullable()->after('view_count');
        });
    }

    public function down()
    {
        Schema::table('lists', function (Blueprint $table) {
            $table->dropColumn(['featured_image', 'slug', 'view_count', 'settings']);
        });
    }
};