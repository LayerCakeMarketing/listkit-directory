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
        // Check if claims table exists
        if (!Schema::hasTable('claims')) {
            // Create the table if it doesn't exist
            Schema::create('claims', function (Blueprint $table) {
                $table->id();
                $table->foreignId('place_id')->constrained('directory_entries')->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->enum('status', ['pending', 'approved', 'rejected', 'expired'])->default('pending');
                $table->enum('verification_method', ['email', 'phone', 'document', 'manual']);
                $table->string('business_email')->nullable();
                $table->string('business_phone')->nullable();
                $table->text('verification_notes')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('rejected_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users');
                $table->foreignId('rejected_by')->nullable()->constrained('users');
                $table->json('metadata')->nullable();
                $table->timestamps();
                
                // Indexes
                $table->index(['place_id', 'status']);
                $table->index(['user_id', 'status']);
                $table->index('status');
                $table->index('expires_at');
            });
        } else {
            // Check if place_id column exists
            if (!Schema::hasColumn('claims', 'place_id')) {
                Schema::table('claims', function (Blueprint $table) {
                    // Check if business_id exists and rename it
                    if (Schema::hasColumn('claims', 'business_id')) {
                        $table->renameColumn('business_id', 'place_id');
                    } else {
                        // Add place_id if neither exists
                        $table->foreignId('place_id')->after('id')->constrained('directory_entries')->onDelete('cascade');
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothing to do - this is a fix migration
    }
};