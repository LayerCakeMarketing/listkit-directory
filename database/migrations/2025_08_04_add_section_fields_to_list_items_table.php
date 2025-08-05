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
        Schema::table('list_items', function (Blueprint $table) {
            // Add section support columns
            $table->boolean('is_section')->default(false)->after('type');
            $table->unsignedBigInteger('section_id')->nullable()->after('list_id');
            
            // Add indexes
            $table->index('is_section');
            $table->index('section_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('list_items', function (Blueprint $table) {
            $table->dropColumn(['is_section', 'section_id']);
        });
    }
};