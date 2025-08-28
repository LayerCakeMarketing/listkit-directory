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
        Schema::table('comments', function (Blueprint $table) {
            // Add depth to track nesting level (0 = root, 1 = direct reply, 2+ = nested replies)
            $table->integer('depth')->default(0)->after('parent_id');
            
            // Track who this comment is directly replying to (for "replying to" display)
            $table->foreignId('reply_to_user_id')->nullable()
                ->after('depth')
                ->constrained('users')
                ->onDelete('set null');
            
            // Add indexes for efficient querying
            $table->index(['commentable_type', 'commentable_id', 'depth']);
            $table->index('reply_to_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropForeign(['reply_to_user_id']);
            $table->dropColumn(['depth', 'reply_to_user_id']);
        });
    }
};