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
        Schema::create('saved_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('color', 20)->default('gray'); // For UI color coding
            $table->string('icon', 30)->nullable(); // Icon name for display
            $table->integer('order_index')->default(0);
            $table->boolean('is_default')->default(false); // For system collections
            $table->timestamps();
            
            $table->unique(['user_id', 'slug']);
            $table->index(['user_id', 'order_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saved_collections');
    }
};
