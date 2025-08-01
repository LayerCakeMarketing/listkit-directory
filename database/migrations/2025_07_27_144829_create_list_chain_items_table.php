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
        Schema::create('list_chain_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('list_chain_id')->constrained()->cascadeOnDelete();
            $table->foreignId('list_id')->constrained('lists')->cascadeOnDelete();
            
            // Order in the chain (e.g., Day 1, Day 2, Step 1, Step 2)
            $table->integer('order_index')->default(0);
            
            // Optional label for this item in the chain (e.g., "Day 1", "Morning Routine")
            $table->string('label')->nullable();
            
            // Optional description specific to this list in this chain
            $table->text('description')->nullable();
            
            $table->timestamps();
            
            // Ensure a list can only be in a chain once
            $table->unique(['list_chain_id', 'list_id']);
            
            // Index for ordering with unique name
            $table->index(['list_chain_id', 'order_index'], 'idx_list_chain_items_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('list_chain_items');
    }
};
