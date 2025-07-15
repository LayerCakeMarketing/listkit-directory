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
        // Add is_featured flag to existing place_regions table
        if (Schema::hasTable('place_regions')) {
            Schema::table('place_regions', function (Blueprint $table) {
                if (!Schema::hasColumn('place_regions', 'is_featured')) {
                    $table->boolean('is_featured')->default(false)->after('region_id');
                    $table->integer('featured_order')->nullable()->after('is_featured');
                    $table->timestamp('featured_at')->nullable()->after('featured_order');
                    $table->index(['region_id', 'is_featured']);
                }
            });
        }
        
        // Create region featured metadata table
        Schema::create('region_featured_metadata', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained()->cascadeOnDelete();
            $table->string('featured_title')->nullable();
            $table->text('featured_description')->nullable();
            $table->integer('max_featured_places')->default(10);
            $table->boolean('show_featured_section')->default(true);
            $table->timestamps();
            
            $table->unique('region_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('region_featured_metadata');
        
        if (Schema::hasTable('place_regions')) {
            Schema::table('place_regions', function (Blueprint $table) {
                $table->dropColumn(['is_featured', 'featured_order', 'featured_at']);
            });
        }
    }
};