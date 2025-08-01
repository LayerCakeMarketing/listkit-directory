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
            $table->foreignId('user_id')->after('id')->constrained()->onDelete('cascade');
            $table->morphs('commentable'); // commentable_type and commentable_id
            $table->text('content')->after('commentable_id');
            $table->foreignId('parent_id')->nullable()->after('content')->constrained('comments')->onDelete('cascade');
            $table->integer('replies_count')->default(0)->after('parent_id');
            $table->integer('likes_count')->default(0)->after('replies_count');
            $table->json('mentions')->nullable()->after('likes_count'); // Store @mentions
            $table->softDeletes();
            
            // Additional indexes for performance (morphs already creates an index)
            $table->index(['parent_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['parent_id']);
            $table->dropMorphs('commentable');
            $table->dropColumn([
                'user_id',
                'content',
                'parent_id',
                'replies_count',
                'likes_count',
                'mentions',
                'deleted_at'
            ]);
        });
    }
};