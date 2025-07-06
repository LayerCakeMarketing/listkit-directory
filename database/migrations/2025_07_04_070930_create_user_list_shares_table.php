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
        Schema::create('user_list_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('list_id')->constrained('lists')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('shared_by')->constrained('users')->onDelete('cascade');
            $table->enum('permission', ['view', 'edit'])->default('view');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            // Ensure a user can only be shared a list once
            $table->unique(['list_id', 'user_id']);
            
            // Indexes for performance
            $table->index(['user_id', 'list_id']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_list_shares');
    }
};