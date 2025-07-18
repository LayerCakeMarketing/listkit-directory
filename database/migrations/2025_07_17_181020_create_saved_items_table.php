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
        Schema::create('saved_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->morphs('saveable'); // Creates saveable_id and saveable_type
            $table->string('notes')->nullable(); // For future extensibility
            $table->timestamps();
            
            // Prevent duplicate saves
            $table->unique(['user_id', 'saveable_id', 'saveable_type']);
            
            // Add indexes for performance
            $table->index(['user_id', 'saveable_type']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saved_items');
    }
};
