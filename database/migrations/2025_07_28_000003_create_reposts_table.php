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
        Schema::create('reposts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->morphs('repostable'); // repostable_type and repostable_id
            $table->text('comment')->nullable(); // Optional comment when reposting
            $table->timestamps();
            
            // Unique constraint to prevent duplicate reposts
            $table->unique(['user_id', 'repostable_type', 'repostable_id']);
            
            // Additional index for performance (morphs already creates an index)
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reposts');
    }
};