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
        Schema::table('lists', function (Blueprint $table) {
            // Add visibility enum column
            $table->string('visibility')->default('public')->after('is_public');
            
            // Update existing data based on is_public
            // Keep is_public for backward compatibility for now
        });
        
        // Update existing records
        DB::statement("UPDATE lists SET visibility = CASE WHEN is_public = true THEN 'public' ELSE 'private' END");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lists', function (Blueprint $table) {
            $table->dropColumn('visibility');
        });
    }
};
