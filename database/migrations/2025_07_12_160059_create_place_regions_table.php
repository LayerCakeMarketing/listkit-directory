<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('place_regions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('place_id');
            $table->unsignedBigInteger('region_id');
            $table->string('association_type', 50); // 'contained', 'nearby', 'user_assigned'
            $table->decimal('distance_meters', 10, 2)->nullable(); // For 'nearby' associations
            $table->decimal('confidence_score', 3, 2)->nullable(); // 0-1 confidence in association
            
            // Denormalized for performance
            $table->string('region_type', 50)->nullable(); // Copied from regions.type
            $table->integer('region_level')->nullable(); // Copied from regions.level
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('place_id')->references('id')->on('directory_entries')->onDelete('cascade');
            $table->foreign('region_id')->references('id')->on('regions')->onDelete('cascade');
            
            // Indexes for fast lookups
            $table->unique(['place_id', 'region_id']);
            $table->index(['region_id', 'place_id']);
            $table->index(['region_id', 'region_type']);
            $table->index(['place_id', 'region_type']);
            $table->index('association_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('place_regions');
    }
};