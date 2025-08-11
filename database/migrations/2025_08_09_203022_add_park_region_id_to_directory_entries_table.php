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
            // Add park_region_id column for associating places with parks
            $table->unsignedBigInteger('park_region_id')->nullable()->after('neighborhood_region_id');
            
            // Add foreign key constraint
            $table->foreign('park_region_id')
                  ->references('id')
                  ->on('regions')
                  ->onDelete('set null');
            
            // Add index for performance
            $table->index('park_region_id');
            $table->index(['park_region_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('directory_entries', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['park_region_id', 'status']);
            $table->dropIndex(['park_region_id']);
            
            // Drop foreign key
            $table->dropForeign(['park_region_id']);
            
            // Drop column
            $table->dropColumn('park_region_id');
        });
    }
};
