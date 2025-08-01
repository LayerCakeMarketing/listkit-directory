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
        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['system', 'claim', 'announcement', 'follow', 'list', 'channel']);
            $table->string('title');
            $table->text('message');
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            
            // For linking to specific resources
            $table->string('related_type')->nullable(); // e.g., 'claim', 'place', 'list'
            $table->unsignedBigInteger('related_id')->nullable();
            
            // Action URL for clicking the notification
            $table->string('action_url')->nullable();
            
            // Read status
            $table->timestamp('read_at')->nullable();
            
            // Priority and metadata
            $table->enum('priority', ['low', 'normal', 'high'])->default('normal');
            $table->json('metadata')->nullable(); // Store extra data like claim status, etc.
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['recipient_id', 'read_at']);
            $table->index(['type', 'created_at']);
            $table->index(['related_type', 'related_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_notifications');
    }
};