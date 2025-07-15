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
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('pinned_at')->nullable();
            
            // Add indexes for performance
            $table->index(['user_id', 'is_pinned', 'pinned_at']);
            $table->index(['user_id', 'visibility', 'is_pinned']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lists', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'is_pinned', 'pinned_at']);
            $table->dropIndex(['user_id', 'visibility', 'is_pinned']);
            $table->dropColumn(['is_pinned', 'pinned_at']);
        });
    }
};