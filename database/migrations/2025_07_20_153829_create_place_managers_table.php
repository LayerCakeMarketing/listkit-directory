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
        Schema::create('place_managers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('place_id');
            $table->morphs('manageable'); // Creates manageable_type and manageable_id
            $table->string('role')->default('manager'); // owner, manager, claimed_owner
            $table->json('permissions')->nullable(); // For granular permissions
            $table->boolean('is_active')->default(true);
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('invited_at')->nullable();
            $table->unsignedBigInteger('invited_by')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->foreign('place_id')->references('id')->on('directory_entries')->onDelete('cascade');
            $table->foreign('invited_by')->references('id')->on('users')->onDelete('set null');
            $table->unique(['place_id', 'manageable_id', 'manageable_type'], 'unique_place_manager');
            $table->index(['manageable_type', 'manageable_id'], 'place_managers_manageable_index');
            $table->index('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('place_managers');
    }
};