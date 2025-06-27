<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('list_items', function (Blueprint $table) {
            // Make directory_entry_id nullable since not all items will be directory entries
            $table->unsignedBigInteger('directory_entry_id')->nullable()->change();
            
            // Add new fields for flexible content
            $table->enum('type', ['directory_entry', 'text', 'location', 'event'])->default('directory_entry')->after('list_id');
            $table->string('title')->nullable()->after('type');
            $table->text('content')->nullable()->after('title');
            $table->json('data')->nullable()->after('content'); // For storing type-specific data
            $table->string('image')->nullable()->after('data');
        });
    }

    public function down()
    {
        Schema::table('list_items', function (Blueprint $table) {
            $table->dropColumn(['type', 'title', 'content', 'data', 'image']);
            $table->unsignedBigInteger('directory_entry_id')->nullable(false)->change();
        });
    }
};