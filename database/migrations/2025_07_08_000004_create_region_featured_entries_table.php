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
        Schema::create('region_featured_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('region_id');
            $table->unsignedBigInteger('directory_entry_id');
            $table->integer('priority')->default(0);
            $table->string('label')->nullable();
            $table->string('tagline')->nullable();
            $table->jsonb('custom_data')->nullable();
            $table->date('featured_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('region_id')->references('id')->on('regions')->onDelete('cascade');
            $table->foreign('directory_entry_id')->references('id')->on('directory_entries')->onDelete('cascade');
            
            // Indexes
            $table->unique(['region_id', 'directory_entry_id']);
            $table->index(['region_id', 'priority']);
            $table->index(['region_id', 'is_active', 'priority']);
            $table->index('featured_until');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('region_featured_entries');
    }
};